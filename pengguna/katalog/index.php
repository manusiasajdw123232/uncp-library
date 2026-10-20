<?php

require __DIR__ . '/../../app/core/bootstrap.php';

$u = require_roles(['pengguna']);

require __DIR__ . '/../../app/helpers/layout.php';

render_start('Katalog Buku', 'Koleksi buku yang telah disetujui', $u);
dashboard_nav('pengguna');
render_mid(
    'Katalog Buku',
    'Cari berdasarkan judul, penulis, ISBN, kategori, atau tahun',
    $u
);

$q = trim($_GET['q'] ?? '');
$cat = (int) ($_GET['category'] ?? 0);
$year = (int) ($_GET['year'] ?? 0);

$sql = "SELECT b.*,c.name category,p.name publisher FROM books b
        LEFT JOIN book_categories c ON c.id=b.category_id
        LEFT JOIN publishers p ON p.id=b.publisher_id
        WHERE b.approval_status='disetujui'";

$params = [];

if ($q !== '') {
    $sql .= ' AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR c.name LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

if ($cat) {
    $sql .= ' AND b.category_id=?';
    $params[] = $cat;
}

if ($year) {
    $sql .= ' AND b.publication_year=?';
    $params[] = $year;
}

$sql .= ' ORDER BY b.id DESC LIMIT 200';

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$cats = $pdo
    ->query('SELECT id,name FROM book_categories ORDER BY name')
    ->fetchAll();
?>

<div class="card" style="margin-bottom: 18px">
    <form class="toolbar" method="get">
        <input
            class="input search"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Cari judul, penulis, ISBN, kategori..."
        >

        <select name="category" style="max-width: 230px">
            <option value="0">Semua kategori</option>

            <?php foreach ($cats as $c): ?>
                <option
                    value="<?= $c['id'] ?>"
                    <?= $cat === $c['id'] ? 'selected' : '' ?>
                >
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input
            class="input"
            style="max-width: 130px"
            name="year"
            value="<?= e($year ?: '') ?>"
            placeholder="Tahun"
        >

        <button class="btn btn-primary">Cari</button>
    </form>
</div>

<div class="grid grid-4 book-catalog-grid">
    <?php foreach ($rows as $b): ?>
        <article class="card book-card">
            <a href="/pengguna/detail-buku/index.php?id=<?= $b['id'] ?>">
                <?php if ($b['cover']): ?>
                    <img
                        class="book-cover"
                        src="<?= e($b['cover']) ?>"
                        alt="Cover"
                        loading="lazy"
                        decoding="async"
                    >
                <?php else: ?>
                    <div class="cover-placeholder">
                        UNCP<br>
                        LIBRARY
                    </div>
                <?php endif; ?>
            </a>

            <h3><?= e($b['title']) ?></h3>
            <p><?= e($b['author']) ?></p>
            <p>
                <?= e($b['publication_year'] ?? '—') ?>
                ·
                <?= e($b['category'] ?? 'Tanpa kategori') ?>
            </p>

            <div class="book-card-actions">
                <span class="badge green">Tersedia</span>

                <a
                    class="btn btn-light"
                    href="/pengguna/detail-buku/index.php?id=<?= $b['id'] ?>"
                >
                    Lihat Detail
                </a>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$rows): ?>
        <div
            class="card empty"
            style="grid-column: 1 / -1"
        >
            Buku yang sesuai pencarian tidak ditemukan.
        </div>
    <?php endif; ?>
</div>

<?php render_end(); ?>
