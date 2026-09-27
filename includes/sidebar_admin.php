<div class="sidebar-brand d-none d-lg-flex align-items-center gap-2 px-4 py-4">
  <i class="fa-solid fa-wallet text-primary fs-4"></i>
  <span class="fw-bold fs-5">Smart<span class="text-primary">Finance</span></span>
</div>

<nav class="sidebar-nav flex-grow-1 px-3">
  <a href="/admin/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-gauge"></i> Dashboard
  </a>
  <a href="/admin/users.php?role=siswa" class="sidebar-link <?= ($currentPage === 'users.php' && ($_GET['role'] ?? '') === 'siswa') ? 'active' : '' ?>">
    <i class="fa-solid fa-user-graduate"></i> Data Siswa
  </a>
  <a href="/admin/users.php?role=guru" class="sidebar-link <?= ($currentPage === 'users.php' && ($_GET['role'] ?? '') === 'guru') ? 'active' : '' ?>">
    <i class="fa-solid fa-chalkboard-user"></i> Data Guru
  </a>
  <a href="/admin/user_form.php" class="sidebar-link <?= $currentPage === 'user_form.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-user-plus"></i> Tambah Akun
  </a>
  <a href="/admin/kategori.php" class="sidebar-link <?= $currentPage === 'kategori.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-tags"></i> Kategori
  </a>
</nav>

<div class="px-3 pb-4">
  <a href="/auth/logout.php" class="sidebar-link text-danger">
    <i class="fa-solid fa-right-from-bracket"></i> Keluar
  </a>
</div>