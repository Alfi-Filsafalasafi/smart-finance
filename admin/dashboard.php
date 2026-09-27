<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Dashboard';

$totalSiswa = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role='siswa'")->fetch()['total'];
$totalGuru = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role='guru'")->fetch()['total'];
$totalTransaksi = $pdo->query("SELECT COUNT(*) AS total FROM transaksi")->fetch()['total'];
$siswaTanpaGuru = $pdo->query("
    SELECT COUNT(*) AS total FROM users u
    WHERE u.role = 'siswa' AND u.id NOT IN (SELECT siswa_id FROM guru_siswa)
")->fetch()['total'];

$userTerbaru = $pdo->query("
    SELECT id, nama, email, role, kelas, created_at FROM users
    WHERE role != 'admin'
    ORDER BY created_at DESC LIMIT 6
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-user-graduate"></i></div>
      <div>
        <p class="text-secondary small mb-1">Total Siswa</p>
        <h4 class="fw-bold mb-0"><?= $totalSiswa ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-orange-subtle text-orange"><i class="fa-solid fa-chalkboard-user"></i></div>
      <div>
        <p class="text-secondary small mb-1">Total Guru</p>
        <h4 class="fw-bold mb-0"><?= $totalGuru ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-receipt"></i></div>
      <div>
        <p class="text-secondary small mb-1">Total Transaksi</p>
        <h4 class="fw-bold mb-0"><?= $totalTransaksi ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-user-slash"></i></div>
      <div>
        <p class="text-secondary small mb-1">Siswa Tanpa Guru</p>
        <h4 class="fw-bold mb-0"><?= $siswaTanpaGuru ?></h4>
      </div>
    </div>
  </div>
</div>

<div class="stat-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold mb-0">Akun Terbaru</h6>
    <a href="/admin/user_form.php" class="btn btn-sm btn-primary rounded-pill px-3">
      <i class="fa-solid fa-plus me-1"></i>Tambah Akun
    </a>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr class="text-secondary small text-uppercase">
          <th>Nama</th>
          <th>Email</th>
          <th>Role</th>
          <th>Kelas</th>
          <th>Terdaftar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($userTerbaru as $u): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($u['nama']) ?></td>
          <td class="text-secondary"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= $u['role'] === 'guru' ? 'bg-orange-subtle text-orange' : 'bg-primary-subtle text-primary' ?> text-capitalize"><?= $u['role'] ?></span></td>
          <td class="text-secondary"><?= htmlspecialchars($u['kelas'] ?: '-') ?></td>
          <td class="text-secondary"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
