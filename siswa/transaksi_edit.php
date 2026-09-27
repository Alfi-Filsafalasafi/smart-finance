<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('siswa');

$siswaId = $_SESSION['user_id'];
$pageTitle = 'Edit Transaksi';
$error = '';

$uploadDir = __DIR__ . '/../assets/uploads/bukti/';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ? AND siswa_id = ?");
$stmt->execute([$id, $siswaId]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    header('Location: transaksi.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategoriId = $_POST['kategori_id'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $jumlah = $_POST['jumlah'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tanggal = $_POST['tanggal_transaksi'] ?? '';
    $hapusBukti = isset($_POST['hapus_bukti']);

    if ($kategoriId === '' || !in_array($jenis, ['pemasukan', 'pengeluaran']) || $jumlah === '' || $tanggal === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!is_numeric($jumlah) || $jumlah <= 0) {
        $error = 'Jumlah harus berupa angka lebih dari 0.';
    } else {
        $buktiFileName = $transaksi['bukti_file'];

        // Hapus bukti lama jika dicentang
        if ($hapusBukti && $buktiFileName) {
            if (file_exists($uploadDir . $buktiFileName)) unlink($uploadDir . $buktiFileName);
            $buktiFileName = null;
        }

        // Upload bukti baru (replace)
        if (!empty($_FILES['bukti_file']['name'])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['bukti_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $error = 'Format bukti hanya boleh JPG, PNG, atau PDF.';
            } elseif ($_FILES['bukti_file']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran file bukti maksimal 2MB.';
            } else {
                if ($buktiFileName && file_exists($uploadDir . $buktiFileName)) {
                    unlink($uploadDir . $buktiFileName);
                }
                $buktiFileName = 'bukti_' . $siswaId . '_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['bukti_file']['tmp_name'], $uploadDir . $buktiFileName);
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("
                UPDATE transaksi
                SET kategori_id=?, jenis=?, jumlah=?, keterangan=?, tanggal_transaksi=?, bukti_file=?
                WHERE id=? AND siswa_id=?
            ");
            $stmt->execute([$kategoriId, $jenis, $jumlah, $keterangan ?: null, $tanggal, $buktiFileName, $id, $siswaId]);
            setFlash('Transaksi berhasil diperbarui.');
            header('Location: transaksi.php');
            exit;
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM kategori WHERE siswa_id IS NULL OR siswa_id = ? ORDER BY jenis, nama_kategori");
$stmt->execute([$siswaId]);
$kategoriList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="stat-card" style="max-width: 640px;">
  <h6 class="fw-bold mb-4">Edit Transaksi</h6>

  <?php if ($error): ?>
    <div class="alert alert-danger small"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <div class="mb-3">
      <label class="form-label fw-semibold">Jenis</label>
      <div class="btn-group w-100" role="group">
        <input type="radio" class="btn-check" name="jenis" id="jenisMasuk" value="pemasukan" <?= $transaksi['jenis'] === 'pemasukan' ? 'checked' : '' ?>>
        <label class="btn btn-outline-success" for="jenisMasuk"><i class="fa-solid fa-arrow-down me-1"></i>Pemasukan</label>
        <input type="radio" class="btn-check" name="jenis" id="jenisKeluar" value="pengeluaran" <?= $transaksi['jenis'] === 'pengeluaran' ? 'checked' : '' ?>>
        <label class="btn btn-outline-danger" for="jenisKeluar"><i class="fa-solid fa-arrow-up me-1"></i>Pengeluaran</label>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Kategori</label>
      <select name="kategori_id" class="form-select" required>
        <?php foreach ($kategoriList as $k): ?>
          <option value="<?= $k['id'] ?>" data-jenis="<?= $k['jenis'] ?>" <?= $k['id'] == $transaksi['kategori_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['nama_kategori']) ?><?= $k['siswa_id'] ? ' (Saya)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Jumlah (Rp)</label>
      <input type="number" name="jumlah" class="form-control" min="1" step="1" required value="<?= htmlspecialchars($transaksi['jumlah']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Tanggal</label>
      <input type="date" name="tanggal_transaksi" class="form-control" required value="<?= htmlspecialchars($transaksi['tanggal_transaksi']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Keterangan</label>
      <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($transaksi['keterangan'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Bukti Saat Ini</label>
      <?php if ($transaksi['bukti_file']): ?>
        <div class="d-flex align-items-center gap-3 mb-2">
          <a href="/assets/uploads/bukti/<?= htmlspecialchars($transaksi['bukti_file']) ?>" target="_blank" class="text-primary">
            <i class="fa-solid fa-paperclip me-1"></i>Lihat bukti
          </a>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" name="hapus_bukti" id="hapusBukti">
            <label class="form-check-label small text-danger" for="hapusBukti">Hapus bukti ini</label>
          </div>
        </div>
      <?php else: ?>
        <p class="text-secondary small mb-2">Belum ada bukti.</p>
      <?php endif; ?>

      <label class="form-label fw-semibold">Ganti / Upload Bukti Baru</label>
      <input type="file" name="bukti_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
      <div class="form-text">JPG, PNG, atau PDF. Maks 2MB. Kosongkan jika tidak diubah.</div>
    </div>

    <div class="d-flex gap-2 mt-4">
      <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan
      </button>
      <a href="transaksi.php" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  function filterKategori() {
    const jenis = document.querySelector('input[name=jenis]:checked').value;
    document.querySelectorAll('select[name=kategori_id] option').forEach(opt => {
      opt.hidden = opt.dataset.jenis !== jenis;
    });
  }
  document.querySelectorAll('input[name=jenis]').forEach(r => r.addEventListener('change', filterKategori));
  filterKategori();
</script>
