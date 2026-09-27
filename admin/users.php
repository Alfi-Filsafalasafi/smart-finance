<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$roleFilter = $_GET['role'] ?? 'siswa';
if (!in_array($roleFilter, ['siswa', 'guru'])) $roleFilter = 'siswa';

$pageTitle = $roleFilter === 'siswa' ? 'Data Siswa' : 'Data Guru';

// Hapus akun
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$deleteId]);
    setFlash('Akun berhasil dihapus.');
    header('Location: users.php?role=' . $roleFilter);
    exit;
}

if ($roleFilter === 'siswa') {
    $data = $pdo->query("
        SELECT u.*, g.nama AS nama_guru
        FROM users u
        LEFT JOIN guru_siswa gs ON gs.siswa_id = u.id
        LEFT JOIN users g ON g.id = gs.guru_id
        WHERE u.role = 'siswa'
        ORDER BY u.nama ASC
    ")->fetchAll();
} else {
    $data = $pdo->query("
        SELECT u.*, (SELECT COUNT(*) FROM guru_siswa gs WHERE gs.guru_id = u.id) AS jumlah_siswa
        FROM users u
        WHERE u.role = 'guru'
        ORDER BY u.nama ASC
    ")->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/css/dataTables.bootstrap5.min.css">

<div class="stat-card">

  <!-- TAB FILTER -->
  <ul class="nav nav-pills mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $roleFilter === 'siswa' ? 'active' : '' ?>" href="?role=siswa">
        <i class="fa-solid fa-user-graduate me-1"></i> Siswa
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $roleFilter === 'guru' ? 'active' : '' ?>" href="?role=guru">
        <i class="fa-solid fa-chalkboard-user me-1"></i> Guru
      </a>
    </li>
  </ul>

  <div class="table-responsive">
    <table id="tabelUsers" class="table align-middle w-100">
      <thead>
        <tr class="text-secondary small text-uppercase">
          <th>Nama</th>
          <th>Email</th>
          <th><?= $roleFilter === 'siswa' ? 'NIS' : 'NIP' ?></th>
          <?php if ($roleFilter === 'siswa'): ?>
            <th>Kelas</th>
            <th>Guru Pemantau</th>
          <?php else: ?>
            <th>Jumlah Siswa</th>
          <?php endif; ?>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada data.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $u): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($u['nama']) ?></td>
          <td class="text-secondary"><?= htmlspecialchars($u['email']) ?></td>
          <td class="text-secondary"><?= htmlspecialchars($u['nis_nip'] ?: '-') ?></td>
          <?php if ($roleFilter === 'siswa'): ?>
            <td class="text-secondary"><?= htmlspecialchars($u['kelas'] ?: '-') ?></td>
            <td>
              <?php if ($u['nama_guru']): ?>
                <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($u['nama_guru']) ?></span>
              <?php else: ?>
                <span class="badge bg-danger-subtle text-danger">Belum ada guru</span>
              <?php endif; ?>
            </td>
          <?php else: ?>
            <td class="text-secondary"><?= $u['jumlah_siswa'] ?> siswa</td>
          <?php endif; ?>
          <td class="text-end">
            <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
              <i class="fa-solid fa-pen"></i>
            </a>
            <a href="?role=<?= $roleFilter ?>&delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill"
               onclick="return confirm('Yakin ingin menghapus akun <?= htmlspecialchars(addslashes($u['nama'])) ?>?');">
              <i class="fa-solid fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(function () {
    $('#tabelUsers').DataTable({
      order: [],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_-_END_ dari _TOTAL_ data",
        paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
        zeroRecords: "Data tidak ditemukan",
        infoEmpty: "Tidak ada data"
      },
      columnDefs: [{ orderable: false, targets: -1 }]
    });
  });
</script>