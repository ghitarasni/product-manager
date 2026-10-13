<?php
/**
 * Halaman UPDATE: Edit Koleksi Sepatu
 * Mendukung:
 * - Read data sepatu lama via Prepared Statement
 * - Ubah kategori, nama, harga, stok, dan ganti foto produk
 * - Proteksi data dan validasi server-side
 * - Pola PRG (Post-Redirect-Get)
 */

session_start();
require_once __DIR__ . '/../config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    $_SESSION['flash_error'] = 'ID produk tidak ditemukan.';
    header("Location: index.php");
    exit;
}

// Ambil list kategori
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Ambil data produk yang diedit
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['flash_error'] = 'Koleksi sepatu tidak ditemukan.';
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$errors = [];
$name = $product['name'];
$category_id = $product['category_id'];
$price = $product['price'];
$stock = $product['stock'];
$sku = $product['sku'];
$currentImage = $product['image_path'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $sku = trim($_POST['sku'] ?? '');

    // Validasi
    if ($name === '') {
        $errors[] = 'Nama produk sepatu wajib diisi.';
    } elseif (mb_strlen($name) < 3) {
        $errors[] = 'Nama produk minimal 3 karakter.';
    }

    if ($category_id === '' || !filter_var($category_id, FILTER_VALIDATE_INT)) {
        $errors[] = 'Kategori produk wajib dipilih.';
    }

    if ($price === '' || !is_numeric($price)) {
        $errors[] = 'Harga harus berupa angka valid.';
    } elseif ((float)$price <= 0) {
        $errors[] = 'Harga harus lebih besar dari Rp 0.';
    }

    if ($stock === '' || (!filter_var($stock, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) && $stock !== '0')) {
        $errors[] = 'Stok harus berupa bilangan bulat dan tidak boleh negatif.';
    }

    // Cek Unik Nama pada produk lain
    if (empty($errors)) {
        $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1");
        $stmtCheck->execute(['name' => $name, 'id' => $id]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Nama produk sepatu sudah terdaftar pada koleksi lain.';
        }
    }

    // Upload Foto Baru jika diunggah
    $imageFilename = $currentImage;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = 'Format gambar hanya boleh JPG, PNG, atau WEBP.';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 3MB.';
        } elseif ($file['error'] === UPLOAD_ERR_OK) {
            $newFilename = 'shoe_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/';
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
                $imageFilename = $newFilename;
            } else {
                $errors[] = 'Gagal menyimpan file gambar baru.';
            }
        }
    }

    // Update Database
    if (empty($errors)) {
        try {
            $stmtUpdate = $pdo->prepare(
                "UPDATE products 
                 SET name = :name, sku = :sku, category_id = :category_id, price = :price, stock = :stock, image_path = :image_path, updated_at = NOW() 
                 WHERE id = :id"
            );
            $stmtUpdate->execute([
                'name'        => $name,
                'sku'         => $sku,
                'category_id' => (int)$category_id,
                'price'       => (float)$price,
                'stock'       => (int)$stock,
                'image_path'  => $imageFilename,
                'id'          => $id
            ]);

            $_SESSION['flash_success'] = "Koleksi '{$name}' berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sepatu - BrandKicks</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="form-shell">
            <div class="form-shell-header">
                <h2>Edit Koleksi Sepatu</h2>
                <p>Perbarui rincian produk #<?= (int)$id ?></p>
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

            <form action="edit.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$id ?>">

                <div class="form-grid">
                    <div class="form-group-full">
                        <label class="form-label" for="name">Nama Seri Sepatu *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
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
                        <label class="form-label" for="sku">Kode SKU</label>
                        <input 
                            type="text" 
                            id="sku" 
                            name="sku" 
                            class="form-input" 
                            value="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                    </div>

                    <div>
                        <label class="form-label" for="price">Harga Satuan (Rp) *</label>
                        <input 
                            type="number" 
                            id="price" 
                            name="price" 
                            class="form-input" 
                            min="1" 
                            value="<?= htmlspecialchars((string)(float)$price, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                    </div>

                    <div>
                        <label class="form-label" for="stock">Stok (Pasang) *</label>
                        <input 
                            type="number" 
                            id="stock" 
                            name="stock" 
                            class="form-input" 
                            min="0" 
                            value="<?= htmlspecialchars((string)(int)$stock, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                    </div>

                    <div class="form-group-full">
                        <label class="form-label" for="image">Ganti Foto Produk (Opsional)</label>
                        <input 
                            type="file" 
                            id="image" 
                            name="image" 
                            class="form-input" 
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <?php if (!empty($currentImage)): ?>
                            <div class="image-preview-box">
                                <img src="uploads/<?= htmlspecialchars(basename($currentImage), ENT_QUOTES, 'UTF-8') ?>" alt="Preview">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-footer-actions">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
