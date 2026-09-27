<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('guru');

$guruId = $_SESSION['user_id'];
$siswaId = $_GET['id'] ?? null;

// Pastikan siswa ini memang dipantau oleh guru yang login
$stmt = $pdo->prepare("
    SELECT u.* FROM guru_siswa gs
    JOIN users u ON u.id = gs.siswa_id
    WHERE gs.guru_id = ? AND gs.siswa_id = ?
");
$stmt->execute([$guruId, $siswaId]);
$siswa = $stmt->fetch();

if (!$siswa) {
    header('Location: siswa_list.php');
    exit;
}

$pageTitle = 'Detail Siswa';
$error = '';

// Tambah catatan guru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaksiId = $_POST['transaksi_id'] ?? '';
    $komentar = trim($_POST['komentar'] ?? '');

    // Pastikan transaksi memang milik siswa ini
    $cek = $pdo->prepare("SELECT id FROM transaksi WHERE id = ? AND siswa_id = ?");
    $cek->execute([$transaksiId, $siswaId]);

    if ($komentar === '') {
        $error = 'Catatan tidak boleh kosong.';
    } elseif (!$cek->fetch()) {
        $error = 'Transaksi tidak valid.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO catatan_guru (transaksi_id, guru_id, komentar) VALUES (?,?,?)");
        $stmt->execute([$transaksiId, $guruId, $komentar]);
        setFlash('Catatan berhasil ditambahkan.');
        header('Location: siswa_detail.php?id=' . $siswaId);
        exit;
    }
}

// Ringkasan saldo
$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) AS total FROM transaksi WHERE siswa_id=? AND jenis='pemasukan'");
$stmt->execute([$siswaId]);
$totalPemasukan = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) AS total FROM transaksi WHERE siswa_id=? AND jenis='pengeluaran'");
$stmt->execute([$siswaId]);
$totalPengeluaran = $stmt->fetch()['total'];

$saldo = $totalPemasukan - $totalPengeluaran;

// Transaksi siswa
$stmt = $pdo->prepare("
    SELECT t.*, k.nama_kategori
    FROM transaksi t
    JOIN kategori k ON k.id = t.kategori_id
    WHERE t.siswa_id = ?
    ORDER BY t.tanggal_transaksi DESC, t.id DESC
");
$stmt->execute([$siswaId]);
$transaksiList = $stmt->fetchAll();

// Catatan guru per transaksi
$catatanPerTransaksi = [];
if (!empty($transaksiList)) {
    $ids = array_column($transaksiList, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT cg.*, u.nama AS nama_guru
        FROM catatan_guru cg
        JOIN users u ON u.id = cg.guru_id
        WHERE cg.transaksi_id IN ($placeholders)
        ORDER BY cg.created_at ASC
    ");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $c) {
        $catatanPerTransaksi[$c['transaksi_id']][] = $c;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/css/dataTables.bootstrap5.min.css">

<!-- INFO SISWA -->
<div class="stat-card mb-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h5 class="fw-bold mb-1"><?= htmlspecialchars($siswa['nama']) ?></h5>
      <p class="text-secondary mb-0 small">
        <?= htmlspecialchars($siswa['kelas'] ?: '-') ?> · NIS <?= htmlspecialchars($siswa['nis_nip'] ?: '-') ?> · <?= htmlspecialchars($siswa['email']) ?>
      </p>
    </div>
    <a href="siswa_list.php" class="btn btn-outline-secondary rounded-pill px-3">
      <i class="fa-solid fa-arrow-left me-1"></i>Kembali
    </a>
  </div>
</div>

<!-- STAT CARDS -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-scale-balanced"></i></div>
      <div>
        <p class="text-secondary small mb-1">Saldo Saat Ini</p>
        <h4 class="fw-bold mb-0 <?= $saldo < 0 ? 'text-danger' : '' ?>">Rp <?= number_format($saldo, 0, ',', '.') ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-trend-up"></i></div>
      <div>
        <p class="text-secondary small mb-1">Total Pemasukan</p>
        <h4 class="fw-bold mb-0">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="stat-icon bg-orange-subtle text-orange"><i class="fa-solid fa-arrow-trend-down"></i></div>
      <div>
        <p class="text-secondary small mb-1">Total Pengeluaran</p>
        <h4 class="fw-bold mb-0">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></h4>
      </div>
    </div>
  </div>
</div>

<!-- TABEL TRANSAKSI -->
<div class="stat-card">
  <h6 class="fw-bold mb-3">Riwayat Transaksi</h6>

  <div class="table-responsive">
    <table id="tabelTransaksi" class="table align-middle w-100">
      <thead>
        <tr class="text-secondary small text-uppercase">
          <th>Tanggal</th>
          <th>Kategori</th>
          <th>Jenis</th>
          <th>Keterangan</th>
          <th>Bukti</th>
          <th>Jumlah</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transaksiList)): ?>
          <tr><td colspan="7" class="text-center text-secondary py-4">Siswa ini belum punya transaksi.</td></tr>
        <?php endif; ?>
        <?php foreach ($transaksiList as $t): ?>
          <?php $catatan = $catatanPerTransaksi[$t['id']] ?? []; ?>
          <tr>
            <td data-order="<?= $t['tanggal_transaksi'] ?>"><?= date('d M Y', strtotime($t['tanggal_transaksi'])) ?></td>
            <td><?= htmlspecialchars($t['nama_kategori']) ?></td>
            <td>
              <span class="badge <?= $t['jenis'] === 'pemasukan' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> text-capitalize">
                <?= $t['jenis'] ?>
              </span>
            </td>
            <td class="text-secondary"><?= htmlspecialchars($t['keterangan'] ?: '-') ?></td>
            <td>
              <?php if ($t['bukti_file']): ?>
                <a href="/assets/uploads/bukti/<?= htmlspecialchars($t['bukti_file']) ?>" target="_blank" class="text-primary">
                  <i class="fa-solid fa-paperclip"></i> Lihat
                </a>
              <?php else: ?>
                <span class="text-secondary">-</span>
              <?php endif; ?>
            </td>
            <td data-order="<?= $t['jumlah'] ?>" class="fw-semibold <?= $t['jenis'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
              <?= $t['jenis'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($t['jumlah'], 0, ',', '.') ?>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCatatan<?= $t['id'] ?>">
                <i class="fa-solid fa-comment-dots me-1"></i><?= count($catatan) ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL CATATAN PER TRANSAKSI -->
<?php foreach ($transaksiList as $t): ?>
  <?php $catatan = $catatanPerTransaksi[$t['id']] ?? []; ?>
  <div class="modal fade" id="modalCatatan<?= $t['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Catatan — <?= htmlspecialchars($t['nama_kategori']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-secondary small mb-3">
            <?= date('d M Y', strtotime($t['tanggal_transaksi'])) ?> ·
            <?= $t['jenis'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($t['jumlah'], 0, ',', '.') ?>
          </p>

          <?php if (empty($catatan)): ?>
            <p class="text-secondary small">Belum ada catatan untuk transaksi ini.</p>
          <?php else: ?>
            <?php foreach ($catatan as $c): ?>
              <div class="border rounded-3 p-3 mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold small"><i class="fa-solid fa-chalkboard-user text-primary me-1"></i><?= htmlspecialchars($c['nama_guru']) ?></span>
                  <span class="text-secondary small"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></span>
                </div>
                <p class="mb-0 small"><?= nl2br(htmlspecialchars($c['komentar'])) ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <hr>

          <form method="POST">
            <input type="hidden" name="transaksi_id" value="<?= $t['id'] ?>">
            <?php if ($error): ?>
              <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <label class="form-label fw-semibold small">Tambah Catatan</label>
            <textarea name="komentar" class="form-control mb-2" rows="2" placeholder="Tulis catatan untuk siswa..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
              <i class="fa-solid fa-paper-plane me-1"></i>Kirim
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(function () {
    $('#tabelTransaksi').DataTable({
      order: [[0, 'desc']],
      columnDefs: [{ orderable: false, targets: [4, 6] }],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_-_END_ dari _TOTAL_ transaksi",
        paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
        zeroRecords: "Transaksi tidak ditemukan",
        infoEmpty: "Tidak ada data"
      }
    });
  });
</script>
