<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

// Only accept POST with a valid CSRF token (a plain link can no longer delete anything)
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = "<div class='error-box'>Invalid request.</div>";
    header("Location: tenants_list.php");
    exit();
}

$tenant_id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($tenant_id) {
    try {
        // Soft delete: set is_active = 0 to retain payment audit trails
        $stmt = $pdo->prepare("UPDATE tenant SET is_active = 0 WHERE tenant_id = ?");
        $stmt->execute([$tenant_id]);

        $_SESSION['flash'] = "<div class='success-box'>Tenant status updated to inactive.</div>";
    } catch (PDOException $e) {
        $_SESSION['flash'] = "<div class='error-box'>Error deactivating tenant.</div>";
    }
}

header("Location: tenants_list.php");
exit();