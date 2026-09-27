<?php
// Pastikan auth.php sudah di-include & requireRole() sudah dipanggil di halaman pemanggil
$role = $_SESSION['role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Smart Finance' : 'Smart Finance' ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-body">

<!-- TOPBAR (mobile) -->
<nav class="navbar navbar-light bg-white shadow-sm d-lg-none sticky-top">
  <div class="container-fluid">
    <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
      <i class="fa-solid fa-bars"></i>
    </button>
    <a class="navbar-brand fw-bold mb-0" href="/index.php">
      Smart<span class="text-primary">Finance</span>
    </a>
    <div style="width: 40px;"></div>
  </div>
</nav>

<div class="app-wrapper d-flex">

  <!-- SIDEBAR (desktop) -->
  <aside class="app-sidebar d-none d-lg-flex flex-column">
    <?php include __DIR__ . '/sidebar_' . $role . '.php'; ?>
  </aside>

  <!-- SIDEBAR (offcanvas mobile) -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
      <a class="navbar-brand fw-bold mb-0" href="/index.php">Smart<span class="text-primary">Finance</span></a>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
      <?php include __DIR__ . '/sidebar_' . $role . '.php'; ?>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <main class="app-content flex-grow-1">

    <!-- TOPBAR (desktop) -->
    <div class="d-none d-lg-flex align-items-center justify-content-between app-topbar">
      <h5 class="fw-bold mb-0"><?= htmlspecialchars($pageTitle ?? '') ?></h5>
      <div class="dropdown">
        <button class="btn btn-light d-flex align-items-center gap-2 border rounded-pill px-3" type="button" data-bs-toggle="dropdown">
          <span class="user-avatar">
            <i class="fa-solid fa-user"></i>
          </span>
          <span class="fw-semibold"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
          <i class="fa-solid fa-chevron-down small text-secondary"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li><span class="dropdown-item-text small text-secondary text-capitalize"><?= htmlspecialchars($role) ?></span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/<?= $role ?>/profil.php"><i class="fa-solid fa-user-pen me-2"></i>Profil</a></li>
          <li><a class="dropdown-item text-danger" href="/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Keluar</a></li>
        </ul>
      </div>
    </div>

    <div class="app-content-inner">
      <?php $flash = getFlash(); ?>
      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
          <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
          <?= htmlspecialchars($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>