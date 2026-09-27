<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('guru');

$guruId = $_SESSION['user_id'];
$pageTitle = 'Profil';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nisNip = trim($_POST['nis_nip'] ?? '');
    $passwordBaru = $_POST['password_baru'] ?? '';
    $passwordKonfirmasi = $_POST['password_konfirmasi'] ?? '';

    if ($nama === '' || $email === '') {
        $error = 'Nama dan email wajib diisi.';
    } elseif ($passwordBaru !== '' && strlen($passwordBaru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($passwordBaru !== $passwordKonfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $guruId]);
        if ($stmt->fetch()) {
            $error = 'Email sudah digunakan akun lain.';
        } else {
            if ($passwordBaru !== '') {
                $hashed = password_hash($passwordBaru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, nis_nip=?, password=? WHERE id=?");
                $stmt->execute([$nama, $email, $nisNip ?: null, $hashed, $guruId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, nis_nip=? WHERE id=?");
                $stmt->execute([$nama, $email, $nisNip ?: null, $guruId]);
            }
            $_SESSION['nama'] = $nama;
            setFlash('Profil berhasil diperbarui.');
            header('Location: profil.php');
            exit;
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$guruId]);
$guru = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT u.nama, u.kelas FROM guru_siswa gs
    JOIN users u ON u.id = gs.siswa_id
    WHERE gs.guru_id = ?
    ORDER BY u.nama ASC
");
$stmt->execute([$guruId]);
$siswaPantauan = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
  <!-- FORM PROFIL -->
  <div class="col-lg-7">
    <div class="stat-card">
      <h6 class="fw-bold mb-4">Data Diri</h6>

      <?php if ($error): ?>
        <div class="alert alert-danger small"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($guru['nama']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($guru['email']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">NIP</label>
          <input type="text" name="nis_nip" class="form-control" value="<?= htmlspecialchars($guru['nis_nip'] ?? '') ?>">
        </div>

        <hr class="my-4">

        <h6 class="fw-bold mb-3">Ganti Password</h6>
        <p class="text-secondary small mb-3">Kosongkan jika tidak ingin mengubah password.</p>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Password Baru</label>
            <input type="password" name="password_baru" class="form-control" minlength="6">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Konfirmasi Password</label>
            <input type="password" name="password_konfirmasi" class="form-control" minlength="6">
          </div>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold mt-2">
          <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan
        </button>
      </form>
    </div>
  </div>

  <!-- INFO SAMPING -->
  <div class="col-lg-5">
    <div class="stat-card mb-4 text-center">
      <div class="user-avatar mx-auto mb-3" style="width: 72px; height: 72px; font-size: 1.8rem;">
        <i class="fa-solid fa-user"></i>
      </div>
      <h6 class="fw-bold mb-0"><?= htmlspecialchars($guru['nama']) ?></h6>
      <p class="text-secondary small mb-0"><?= htmlspecialchars($guru['email']) ?></p>
      <span class="badge bg-orange-subtle text-orange rounded-pill mt-2">Guru</span>
    </div>

    <div class="stat-card">
      <h6 class="fw-bold mb-3"><i class="fa-solid fa-user-graduate text-primary me-2"></i>Siswa Pantauan (<?= count($siswaPantauan) ?>)</h6>
      <?php if (empty($siswaPantauan)): ?>
        <p class="text-secondary small mb-0">Belum ada siswa yang memilihmu sebagai guru pemantau.</p>
      <?php else: ?>
        <?php foreach ($siswaPantauan as $s): ?>
          <div class="d-flex align-items-center gap-2 py-2 border-bottom">
            <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
              <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div>
              <p class="mb-0 fw-semibold small"><?= htmlspecialchars($s['nama']) ?></p>
              <p class="mb-0 text-secondary" style="font-size: 0.8rem;"><?= htmlspecialchars($s['kelas'] ?: '-') ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>