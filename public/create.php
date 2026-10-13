<?php
/**
 * Halaman CREATE: Tambah Koleksi Sepatu Baru
 * Mendukung:
 * - Input SKU otomatis / custom
 * - Pemilihan Kategori dari relasi database
 * - Upload gambar sepatu (JPG, PNG, WEBP) atau default
 * - Validasi server-side lengkap
 * - Pola PRG (Post-Redirect-Get)
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// Ambil list kategori untuk select form
$categories = [];
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$errors = [];
$name = '';
$category_id = '';
$price = '';
$stock = '';
$sku = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $sku = trim($_POST['sku'] ?? '');

    // Validasi Nama
    if ($name === '') {
        $errors[] = 'Nama produk sepatu wajib diisi.';
    } elseif (mb_strlen($name) < 3) {
        $errors[] = 'Nama produk minimal 3 karakter.';
    }

    // Validasi Kategori
    if ($category_id === '' || !filter_var($category_id, FILTER_VALIDATE_INT)) {
        $errors[] = 'Kategori produk wajib dipilih.';
    }

    // Validasi Harga
    if ($price === '' || !is_numeric($price)) {
        $errors[] = 'Harga harus berupa angka valid.';
    } elseif ((float)$price <= 0) {
        $errors[] = 'Harga harus lebih besar dari Rp 0.';
    }

    // Validasi Stok
    if ($stock === '' || (!filter_var($stock, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) && $stock !== '0')) {
        $errors[] = 'Stok harus berupa bilangan bulat dan tidak boleh negatif.';
    }

    // Generate SKU otomatis jika kosong
    if ($sku === '') {
        $sku = 'SHOE-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
    }

    // Cek Unik Nama & SKU
    if (empty($errors)) {
        $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmtCheck->execute(['name' => $name]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Produk dengan nama tersebut sudah ada di katalog.';
        }
    }

    // Penanganan Upload Gambar
    $imageFilename = 'default.jpg';
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = 'Format gambar hanya boleh JPG, PNG, atau WEBP.';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 3MB.';
        } elseif ($file['error'] === UPLOAD_ERR_OK) {
            $imageFilename = 'shoe_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $imageFilename)) {
                $errors[] = 'Gagal menyimpan file gambar yang diunggah.';
            }
        }
    }

    // Simpan ke database dengan Prepared Statement
    if (empty($errors)) {
        try {
            $stmtInsert = $pdo->prepare(
                "INSERT INTO products (sku, name, category_id, price, stock, image_path, created_at, updated_at) 
                 VALUES (:sku, :name, :category_id, :price, :stock, :image_path, NOW(), NOW())"
            );
            $stmtInsert->execute([
                'sku'         => $sku,
                'name'        => $name,
                'category_id' => (int)$category_id,
                'price'       => (float)$price,
                'stock'       => (int)$stock,
                'image_path'  => $imageFilename
            ]);

            $_SESSION['flash_success'] = "Koleksi sepatu '{$name}' berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan produk: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Sepatu Baru - BrandKicks</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="form-shell">
            <div class="form-shell-header">
                <h2>Tambah Koleksi Sepatu</h2>
                <p>Masukkan rincian informasi dan foto produk sepatu branded</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="display: block;">
                    <strong>Perhatian:</strong>
                    <ul style="margin-left: 20px; margin-top: 6px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="create.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group-full">
                        <label class="form-label" for="name">Nama Seri Sepatu *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
                            placeholder="Contoh: Nike Air Jordan 1 Retro High OG" 
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                        <div class="form-help">Minimal 3 karakter dan nama harus unik.</div>
                    </div>

                    <div>
                        <label class="form-label" for="category_id">Kategori Sepatu *</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="sku">Kode SKU (Opsional)</label>
                        <input 
                            type="text" 
                            id="sku" 
                            name="sku" 
                            class="form-input" 
                            placeholder="Biarkan kosong untuk otomatis" 
                            value="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div>
                        <label class="form-label" for="price">Harga Satuan (Rp) *</label>
                        <input 
                            type="number" 
                            id="price" 
                            name="price" 
                            class="form-input" 
                            placeholder="Contoh: 2199000" 
                            min="1" 
                            value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                        <div class="form-help">Harga harus lebih dari 0.</div>
                    </div>

                    <div>
                        <label class="form-label" for="stock">Stok (Pasang) *</label>
                        <input 
                            type="number" 
                            id="stock" 
                            name="stock" 
                            class="form-input" 
                            placeholder="Contoh: 12" 
                            min="0" 
                            value="<?= htmlspecialchars($stock, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                        <div class="form-help">Minimal 0 (tidak boleh negatif).</div>
                    </div>

                    <div class="form-group-full">
                        <label class="form-label" for="image">Foto Produk (Opsional)</label>
                        <input 
                            type="file" 
                            id="image" 
                            name="image" 
                            class="form-input" 
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <div class="form-help">Format didukung: JPG, PNG, WEBP. Maksimal ukuran 3MB. Jika dikosongkan akan menggunakan gambar sampel standar.</div>
                    </div>
                </div>

                <div class="form-footer-actions">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Koleksi</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
