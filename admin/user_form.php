<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$editId = $_GET['id'] ?? null;
$editData = null;
$currentGuruId = null;
$error = '';
$success = false;

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();

    if ($editData && $editData['role'] === 'siswa') {
        $stmt = $pdo->prepare("SELECT guru_id FROM guru_siswa WHERE siswa_id = ? LIMIT 1");
        $stmt->execute([$editId]);
        $rel = $stmt->fetch();
        $currentGuruId = $rel['guru_id'] ?? null;
    }
}

$pageTitle = $editData ? 'Edit Akun' : 'Tambah Akun';
$daftarGuru = $pdo->query("SELECT id, nama FROM users WHERE role = 'guru' ORDER BY nama ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $nisNip = trim($_POST['nis_nip'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $guruId = $_POST['guru_id'] ?? '';
    $userId = $_POST['user_id'] ?? null;

    if ($nama === '' || $email === '' || !in_array($role, ['siswa', 'guru'])) {
        $error = 'Nama, email, dan role wajib diisi.';
    } elseif (!$userId && $password === '') {
        $error = 'Password wajib diisi untuk akun baru.';
    } elseif ($role === 'siswa' && $guruId === '') {
        $error = 'Siswa wajib memiliki guru pemantau.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId ?: 0]);
        if ($stmt->fetch()) {
            $error = 'Email sudah digunakan akun lain.';
        } else {
            if ($userId) {
                // UPDATE
                if ($password !== '') {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, password=?, role=?, nis_nip=?, kelas=? WHERE id=?");
                    $stmt->execute([$nama, $email, $hashed, $role, $nisNip ?: null, $role === 'siswa' ? $kelas : null, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, role=?, nis_nip=?, kelas=? WHERE id=?");
                    $stmt->execute([$nama, $email, $role, $nisNip ?: null, $role === 'siswa' ? $kelas : null, $userId]);
                }
                $targetId = $userId;
            } else {
                // INSERT
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, nis_nip, kelas) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$nama, $email, $hashed, $role, $nisNip ?: null, $role === 'siswa' ? $kelas : null]);
                $targetId = $pdo->lastInsertId();
            }

            // Update relasi guru-siswa
            if ($role === 'siswa') {
                $pdo->prepare("DELETE FROM guru_siswa WHERE siswa_id = ?")->execute([$targetId]);
                if ($guruId !== '') {
                    $pdo->prepare("INSERT INTO guru_siswa (guru_id, siswa_id) VALUES (?,?)")->execute([$guruId, $targetId]);
                }
            }

            setFlash($userId ? 'Akun berhasil diperbarui.' : 'Akun baru berhasil ditambahkan.');
            header('Location: users.php?role=' . $role);
            exit;
        }
    }
}

$currentRole = $_POST['role'] ?? ($editData['role'] ?? 'siswa');

include __DIR__ . '/../includes/header.php';
?>

<div class="stat-card" style="max-width: 640px;">
  <h6 class="fw-bold mb-4"><?= $editData ? 'Edit Akun: ' . htmlspecialchars($editData['nama']) : 'Tambah Akun Baru' ?></h6>

  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <input type="hidden" name="user_id" value="<?= $editData['id'] ?? '' ?>">

    <label class="form-label fw-semibold">Daftar sebagai</label>
    <div class="btn-group w-100 mb-4" role="group">
      <input type="radio" class="btn-check" name="role" id="roleSiswa" value="siswa" <?= $currentRole === 'siswa' ? 'checked' : '' ?>>
      <label class="btn btn-outline-primary" for="roleSiswa"><i class="fa-solid fa-user-graduate me-1"></i>Siswa</label>

      <input type="radio" class="btn-check" name="role" id="roleGuru" value="guru" <?= $currentRole === 'guru' ? 'checked' : '' ?>>
      <label class="btn btn-outline-primary" for="roleGuru"><i class="fa-solid fa-chalkboard-user me-1"></i>Guru</label>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Nama Lengkap</label>
      <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($editData['nama'] ?? $_POST['nama'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Email</label>
      <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editData['email'] ?? $_POST['email'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Password <?= $editData ? '(kosongkan jika tidak diubah)' : '' ?></label>
      <input type="password" name="password" class="form-control" minlength="6" <?= $editData ? '' : 'required' ?>>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold" id="labelNisNip"><?= $currentRole === 'guru' ? 'NIP' : 'NIS' ?></label>
      <input type="text" name="nis_nip" class="form-control" value="<?= htmlspecialchars($editData['nis_nip'] ?? $_POST['nis_nip'] ?? '') ?>">
    </div>

    <div id="fieldSiswaOnly">
      <div class="mb-3">
        <label class="form-label fw-semibold">Kelas</label>
        <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($editData['kelas'] ?? $_POST['kelas'] ?? '') ?>">
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">Guru Pemantau</label>
        <select name="guru_id" class="form-select">
          <option value="">-- Pilih Guru --</option>
          <?php foreach ($daftarGuru as $g): ?>
            <option value="<?= $g['id'] ?>" <?= ($currentGuruId == $g['id']) ? 'selected' : '' ?>><?= htmlspecialchars($g['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
        <i class="fa-solid fa-floppy-disk me-2"></i><?= $editData ? 'Simpan Perubahan' : 'Simpan Akun' ?>
      </button>
      <a href="users.php" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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