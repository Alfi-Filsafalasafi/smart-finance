<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = false;

// Ambil daftar guru untuk dropdown siswa
$daftarGuru = $pdo->query("SELECT id, nama FROM users WHERE role = 'guru' ORDER BY nama ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $nisNip = trim($_POST['nis_nip'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $guruId = $_POST['guru_id'] ?? '';

    if ($nama === '' || $email === '' || $password === '' || !in_array($role, ['siswa', 'guru'])) {
        $error = 'Semua field wajib diisi dengan benar.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($role === 'siswa' && $guruId === '') {
        $error = 'Siswa wajib memilih guru pemantau.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, nis_nip, kelas) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$nama, $email, $hashed, $role, $nisNip ?: null, $role === 'siswa' ? $kelas : null]);
            $newUserId = $pdo->lastInsertId();

            if ($role === 'siswa' && $guruId !== '') {
                $stmt = $pdo->prepare("INSERT INTO guru_siswa (guru_id, siswa_id) VALUES (?,?)");
                $stmt->execute([$guruId, $newUserId]);
            }

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Akun - Smart Finance</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body bg-primary-gradient">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

      <div class="text-center mb-4 text-white">
        <i class="fa-solid fa-wallet fs-1"></i>
        <h3 class="fw-bold mt-2 mb-1">Buat Akun Baru</h3>
        <p class="opacity-75">Daftar sebagai siswa atau guru di Smart Finance</p>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
          <i class="fa-solid fa-circle-check me-2"></i>
          <div>Pendaftaran berhasil! Silakan <a href="login.php" class="fw-semibold">masuk</a> menggunakan akunmu.</div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php else: ?>

        <?php if ($error): ?>
          <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="bg-white p-4 p-md-5 rounded-4 shadow-lg" novalidate>

          <!-- PILIH ROLE -->
          <label class="form-label fw-semibold">Daftar sebagai</label>
          <div class="btn-group w-100 mb-4" role="group">
            <input type="radio" class="btn-check" name="role" id="roleSiswa" value="siswa"
                   <?= (($_POST['role'] ?? 'siswa') === 'siswa') ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary" for="roleSiswa"><i class="fa-solid fa-user-graduate me-1"></i>Siswa</label>

            <input type="radio" class="btn-check" name="role" id="roleGuru" value="guru"
                   <?= (($_POST['role'] ?? '') === 'guru') ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary" for="roleGuru"><i class="fa-solid fa-chalkboard-user me-1"></i>Guru</label>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
            <div class="form-text">Minimal 6 karakter.</div>
          </div>

          <div class="mb-3" id="fieldNisNip">
            <label class="form-label fw-semibold" id="labelNisNip">NIS</label>
            <input type="text" name="nis_nip" class="form-control"
                   value="<?= htmlspecialchars($_POST['nis_nip'] ?? '') ?>">
          </div>

          <!-- FIELD KHUSUS SISWA -->
          <div id="fieldSiswaOnly">
            <div class="mb-3">
              <label class="form-label fw-semibold">Kelas</label>
              <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII RPL 1"
                     value="<?= htmlspecialchars($_POST['kelas'] ?? '') ?>">
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Guru Pemantau</label>
              <select name="guru_id" class="form-select">
                <option value="">-- Pilih Guru --</option>
                <?php foreach ($daftarGuru as $g): ?>
                  <option value="<?= $g['id'] ?>" <?= (($_POST['guru_id'] ?? '') == $g['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['nama']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (empty($daftarGuru)): ?>
                <div class="form-text text-danger">Belum ada guru terdaftar. Guru perlu daftar terlebih dahulu.</div>
              <?php endif; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-pill">
            <i class="fa-solid fa-user-plus me-2"></i>Daftar
          </button>
        </form>

        <p class="text-center text-white mt-4">
          Sudah punya akun? <a href="login.php" class="fw-semibold text-white text-decoration-underline">Masuk</a>
        </p>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const roleSiswa = document.getElementById('roleSiswa');
  const roleGuru = document.getElementById('roleGuru');
  const fieldSiswaOnly = document.getElementById('fieldSiswaOnly');
  const labelNisNip = document.getElementById('labelNisNip');

  function toggleFields() {
    const isSiswa = roleSiswa.checked;
    fieldSiswaOnly.style.display = isSiswa ? 'block' : 'none';
    labelNisNip.textContent = isSiswa ? 'NIS' : 'NIP';
  }
  roleSiswa.addEventListener('change', toggleFields);
  roleGuru.addEventListener('change', toggleFields);
  toggleFields();
</script>
</body>
</html>