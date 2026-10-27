<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
    <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>
</div>

<?php if ($_SESSION['role'] === 'Landlord'): ?>
    <div class="card">
        <h3>Landlord Management Portal</h3>
        <a href="tenants_list.php" class="btn-action">Manage Tenants</a>
        <a href="record_pay.php" class="btn-action">Record Payment</a>
        <a href="confirm_pay.php" class="btn-action">Confirm Payments</a>
        <a href="report.php" class="btn-action">View Reports</a>
    </div>
<?php else: ?>
    <div class="card">
        <h3>Tenant Portal</h3>
        <a href="payment_history.php" class="btn-action">View Payment History</a>
        <a href="current_balance.php" class="btn-action">View Outstanding Balance</a>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>