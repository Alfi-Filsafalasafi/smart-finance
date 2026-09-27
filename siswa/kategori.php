<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('siswa');

$siswaId = $_SESSION['user_id'];
$pageTitle = 'Kategori';
$error = '';

// Hapus kategori custom milik sendiri
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $used = $pdo->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE kategori_id = ?");
    $used->execute([$deleteId]);
    if ($used->fetch()['total'] > 0) {
        $error = 'Kategori tidak bisa dihapus karena sudah dipakai di transaksi.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ? AND siswa_id = ?");
        $stmt->execute([$deleteId, $siswaId]);
        setFlash('Kategori berhasil dihapus.');
        header('Location: kategori.php');
        exit;
    }
}

// Tambah / edit kategori custom
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $jenis = $_POST['jenis'] ?? '';
    $id = $_POST['id'] ?? null;

    if ($nama === '' || !in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        $error = 'Nama kategori dan jenis wajib diisi.';
    } elseif ($id) {
        $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori=?, jenis=? WHERE id=? AND siswa_id=?");
        $stmt->execute([$nama, $jenis, $id, $siswaId]);
        setFlash('Kategori berhasil diperbarui.');
        header('Location: kategori.php');
        exit;
    } else {
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, jenis, siswa_id) VALUES (?,?,?)");
        $stmt->execute([$nama, $jenis, $siswaId]);
        setFlash('Kategori baru berhasil ditambahkan.');
        header('Location: kategori.php');
        exit;
    }
}

$editId = $_GET['edit'] ?? null;
$editData = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id = ? AND siswa_id = ?");
    $stmt->execute([$editId, $siswaId]);
    $editData = $stmt->fetch();
}

$kategoriSaya = $pdo->prepare("SELECT * FROM kategori WHERE siswa_id = ? ORDER BY jenis, nama_kategori");
$kategoriSaya->execute([$siswaId]);
$kategoriSaya = $kategoriSaya->fetchAll();

$kategoriDefault = $pdo->query("SELECT * FROM kategori WHERE siswa_id IS NULL ORDER BY jenis, nama_kategori")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <!-- FORM -->
  <div class="col-lg-4">
    <div class="stat-card">
      <h6 class="fw-bold mb-3"><?= $editData ? 'Edit Kategori' : 'Tambah Kategori Saya' ?></h6>

      <?php if ($error): ?>
        <div class="alert alert-danger small"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

        <div class="mb-3">
          <label class="form-label fw-semibold">Nama Kategori</label>
          <input type="text" name="nama_kategori" class="form-control" required
                 value="<?= htmlspecialchars($editData['nama_kategori'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Jenis</label>
          <select name="jenis" class="form-select" required>
            <option value="pemasukan" <?= (($editData['jenis'] ?? '') === 'pemasukan') ? 'selected' : '' ?>>Pemasukan</option>
            <option value="pengeluaran" <?= (($editData['jenis'] ?? '') === 'pengeluaran') ? 'selected' : '' ?>>Pengeluaran</option>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fa-solid fa-floppy-disk me-2"></i><?= $editData ? 'Simpan' : 'Tambah' ?>
          </button>
          <?php if ($editData): ?>
            <a href="kategori.php" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- LIST -->
  <div class="col-lg-8">

    <!-- KATEGORI SAYA -->
    <div class="stat-card mb-4">
      <h6 class="fw-bold mb-3">Kategori Saya</h6>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr class="text-secondary small text-uppercase">
              <th>Nama Kategori</th>
              <th>Jenis</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($kategoriSaya)): ?>
              <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada kategori custom.</td></tr>
            <?php endif; ?>
            <?php foreach ($kategoriSaya as $k): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($k['nama_kategori']) ?></td>
              <td>
                <span class="badge <?= $k['jenis'] === 'pemasukan' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> text-capitalize">
                  <?= $k['jenis'] ?>
                </span>
              </td>
              <td class="text-end">
                <a href="?edit=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                  <i class="fa-solid fa-pen"></i>
                </a>
                <a href="?delete=<?= $k['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill"
                   onclick="return confirm('Hapus kategori <?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>?');">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- KATEGORI DEFAULT (READ ONLY) -->
    <div class="stat-card">
      <h6 class="fw-bold mb-3">Kategori Default Sistem</h6>
      <p class="text-secondary small mb-3">Kategori ini diatur oleh admin dan berlaku untuk semua siswa.</p>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($kategoriDefault as $k): ?>
          <span class="badge <?= $k['jenis'] === 'pemasukan' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-3 py-2">
            <?= htmlspecialchars($k['nama_kategori']) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>