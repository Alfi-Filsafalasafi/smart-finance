<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['foto'] = $user['foto'];

            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
            } elseif ($user['role'] === 'guru') {
                header('Location: /guru/dashboard.php');
            } else {
                header('Location: /siswa/dashboard.php');
            }
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Smart Finance</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">

<div class="container-fluid vh-100">
  <div class="row h-100">

    <!-- SISI KIRI: BRANDING -->
    <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center align-items-center bg-primary-gradient text-white p-5">
      <i class="fa-solid fa-wallet display-1 mb-4"></i>
      <h2 class="fw-bold mb-3 text-center">Smart Finance</h2>
      <p class="text-center opacity-75 col-9">
        Catat keuanganmu, biarkan gurumu memantau progresmu dengan mudah dan transparan.
      </p>
    </div>

    <!-- SISI KANAN: FORM LOGIN -->
    <div class="col-lg-6 d-flex align-items-center justify-content-center">
      <div class="w-100 px-4" style="max-width: 420px;">

        <div class="text-center mb-4 d-lg-none">
          <i class="fa-solid fa-wallet fs-1 text-primary"></i>
        </div>

        <h3 class="fw-bold mb-1">Selamat Datang <i class="fa-solid fa-hand-peace text-primary"></i></h3>
        <p class="text-secondary mb-4">Masuk ke akun Smart Finance kamu</p>

        <?php if ($error): ?>
          <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="fa-solid fa-envelope text-secondary"></i></span>
              <input type="email" name="email" class="form-control" placeholder="nama@sekolah.sch.id"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-secondary"></i></span>
              <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
              <span class="input-group-text bg-white" role="button" id="togglePassword">
                <i class="fa-solid fa-eye text-secondary"></i>
              </span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-pill mt-2">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
          </button>
        </form>

        <p class="text-center text-secondary mt-4 mb-1">
          Belum punya akun? <a href="register.php" class="fw-semibold">Daftar di sini</a>
        </p>
        <p class="text-center text-secondary mb-0">
          <a href="../index.php"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke Beranda</a>
        </p>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('passwordInput');
    const icon = this.querySelector('i');
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
  });
</script>
</body>
</html>
