<?php
// Shared page header. Landlords get a left sidebar; tenants get a simple top bar.
$pageTitle  = $pageTitle ?? 'Rent Payment System';
$isLoggedIn = isset($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? '';
$current    = basename($_SERVER['SCRIPT_NAME']);

// Menu: label => [main page, other pages that should keep this item highlighted]
$landlordMenu = [
    'Dashboard'        => ['dashboard.php',    [], 'dashboard'],
    'Tenants'          => ['tenants_list.php', ['add_tenant.php', 'edit_tenant.php'], 'tenants'],
    'Record Payment'   => ['record_pay.php',   [], 'record'],
    'Confirm Payments' => ['confirm_pay.php',  [], 'confirm'],
    'Reports'          => ['report.php',       ['generate_charges.php', 'generate_monthly_rent.php'], 'reports'],
];
$tenantMenu = [
    'Dashboard'       => ['dashboard.php',       [], 'dashboard'],
    'Payment History' => ['payment_history.php', [], 'history'],
    'My Balance'      => ['current_balance.php', [], 'balance'],
];

// Simple line icons (24x24)
$icons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    'tenants'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'record'    => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
    'confirm'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    'reports'   => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
    'history'   => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'balance'   => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
    'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    'collapse'  => '<polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/>',
];
$svg = function (string $name) use ($icons): string {
    return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . $icons[$name] . '</svg>';
};

$layoutSidebar = $isLoggedIn;
$menu          = ($role === 'Landlord') ? $landlordMenu : $tenantMenu;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Rent Payment System</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script>try { if (localStorage.getItem("sidebar") === "collapsed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}</script>
</head>
<body class="<?php echo $layoutSidebar ? 'layout-sidebar' : 'layout-top'; ?>">

<?php if ($layoutSidebar): ?>

    <!-- Sidebar toggle for phones (no JavaScript needed) -->
    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <div class="mobilebar">
        <label for="nav-toggle" class="menu-btn">&#9776; Menu</label>
        <span class="mobilebar-title">Rent Payment System</span>
    </div>

    <aside class="sidebar">
        <div class="sidebar-head">
            <div class="brand">Rent Payment<br>Confirmation System</div>
            <button type="button" class="collapse-btn" id="sidebarToggle" title="Collapse / expand menu" aria-label="Collapse or expand the menu"><?php echo $svg('collapse'); ?></button>
        </div>
        <nav class="side-nav">
            <?php foreach ($menu as $label => [$page, $others, $icon]): ?>
                <a href="<?php echo $page; ?>" title="<?php echo htmlspecialchars($label); ?>"
                   class="<?php echo ($current === $page || in_array($current, $others, true)) ? 'active' : ''; ?>">
                    <?php echo $svg($icon); ?><span class="lbl"><?php echo htmlspecialchars($label); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="side-user">
            <div class="side-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></div>
            <div class="side-user-role"><?php echo htmlspecialchars($role); ?></div>
            <a href="logout.php" class="btn-logout" title="Log Out"><?php echo $svg('logout'); ?><span class="lbl">Log Out</span></a>
        </div>
    </aside>

    <main class="main">
    <div class="container">

<?php else: ?>

    <div class="container">

<?php endif; ?>
