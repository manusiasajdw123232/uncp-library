(function () {
    const SIDEBAR_SCROLL_KEY = 'uncp-scroll-sidebar-v5';
    let navTicking = false;
    let modalScroll = { x: 0, y: 0, nav: 0 };
    let modalReturnFocus = null;

    const sidebar = () => document.getElementById('sidebar');
    const nav = () => document.getElementById('mainNav');

    function storage() {
        try {
            return window.sessionStorage;
        } catch (e) {
            return null;
        }
    }

    function saveSidebarScroll() {
        const s = storage();
        const n = nav();

        if (!s || !n) return;

        try {
            s.setItem(
                SIDEBAR_SCROLL_KEY,
                String(Math.max(0, n.scrollTop || 0))
            );
        } catch (e) {}
    }

    function restoreSidebarScroll() {
        const s = storage();
        const n = nav();

        if (!s || !n) return;

        try {
            const value = Math.max(
                0,
                Number(s.getItem(SIDEBAR_SCROLL_KEY)) || 0
            );

            // Apply immediately before the browser paints a second frame.
            n.scrollTop = value;
        } catch (e) {}
    }

    function saveScrollState() {
        saveSidebarScroll();
    }

    function syncScrollLock() {
        const root = document.documentElement;
        const locked =
            !!document.querySelector('.modal-backdrop.is-open') ||
            !!sidebar()?.classList.contains('open');

        root.classList.toggle('scroll-locked', locked);
    }

    window.lockPageScroll = () => {
        syncScrollLock();
    };

    window.unlockPageScroll = () => {
        syncScrollLock();
    };

    window.captureModalScroll = function () {
        modalScroll = {
            x: window.scrollX || 0,
            y: window.scrollY || 0,
            nav: nav()?.scrollTop || 0,
        };

        modalReturnFocus =
            document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;

        return modalScroll;
    };

    window.restoreModalScroll = function () {
        const p = modalScroll || { x: 0, y: 0, nav: 0 };

        // Restoring is synchronous so closing a modal does not visibly jump.
        window.scrollTo(
            Math.max(0, Number(p.x) || 0),
            Math.max(0, Number(p.y) || 0)
        );

        const n = nav();

        if (n) {
            n.scrollTop = Math.max(0, Number(p.nav) || 0);
        }

        if (modalReturnFocus && document.contains(modalReturnFocus)) {
            try {
                modalReturnFocus.focus({ preventScroll: true });
            } catch (e) {
                try {
                    modalReturnFocus.focus();
                } catch (e2) {}
            }
        }
    };

    window.restoreScrollState = function () {
        restoreSidebarScroll();
    };

    window.saveScrollState = saveScrollState;

    window.toggleSidebar = function () {
        const sb = sidebar();

        if (!sb) return;

        const open = !sb.classList.contains('open');
        sb.classList.toggle('open', open);

        const btn = document.querySelector('.mobile-menu');

        if (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        syncScrollLock();
    };

    window.closeSidebar = function () {
        const sb = sidebar();

        if (sb) {
            sb.classList.remove('open');
        }

        const btn = document.querySelector('.mobile-menu');

        if (btn) {
            btn.setAttribute('aria-expanded', 'false');
        }

        syncScrollLock();
    };

    window.confirmAction = function (message) {
        return window.confirm(message || 'Apakah Anda yakin?');
    };

    function initToasts() {
        const TOAST_DURATION = 2300;

        document.querySelectorAll('[data-toast]').forEach(function (toast) {
            const close = function () {
                if (toast.classList.contains('is-leaving')) return;

                toast.classList.add('is-leaving');
                window.setTimeout(() => toast.remove(), 180);
            };

            toast
                .querySelector('[data-toast-close]')
                ?.addEventListener('click', close);

            window.setTimeout(close, TOAST_DURATION);
        });
    }

    function closeOpenModal() {
        const modal = document.querySelector('.modal-backdrop.is-open');

        if (!modal) return false;

        const button = modal.querySelector('[data-modal-cancel]');

        if (button) {
            button.click();
            return true;
        }

        // Custom page modals already close when the backdrop itself is clicked.
        modal.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        if (document.documentElement.classList.contains('scroll-locked')) {
            const closeButton = modal.querySelector(
                '[data-close-book-modal],[data-close-repo-modal]'
            );

            if (closeButton) {
                closeButton.click();
            }
        }

        return true;
    }

    function initConfirmModal() {
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest?.('[data-confirm]');

            if (!trigger || trigger.dataset.confirmBypass === '1') return;

            e.preventDefault();
            window.captureModalScroll();

            const message = trigger.dataset.confirm || 'Apakah Anda yakin?';
            const modal = document.createElement('div');

            modal.className = 'modal-backdrop is-open';
            modal.innerHTML =
                '<div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">' +
                '<div class="modal-icon">!</div>' +
                '<h2 id="confirmTitle">Konfirmasi</h2>' +
                '<p>' +
                message.replace(
                    /[&<>"\']/g,
                    (m) =>
                        ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;',
                        })[m]
                ) +
                '</p>' +
                '<div class="modal-actions">' +
                '<button type="button" class="btn btn-light" data-modal-cancel>Tidak</button>' +
                '<button type="button" class="btn btn-danger" data-modal-ok>Ya, Lanjutkan</button>' +
                '</div></div>';

            document.body.appendChild(modal);
            syncScrollLock();

            const close = () => {
                modal.remove();
                syncScrollLock();
                window.restoreModalScroll();
            };

            modal
                .querySelector('[data-modal-cancel]')
                ?.addEventListener('click', close);

            modal.querySelector('[data-modal-ok]')?.addEventListener(
                'click',
                function () {
                    trigger.dataset.confirmBypass = '1';
                    close();

                    if (trigger.matches('a')) {
                        location.href = trigger.href;
                    } else if (trigger.form) {
                        trigger.form.requestSubmit(
                            trigger.type === 'submit' ? trigger : null
                        );
                    } else if (typeof trigger.click === 'function') {
                        trigger.click();
                    }
                }
            );

            modal.addEventListener('click', (ev) => {
                if (ev.target === modal) close();
            });

            setTimeout(
                () => modal.querySelector('[data-modal-ok]')?.focus(),
                0
            );
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Do not force the main document back to a saved position. Normal browser navigation
        // should remain stable; only the sidebar menu position is persisted.
        try {
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'auto';
            }
        } catch (e) {}

        restoreSidebarScroll();
        initToasts();
        initConfirmModal();

        document.querySelectorAll('[data-nav-link]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (this.getAttribute('href')?.startsWith('#')) return;

                saveSidebarScroll();

                if (window.innerWidth < 768) {
                    window.closeSidebar();
                }
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                saveSidebarScroll();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (closeOpenModal()) return;
                window.closeSidebar();
            }
        });

        const navEl = nav();

        if (navEl) {
            navEl.addEventListener(
                'scroll',
                function () {
                    if (navTicking) return;

                    navTicking = true;

                    requestAnimationFrame(function () {
                        saveSidebarScroll();
                        navTicking = false;
                    });
                },
                { passive: true }
            );
        }

        window.addEventListener('pagehide', saveSidebarScroll);

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                window.closeSidebar();
            }
        });
    });
})();
