<?php
/**
 * Handler DELETE: Hapus Data Produk
 * Syarat Slide 17 & 18:
 * - Wajib menggunakan HTTP POST
 * - Wajib proteksi CSRF Token
 * - Menggunakan Prepared Statement
 * - Redirect ke index.php dengan status pesan
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan hanya melayani request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Metode tidak diizinkan. Penghapusan harus menggunakan metode POST.');
}

// Verifikasi CSRF Token
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    $_SESSION['flash_error'] = 'Token CSRF tidak valid atau sesi kadaluarsa!';
    header("Location: index.php");
    exit;
}

// Ambil ID produk
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash_error'] = 'ID produk tidak valid.';
    header("Location: index.php");
    exit;
}

try {
    // Ambil nama produk terlebih dahulu untuk notifikasi
    $stmtCheck = $pdo->prepare("SELECT name FROM products WHERE id = :id");
    $stmtCheck->execute(['id' => $id]);
    $product = $stmtCheck->fetch();

    if ($product) {
        $stmtDelete = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmtDelete->execute(['id' => $id]);
        $_SESSION['flash_success'] = "Produk '{$product['name']}' berhasil dihapus.";
    } else {
        $_SESSION['flash_error'] = "Produk tidak ditemukan atau sudah dihapus.";
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = "Gagal menghapus produk: " . $e->getMessage();
}

// Pola PRG (Redirect ke index.php)
header("Location: index.php");
exit;
