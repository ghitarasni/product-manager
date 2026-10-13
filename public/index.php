<?php
/**
 * Halaman Utama (READ): Katalog Sepatu Branded
 * Mini Project 2: Pemrograman Web Pertemuan 3
 * Fitur:
 * - Read Data Produk & Relasi Kategori (JOIN)
 * - Tampilan Kartu Responsif + Foto Sepatu Nyata
 * - Filter Kategori & Pencarian (Bonus)
 * - Proteksi CSRF Delete
 * - XSS Escaping di semua output
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// Generate CSRF Token untuk request penghapusan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil daftar kategori untuk filter dropdown
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categoriesList = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categoriesList = [];
}

// Parameter Pencarian & Filter
$search = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

try {
    $sql = "SELECT p.*, c.name AS category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (p.name LIKE :search_name OR p.sku LIKE :search_sku)";
        $params['search_name'] = "%{$search}%";
        $params['search_sku'] = "%{$search}%";
    }

    if ($categoryFilter !== '') {
        $sql .= " AND p.category_id = :cat_id";
        $params['cat_id'] = (int)$categoryFilter;
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat katalog produk: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrandKicks - Katalog Sepatu Branded</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="app-header">
            <div class="brand-wrapper">
                <div class="brand-badge">BK</div>
                <div class="brand-info">
                    <h1>BrandKicks Vault</h1>
                    <p>Sistem Katalog & Inventaris Sepatu Branded Original</p>
                </div>
            </div>
            <div>
                <a href="create.php" class="btn btn-primary">+ Tambah Produk</a>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if ($flash_success): ?>
            <div class="alert alert-success">
                <span><?= htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flash_error): ?>
            <div class="alert alert-danger">
                <span><?= htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <!-- Toolbar Pencarian & Filter Kategori -->
        <div class="toolbar-card">
            <form action="index.php" method="GET" class="search-filter-group">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Cari seri sepatu atau kode SKU..." 
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                >
                <select name="category" class="category-select" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary">Cari</button>
                <?php if ($search !== '' || $categoryFilter !== ''): ?>
                    <a href="index.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
            <div class="toolbar-stats">
                Menampilkan <strong><?= count($products) ?></strong> koleksi sepatu
            </div>
        </div>

        <!-- Katalog Kartu Produk (Responsif & Anti-Slop) -->
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div class="empty-hero">
                    <div class="empty-icon">👟</div>
                    <h3>Katalog Masih Kosong</h3>
                    <p><?= ($search !== '' || $categoryFilter !== '') ? 'Tidak ada produk yang cocok dengan kriteria pencarian Anda.' : 'Belum ada produk yang terdaftar dalam inventaris.' ?></p>
                    <a href="create.php" class="btn btn-primary">+ Tambah Produk Pertama</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $item): ?>
                    <?php 
                        $categoryName = $item['category_name'] ?? 'Koleksi Umum';
                        $imageFile = $item['image_path'] ?? '';
                        $imageSrc = (!empty($imageFile) && file_exists(__DIR__ . '/uploads/' . basename($imageFile))) 
                                    ? 'uploads/' . htmlspecialchars(basename($imageFile), ENT_QUOTES, 'UTF-8') 
                                    : 'uploads/default.jpg';
                    ?>
                    <div class="shoe-card">
                        <div class="shoe-media">
                            <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" class="shoe-img" loading="lazy">
                            <div class="shoe-badge-group">
                                <span class="badge-tag"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($item['sku'])): ?>
                                    <span class="badge-sku"><?= htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="shoe-body">
                            <div>
                                <h2 class="shoe-title"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                
                                <div class="shoe-pricing-row">
                                    <div class="shoe-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                    <div class="shoe-stock <?= (int)$item['stock'] > 0 ? 'stock-in' : 'stock-out' ?>">
                                        <?= (int)$item['stock'] > 0 ? (int)$item['stock'] . ' pasang' : 'Stok Habis' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="shoe-actions">
                                <a href="edit.php?id=<?= (int)$item['id'] ?>" class="btn btn-secondary btn-sm" style="flex: 1;">Edit Data</a>
                                
                                <!-- Delete form dengan proteksi POST & CSRF Token -->
                                <form action="delete.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" style="flex: 1;">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-danger-outline btn-sm" style="width: 100%;">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
