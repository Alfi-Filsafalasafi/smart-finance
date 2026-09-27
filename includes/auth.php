<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Pastikan user sudah login, kalau belum redirect ke login
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Pastikan user login dan sesuai role tertentu
 * @param string $role 'siswa' atau 'guru'
 */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Simpan pesan flash (sekali tampil setelah redirect)
 * @param string $message
 * @param string $type 'success' | 'danger' | 'warning' | 'info'
 */
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Ambil & hapus pesan flash (dipanggil sekali di header.php)
 */
function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}