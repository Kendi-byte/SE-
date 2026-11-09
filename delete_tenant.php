<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    $tenant_id = (int) $_GET['id'];

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
?>