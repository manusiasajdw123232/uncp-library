(function () {
  const root = document.documentElement;
  const isFixedPublicTheme = root.dataset.publicTheme === 'fixed-dark';

  function systemTheme() {
    return window.matchMedia &&
      window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';
  }

  function applyTheme(mode) {
    if (isFixedPublicTheme) {
      root.dataset.theme = 'dark';
      root.dataset.themeMode = 'fixed';
      return;
    }

    const resolved = mode === 'system' ? systemTheme() : mode;

    document.documentElement.dataset.theme = resolved;
    document.documentElement.dataset.themeMode = mode;

    window.dispatchEvent(
      new CustomEvent('uncp:themechange', {
        detail: {
          mode,
          resolved,
        },
      })
    );
  }

  function getMode() {
    try {
      return localStorage.getItem('uncp-theme') || 'light';
    } catch (e) {
      return 'light';
    }
  }

  function applyDisplaySettings() {
    if (isFixedPublicTheme) {
      root.dataset.textSize = 'medium';
      root.dataset.density = 'normal';
      root.dataset.motion = 'light';
      root.dataset.sidebarMode = 'default';
      return;
    }

    root.dataset.textSize = localStorage.getItem('uncp-text-size') || 'medium';
    root.dataset.density = localStorage.getItem('uncp-density') || 'normal';
    root.dataset.motion = localStorage.getItem('uncp-motion') || 'light';
    root.dataset.sidebarMode = localStorage.getItem('uncp-sidebar') || 'default';
  }

  applyTheme(getMode());
  applyDisplaySettings();

  if (!isFixedPublicTheme && window.matchMedia) {
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const onChange = function () {
      if (getMode() === 'system') {
        applyTheme('system');
      }
    };

    if (media.addEventListener) {
      media.addEventListener('change', onChange);
    } else if (media.addListener) {
      media.addListener(onChange);
    }
  }

  window.setTheme = function (mode) {
    if (isFixedPublicTheme) return;

    if (!['light', 'dark', 'system'].includes(mode)) {
      mode = 'light';
    }

    try {
      localStorage.setItem('uncp-theme', mode);
    } catch (e) {}

    applyTheme(mode);
  };

  window.setDisplaySetting = function (key, value) {
    if (isFixedPublicTheme) return;

    try {
      localStorage.setItem('uncp-' + key, value);
    } catch (e) {}

    applyDisplaySettings();
  };
})();
