<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('guru');

$guruId = $_SESSION['user_id'];
$pageTitle = 'Dashboard';

// Jumlah siswa yang dipantau
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM guru_siswa WHERE guru_id = ?");
$stmt->execute([$guruId]);
$totalSiswaPantauan = $stmt->fetch()['total'];

// Total transaksi minggu ini dari semua siswa pantauan
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM transaksi t
    JOIN guru_siswa gs ON gs.siswa_id = t.siswa_id
    WHERE gs.guru_id = ? AND YEARWEEK(t.tanggal_transaksi, 1) = YEARWEEK(CURDATE(), 1)
");
$stmt->execute([$guruId]);
$transaksiMingguIni = $stmt->fetch()['total'];

// Siswa dengan saldo negatif (perlu perhatian)
$stmt = $pdo->prepare("
    SELECT u.id, u.nama, u.kelas,
        COALESCE(SUM(CASE WHEN t.jenis='pemasukan' THEN t.jumlah ELSE 0 END),0)
        - COALESCE(SUM(CASE WHEN t.jenis='pengeluaran' THEN t.jumlah ELSE 0 END),0) AS saldo
    FROM guru_siswa gs
    JOIN users u ON u.id = gs.siswa_id
    LEFT JOIN transaksi t ON t.siswa_id = u.id
    WHERE gs.guru_id = ?
    GROUP BY u.id, u.nama, u.kelas
    HAVING saldo < 0
    ORDER BY saldo ASC
    LIMIT 5
");
$stmt->execute([$guruId]);
$siswaPerluPerhatian = $stmt->fetchAll();

// Aktivitas transaksi terbaru dari siswa pantauan
$stmt = $pdo->prepare("
    SELECT t.*, u.nama AS nama_siswa, k.nama_kategori
    FROM transaksi t
    JOIN guru_siswa gs ON gs.siswa_id = t.siswa_id
    JOIN users u ON u.id = t.siswa_id
    JOIN kategori k ON k.id = t.kategori_id
    WHERE gs.guru_id = ?
    ORDER BY t.created_at DESC
    LIMIT 6
");
$stmt->execute([$guruId]);
$aktivitasTerbaru = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- STAT CARDS -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-primary-subtle text-primary">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <div>
        <p class="text-secondary small mb-1">Siswa Dipantau</p>
        <h4 class="fw-bold mb-0"><?= $totalSiswaPantauan ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-orange-subtle text-orange">
        <i class="fa-solid fa-calendar-week"></i>
      </div>
      <div>
        <p class="text-secondary small mb-1">Transaksi Minggu Ini</p>
        <h4 class="fw-bold mb-0"><?= $transaksiMingguIni ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-danger-subtle text-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <div>
        <p class="text-secondary small mb-1">Saldo Negatif</p>
        <h4 class="fw-bold mb-0"><?= count($siswaPerluPerhatian) ?> siswa</h4>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- SISWA PERLU PERHATIAN -->
  <div class="col-lg-5">
    <div class="stat-card h-100">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Perlu Perhatian</h6>
      <?php if (empty($siswaPerluPerhatian)): ?>
        <p class="text-secondary small mb-0">Semua siswa pantauan memiliki saldo aman <i class="fa-solid fa-thumbs-up text-success"></i></p>
      <?php else: ?>
        <?php foreach ($siswaPerluPerhatian as $s): ?>
          <a href="/guru/siswa_detail.php?id=<?= $s['id'] ?>" class="d-flex align-items-center justify-content-between py-2 text-decoration-none border-bottom">
            <div>
              <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($s['nama']) ?></p>
              <p class="mb-0 small text-secondary"><?= htmlspecialchars($s['kelas'] ?: '-') ?></p>
            </div>
            <span class="fw-semibold text-danger">Rp <?= number_format($s['saldo'], 0, ',', '.') ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- AKTIVITAS TERBARU -->
  <div class="col-lg-7">
    <div class="stat-card h-100">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Aktivitas Terbaru</h6>
      <?php if (empty($aktivitasTerbaru)): ?>
        <p class="text-secondary small mb-0">Belum ada aktivitas transaksi dari siswa pantauan.</p>
      <?php else: ?>
        <?php foreach ($aktivitasTerbaru as $a): ?>
          <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
            <div class="d-flex align-items-center gap-3">
              <div class="stat-icon <?= $a['jenis'] === 'pemasukan' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width:40px;height:40px;font-size:1rem;">
                <i class="fa-solid <?= $a['jenis'] === 'pemasukan' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
              </div>
              <div>
                <p class="mb-0 fw-semibold"><?= htmlspecialchars($a['nama_siswa']) ?></p>
                <p class="mb-0 small text-secondary"><?= htmlspecialchars($a['nama_kategori']) ?> · <?= date('d M', strtotime($a['tanggal_transaksi'])) ?></p>
              </div>
            </div>
            <span class="fw-semibold <?= $a['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
              <?= $a['jenis'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($a['jumlah'], 0, ',', '.') ?>
            </span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
