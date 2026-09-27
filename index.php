<?php
require_once __DIR__ . '/config/database.php';

// Ambil jumlah siswa & guru dari database
try {
    $stmtSiswa = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'siswa'");
    $totalSiswa = $stmtSiswa->fetch()['total'];

    $stmtGuru = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'guru'");
    $totalGuru = $stmtGuru->fetch()['total'];

    $stmtTransaksi = $pdo->query("SELECT COUNT(*) AS total FROM transaksi");
    $totalTransaksi = $stmtTransaksi->fetch()['total'];
} catch (Exception $e) {
    $totalSiswa = 0;
    $totalGuru = 0;
    $totalTransaksi = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Smart Finance - Pencatatan Keuangan Siswa</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <i class="fa-solid fa-wallet text-primary me-1"></i>Smart<span class="text-primary">Finance</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
        <li class="nav-item"><a class="nav-link" href="#statistik">Statistik</a></li>
        <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
        <li class="nav-item mt-2 mt-lg-0">
          <a href="auth/login.php" class="btn btn-primary rounded-pill px-4">Masuk</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<header class="hero-section">
  <div class="container py-5">
    <div class="row align-items-center g-5 py-lg-5">
      <div class="col-lg-6">
        <span class="badge rounded-pill bg-orange-subtle text-orange fw-semibold px-3 py-2 mb-3">
          <i class="fa-solid fa-graduation-cap me-1"></i> Untuk Siswa & Guru
        </span>
        <h1 class="display-5 fw-bold mb-3">
          Kelola Keuangan Siswa, <span class="text-primary">Lebih Rapi &amp; Transparan</span>
        </h1>
        <p class="lead text-secondary mb-4">
          Smart Finance membantu siswa mencatat pemasukan dan pengeluaran harian lengkap dengan bukti,
          sementara guru dapat memantau langsung kondisi keuangan siswanya secara real-time.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="auth/login.php" class="btn btn-primary btn-lg rounded-pill px-4">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Mulai Sekarang
          </a>
          <a href="#fitur" class="btn btn-outline-primary btn-lg rounded-pill px-4">
            Pelajari Fitur
          </a>
        </div>
      </div>
      <div class="col-lg-6 text-center">
        <i class="fa-solid fa-sack-dollar hero-icon"></i>
      </div>
    </div>
  </div>
</header>

<!-- STATISTIK -->
<section id="statistik" class="py-5 bg-primary-gradient text-white">
  <div class="container py-4">
    <div class="row text-center g-4">
      <div class="col-md-4">
        <i class="fa-solid fa-user-graduate fs-1 mb-3"></i>
        <h2 class="fw-bold display-6"><?= number_format($totalSiswa, 0, ',', '.') ?></h2>
        <p class="mb-0 opacity-75">Siswa Terdaftar</p>
      </div>
      <div class="col-md-4">
        <i class="fa-solid fa-chalkboard-user fs-1 mb-3"></i>
        <h2 class="fw-bold display-6"><?= number_format($totalGuru, 0, ',', '.') ?></h2>
        <p class="mb-0 opacity-75">Guru Aktif</p>
      </div>
      <div class="col-md-4">
        <i class="fa-solid fa-receipt fs-1 mb-3"></i>
        <h2 class="fw-bold display-6"><?= number_format($totalTransaksi, 0, ',', '.') ?></h2>
        <p class="mb-0 opacity-75">Transaksi Tercatat</p>
      </div>
    </div>
  </div>
</section>

<!-- FITUR -->
<section id="fitur" class="py-5">
  <div class="container py-4">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Fitur Unggulan</h2>
      <p class="text-secondary">Semua yang dibutuhkan untuk pencatatan keuangan siswa dalam satu aplikasi</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card h-100 p-4 rounded-4 shadow-sm">
          <div class="icon-box bg-primary-subtle text-primary mb-3">
            <i class="fa-solid fa-money-bill-trend-up"></i>
          </div>
          <h5 class="fw-semibold">Catat Pemasukan &amp; Pengeluaran</h5>
          <p class="text-secondary mb-0">Siswa dapat mencatat setiap transaksi keuangan dengan kategori yang jelas.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100 p-4 rounded-4 shadow-sm">
          <div class="icon-box bg-orange-subtle text-orange mb-3">
            <i class="fa-solid fa-paperclip"></i>
          </div>
          <h5 class="fw-semibold">Upload Bukti Transaksi</h5>
          <p class="text-secondary mb-0">Lampirkan foto struk atau bukti pembayaran untuk setiap catatan.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100 p-4 rounded-4 shadow-sm">
          <div class="icon-box bg-primary-subtle text-primary mb-3">
            <i class="fa-solid fa-eye"></i>
          </div>
          <h5 class="fw-semibold">Pantauan Guru Real-time</h5>
          <p class="text-secondary mb-0">Guru dapat memantau langsung kondisi keuangan siswa yang diampu.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TENTANG -->
<section id="tentang" class="py-5 bg-light">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <i class="fa-solid fa-chart-pie about-icon"></i>
      </div>
      <div class="col-lg-6">
        <h2 class="fw-bold mb-3">Kenapa Smart Finance?</h2>
        <p class="text-secondary mb-4">
          Kami percaya siswa perlu belajar mengelola keuangan sejak dini, dan guru perlu alat yang mudah
          untuk membimbing mereka. Smart Finance menjembatani keduanya dalam satu platform sederhana.
        </p>
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><i class="fa-solid fa-circle-check text-primary me-2"></i>Antarmuka sederhana dan mudah digunakan</li>
          <li><i class="fa-solid fa-circle-check text-primary me-2"></i>Data tersimpan aman di database</li>
          <li><i class="fa-solid fa-circle-check text-primary me-2"></i>Bisa diakses dari perangkat apa saja</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary-gradient text-white text-center">
  <div class="container py-4">
    <h2 class="fw-bold mb-3">Siap Mulai Kelola Keuangan?</h2>
    <p class="mb-4 opacity-75">Masuk sekarang dan mulai catat keuanganmu hari ini.</p>
    <a href="auth/login.php" class="btn btn-light btn-lg rounded-pill px-5 fw-semibold text-primary">
      Masuk ke Smart Finance
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer class="py-4 bg-dark text-light">
  <div class="container text-center">
    <p class="mb-0 small opacity-75">&copy; <?= date('Y') ?> Smart Finance. Semua hak dilindungi.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
