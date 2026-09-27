<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('guru');

$guruId = $_SESSION['user_id'];
$pageTitle = 'Siswa Pantauan';

$stmt = $pdo->prepare("
    SELECT u.id, u.nama, u.email, u.nis_nip, u.kelas,
        COALESCE(SUM(CASE WHEN t.jenis='pemasukan' THEN t.jumlah ELSE 0 END),0) AS total_pemasukan,
        COALESCE(SUM(CASE WHEN t.jenis='pengeluaran' THEN t.jumlah ELSE 0 END),0) AS total_pengeluaran,
        COUNT(t.id) AS jumlah_transaksi
    FROM guru_siswa gs
    JOIN users u ON u.id = gs.siswa_id
    LEFT JOIN transaksi t ON t.siswa_id = u.id
    WHERE gs.guru_id = ?
    GROUP BY u.id, u.nama, u.email, u.nis_nip, u.kelas
    ORDER BY u.nama ASC
");
$stmt->execute([$guruId]);
$siswaList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/css/dataTables.bootstrap5.min.css">

<div class="stat-card">
  <h6 class="fw-bold mb-3">Daftar Siswa Pantauan</h6>

  <div class="table-responsive">
    <table id="tabelSiswa" class="table align-middle w-100">
      <thead>
        <tr class="text-secondary small text-uppercase">
          <th>Nama</th>
          <th>Kelas</th>
          <th>NIS</th>
          <th>Transaksi</th>
          <th>Saldo</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($siswaList)): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada siswa yang memilihmu sebagai guru pemantau.</td></tr>
        <?php endif; ?>
        <?php foreach ($siswaList as $s): ?>
          <?php $saldo = $s['total_pemasukan'] - $s['total_pengeluaran']; ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($s['nama']) ?></td>
            <td class="text-secondary"><?= htmlspecialchars($s['kelas'] ?: '-') ?></td>
            <td class="text-secondary"><?= htmlspecialchars($s['nis_nip'] ?: '-') ?></td>
            <td class="text-secondary"><?= $s['jumlah_transaksi'] ?> transaksi</td>
            <td data-order="<?= $saldo ?>" class="fw-semibold <?= $saldo < 0 ? 'text-danger' : 'text-success' ?>">
              Rp <?= number_format($saldo, 0, ',', '.') ?>
            </td>
            <td class="text-end">
              <a href="siswa_detail.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="fa-solid fa-eye me-1"></i>Lihat Detail
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
    $('#tabelSiswa').DataTable({
      order: [[0, 'asc']],
      columnDefs: [{ orderable: false, targets: -1 }],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_-_END_ dari _TOTAL_ siswa",
        paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
        zeroRecords: "Siswa tidak ditemukan",
        infoEmpty: "Tidak ada data"
      }
    });
  });
</script>
