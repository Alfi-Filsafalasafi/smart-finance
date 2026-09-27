<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('siswa');

$siswaId = $_SESSION['user_id'];
$pageTitle = 'Transaksi';
$error = '';

$uploadDir = __DIR__ . '/../assets/uploads/bukti/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ==== HAPUS TRANSAKSI ====
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT bukti_file FROM transaksi WHERE id = ? AND siswa_id = ?");
    $stmt->execute([$deleteId, $siswaId]);
    $row = $stmt->fetch();
    if ($row) {
        if ($row['bukti_file'] && file_exists($uploadDir . $row['bukti_file'])) {
            unlink($uploadDir . $row['bukti_file']);
        }
        $pdo->prepare("DELETE FROM transaksi WHERE id = ? AND siswa_id = ?")->execute([$deleteId, $siswaId]);
        setFlash('Transaksi berhasil dihapus.');
    }
    header('Location: transaksi.php');
    exit;
}

// ==== TAMBAH TRANSAKSI ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategoriId = $_POST['kategori_id'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tanggal = $_POST['tanggal_transaksi'] ?? '';

    if ($kategoriId === '' || !in_array($jenis, ['pemasukan', 'pengeluaran']) || $jumlah === '' || $tanggal === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!is_numeric($jumlah) || $jumlah <= 0) {
        $error = 'Jumlah harus berupa angka lebih dari 0.';
    } else {
        $buktiFileName = null;

        if (!empty($_FILES['bukti_file']['name'])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['bukti_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $error = 'Format bukti hanya boleh JPG, PNG, atau PDF.';
            } elseif ($_FILES['bukti_file']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran file bukti maksimal 2MB.';
            } else {
                $buktiFileName = 'bukti_' . $siswaId . '_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['bukti_file']['tmp_name'], $uploadDir . $buktiFileName);
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("
                INSERT INTO transaksi (siswa_id, kategori_id, jenis, jumlah, keterangan, tanggal_transaksi, bukti_file)
                VALUES (?,?,?,?,?,?,?)
            ");
            $stmt->execute([$siswaId, $kategoriId, $jenis, $jumlah, $keterangan ?: null, $tanggal, $buktiFileName]);
            setFlash('Transaksi berhasil ditambahkan.');
            header('Location: transaksi.php');
            exit;
        }
    }
}

// Kategori gabungan (default sistem + custom siswa)
$stmt = $pdo->prepare("SELECT * FROM kategori WHERE siswa_id IS NULL OR siswa_id = ? ORDER BY jenis, nama_kategori");
$stmt->execute([$siswaId]);
$kategoriList = $stmt->fetchAll();

// Semua transaksi siswa
$stmt = $pdo->prepare("
    SELECT t.*, k.nama_kategori
    FROM transaksi t
    JOIN kategori k ON k.id = t.kategori_id
    WHERE t.siswa_id = ?
    ORDER BY t.tanggal_transaksi DESC, t.id DESC
");
$stmt->execute([$siswaId]);
$transaksiList = $stmt->fetchAll();

// Catatan guru untuk transaksi-transaksi di atas, dikelompokkan per transaksi_id
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

<div class="stat-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold mb-0">Riwayat Transaksi</h6>
    <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
      <i class="fa-solid fa-plus me-1"></i>Tambah Transaksi
    </button>
  </div>

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
          <th>Catatan Guru</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transaksiList as $t): ?>
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
            <?php $catatan = $catatanPerTransaksi[$t['id']] ?? []; ?>
            <?php if (!empty($catatan)): ?>
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCatatan<?= $t['id'] ?>">
                <i class="fa-solid fa-comment-dots me-1"></i><?= count($catatan) ?>
              </button>
            <?php else: ?>
              <span class="text-secondary">-</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="transaksi_edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
              <i class="fa-solid fa-pen"></i>
            </a>
            <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill"
               onclick="return confirm('Hapus transaksi ini?');">
              <i class="fa-solid fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL CATATAN GURU (satu per transaksi yang punya catatan) -->
<?php foreach ($transaksiList as $t): ?>
  <?php $catatan = $catatanPerTransaksi[$t['id']] ?? []; ?>
  <?php if (!empty($catatan)): ?>
  <div class="modal fade" id="modalCatatan<?= $t['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Catatan Guru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-secondary small mb-3">
            Transaksi <?= htmlspecialchars($t['nama_kategori']) ?> — <?= date('d M Y', strtotime($t['tanggal_transaksi'])) ?>
          </p>
          <?php foreach ($catatan as $c): ?>
            <div class="border rounded-3 p-3 mb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small"><i class="fa-solid fa-chalkboard-user text-primary me-1"></i><?= htmlspecialchars($c['nama_guru']) ?></span>
                <span class="text-secondary small"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></span>
              </div>
              <p class="mb-0 small"><?= nl2br(htmlspecialchars($c['komentar'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
<?php endforeach; ?>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Tambah Transaksi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <?php if ($error): ?>
            <div class="alert alert-danger small"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Jenis</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="jenis" id="jenisMasuk" value="pemasukan" checked>
              <label class="btn btn-outline-success" for="jenisMasuk"><i class="fa-solid fa-arrow-down me-1"></i>Pemasukan</label>
              <input type="radio" class="btn-check" name="jenis" id="jenisKeluar" value="pengeluaran">
              <label class="btn btn-outline-danger" for="jenisKeluar"><i class="fa-solid fa-arrow-up me-1"></i>Pengeluaran</label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Kategori</label>
            <select name="kategori_id" class="form-select" required>
              <?php foreach ($kategoriList as $k): ?>
                <option value="<?= $k['id'] ?>" data-jenis="<?= $k['jenis'] ?>">
                  <?= htmlspecialchars($k['nama_kategori']) ?><?= $k['siswa_id'] ? ' (Saya)' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Jumlah (Rp)</label>
            <input type="number" name="jumlah" class="form-control" min="1" step="1" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tanggal</label>
            <input type="date" name="tanggal_transaksi" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold">Bukti (opsional)</label>
            <input type="file" name="bukti_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            <div class="form-text">JPG, PNG, atau PDF. Maks 2MB.</div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="fa-solid fa-floppy-disk me-2"></i>Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($error): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('modalTambah')).show();
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(function () {
    $('#tabelTransaksi').DataTable({
      order: [[0, 'desc']],
      columnDefs: [{ orderable: false, targets: [4, 6, 7] }],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_-_END_ dari _TOTAL_ transaksi",
        paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
        zeroRecords: "Belum ada transaksi",
        infoEmpty: "Tidak ada data"
      }
    });

    // Filter kategori sesuai jenis yang dipilih
    function filterKategori() {
      const jenis = $('input[name=jenis]:checked').val();
      $('#modalTambah select[name=kategori_id] option').each(function () {
        $(this).toggle($(this).data('jenis') === jenis);
      });
      $('#modalTambah select[name=kategori_id]').val(
        $('#modalTambah select[name=kategori_id] option[data-jenis="' + jenis + '"]:first').val()
      );
    }
    $('input[name=jenis]').on('change', filterKategori);
    filterKategori();
  });
</script>