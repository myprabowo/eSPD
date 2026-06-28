<?php
/**
 * index.php — eSPD Main Entry Point & Router
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

session_start_safe();

// Handle login/logout
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $result = check_login($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($result['status']) {
        do_login($result['username'], $result['role'], $result['display_name'] ?? '');
        log_activity('LOGIN', 'User login berhasil');
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        log_activity('LOGIN_FAILED', 'Gagal login: ' . ($_POST['username'] ?? ''), $_POST['username'] ?? 'Unknown');
        $loginError = 'Username atau password salah.';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    log_activity('LOGOUT', 'User logout');
    do_logout();
    header('Location: index.php');
    exit;
}

// Determine page
$page = $_GET['page'] ?? '';
if (empty($_SESSION['logged_in'])) {
    $page = 'login';
}
if ($page === '' || $page === 'login') {
    if (!empty($_SESSION['logged_in'])) {
        $page = 'dashboard';
    } else {
        $page = 'login';
    }
}

// Valid pages
$validPages = ['login', 'dashboard', 'kegiatan', 'spd_list', 'spd_detail'];
if (!in_array($page, $validPages)) $page = 'dashboard';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="eSPD — Sistem Pengisian Surat Perintah Perjalanan Dinas Online">
    <title>eSPD — <?= h(ucfirst(str_replace('_', ' ', $page))) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.1">
</head>
<body class="<?= $page === 'login' ? 'login-body' : '' ?>">

<?php if ($page !== 'login'): ?>
<!-- Navigation -->
<nav class="main-nav">
    <div class="nav-brand">
        <div class="nav-logo">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <rect width="28" height="28" rx="8" fill="url(#logo-grad)"/>
                <path d="M7 9h14M7 14h10M7 19h14" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <defs><linearGradient id="logo-grad" x1="0" y1="0" x2="28" y2="28"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
            </svg>
        </div>
        <span class="nav-title">eSPD</span>
    </div>
    <div class="nav-links">
        <a href="?page=dashboard" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>
        <a href="?page=kegiatan" class="nav-link <?= $page === 'kegiatan' ? 'active' : '' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Kegiatan
        </a>
    </div>
    <div class="nav-user">
        <div class="user-avatar"><?= mb_substr(current_user(), 0, 1) ?></div>
        <span class="user-name"><?= h(current_user()) ?></span>
        <a href="?action=logout" class="btn-logout" title="Logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</nav>

<main class="main-content">
<?php endif; ?>

<?php
    $pageFile = __DIR__ . '/pages/' . $page . '.php';
    if (file_exists($pageFile)) {
        include $pageFile;
    } else {
        echo '<div class="container"><h1>Halaman tidak ditemukan</h1></div>';
    }
?>

<?php if ($page !== 'login'): ?>
</main>
<footer class="app-footer">
    &copy; 2026 MYP - Unit Pengembangan Layanan dan Bisnis
</footer>
<?php endif; ?>

<!-- Toast container -->
<div id="toast-container"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
