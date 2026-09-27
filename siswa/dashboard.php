<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('siswa');

$siswaId = $_SESSION['user_id'];
$pageTitle = 'Dashboard';

// Total pemasukan
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) AS total FROM transaksi WHERE siswa_id = ? AND jenis = 'pemasukan'");
$stmt->execute([$siswaId]);
$totalPemasukan = $stmt->fetch()['total'];

// Total pengeluaran
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) AS total FROM transaksi WHERE siswa_id = ? AND jenis = 'pengeluaran'");
$stmt->execute([$siswaId]);
$totalPengeluaran = $stmt->fetch()['total'];

$saldo = $totalPemasukan - $totalPengeluaran;

// Transaksi terbaru
$stmt = $pdo->prepare("
    SELECT t.*, k.nama_kategori
    FROM transaksi t
    JOIN kategori k ON k.id = t.kategori_id
    WHERE t.siswa_id = ?
    ORDER BY t.tanggal_transaksi DESC, t.id DESC
    LIMIT 5
");
$stmt->execute([$siswaId]);
$transaksiTerbaru = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- STAT CARDS -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-primary-subtle text-primary">
        <i class="fa-solid fa-scale-balanced"></i>
      </div>
      <div>
        <p class="text-secondary small mb-1">Saldo Saat Ini</p>
        <h4 class="fw-bold mb-0">Rp <?= number_format($saldo, 0, ',', '.') ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-success-subtle text-success">
        <i class="fa-solid fa-arrow-trend-up"></i>
      </div>
      <div>
        <p class="text-secondary small mb-1">Total Pemasukan</p>
        <h4 class="fw-bold mb-0">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-orange-subtle text-orange">
        <i class="fa-solid fa-arrow-trend-down"></i>
      </div>
      <div>
        <p class="text-secondary small mb-1">Total Pengeluaran</p>
        <h4 class="fw-bold mb-0">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></h4>
      </div>
    </div>
  </div>
</div>

<!-- TRANSAKSI TERBARU -->
<div class="stat-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold mb-0">Transaksi Terbaru</h6>
    <a href="/siswa/transaksi.php" class="btn btn-sm btn-primary rounded-pill px-3">
      <i class="fa-solid fa-plus me-1"></i>Tambah
    </a>
  </div>

  <?php if (empty($transaksiTerbaru)): ?>
    <div class="text-center text-secondary py-5">
      <i class="fa-solid fa-inbox fs-1 mb-3 d-block opacity-50"></i>
      Belum ada transaksi. Yuk mulai catat keuanganmu!
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr class="text-secondary small text-uppercase">
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Keterangan</th>
            <th>Bukti</th>
            <th class="text-end">Jumlah</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transaksiTerbaru as $t): ?>
          <tr>
            <td><?= date('d M Y', strtotime($t['tanggal_transaksi'])) ?></td>
            <td><?= htmlspecialchars($t['nama_kategori']) ?></td>
            <td class="text-secondary"><?= htmlspecialchars($t['keterangan'] ?: '-') ?></td>
            <td>
              <?php if ($t['bukti_file']): ?>
                <a href="/assets/uploads/bukti/<?= htmlspecialchars($t['bukti_file']) ?>" target="_blank" class="text-primary">
                  <i class="fa-solid fa-paperclip"></i>
                </a>
              <?php else: ?>
                <span class="text-secondary">-</span>
              <?php endif; ?>
            </td>
            <td class="text-end fw-semibold <?= $t['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
              <?= $t['jenis'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($t['jumlah'], 0, ',', '.') ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
