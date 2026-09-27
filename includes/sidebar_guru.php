<div class="sidebar-brand d-none d-lg-flex align-items-center gap-2 px-4 py-4">
  <i class="fa-solid fa-wallet text-primary fs-4"></i>
  <span class="fw-bold fs-5">Smart<span class="text-primary">Finance</span></span>
</div>

<nav class="sidebar-nav flex-grow-1 px-3">
  <a href="/guru/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-gauge"></i> Dashboard
  </a>
  <a href="/guru/siswa_list.php" class="sidebar-link <?= in_array($currentPage, ['siswa_list.php','siswa_detail.php']) ? 'active' : '' ?>">
    <i class="fa-solid fa-user-graduate"></i> Siswa Pantauan
  </a>
  <a href="/guru/profil.php" class="sidebar-link <?= $currentPage === 'profil.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-user"></i> Profil
  </a>
</nav>

<div class="px-3 pb-4">
  <a href="/auth/logout.php" class="sidebar-link text-danger">
    <i class="fa-solid fa-right-from-bracket"></i> Keluar
  </a>
</div>
