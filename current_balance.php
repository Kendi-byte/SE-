<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control: Tenant only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tenant') {
    header("Location: dashboard.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'] ?? 0;

// Fetch tenant's balance record
$stmt = $pdo->prepare("
    SELECT full_name, house_number, expected_monthly_rent, 
           total_rent_due, total_paid, outstanding_balance, payment_status 
    FROM v_paid_arrears_report 
    WHERE tenant_id = ?
");
$stmt->execute([$tenant_id]);
$balanceInfo = $stmt->fetch();

$pageTitle = "My Current Balance";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Account Balance Summary</h2>
    <p style="margin-bottom: 20px; color: #555;">Overview of your rent status and balance.</p>

    <div style="margin-bottom: 20px;">
        <a href="dashboard.php" class="btn-action btn-secondary">← Back to Dashboard</a>
    </div>

    <?php if ($balanceInfo): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
            <p style="margin-bottom: 8px;"><strong>House Number:</strong> <?php echo htmlspecialchars($balanceInfo['house_number']); ?></p>
            <p style="margin-bottom: 8px;"><strong>Expected Monthly Rent:</strong> KES <?php echo number_format($balanceInfo['expected_monthly_rent'], 2); ?></p>
            <p style="margin-bottom: 8px;"><strong>Total Rent Billed:</strong> KES <?php echo number_format($balanceInfo['total_rent_due'], 2); ?></p>
            <p style="margin-bottom: 8px;"><strong>Total Rent Paid:</strong> KES <?php echo number_format($balanceInfo['total_paid'], 2); ?></p>
            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #ddd;">
            <p style="font-size: 18px; margin-bottom: 10px;">
                <strong>Outstanding Balance:</strong> 
                <span style="color: <?php echo $balanceInfo['outstanding_balance'] > 0 ? '#d9534f' : '#004D40'; ?>;">
                    KES <?php echo number_format($balanceInfo['outstanding_balance'], 2); ?>
                </span>
            </p>
            <p style="margin: 0;">
                <strong>Status:</strong> 
                <?php if ($balanceInfo['payment_status'] === 'Paid'): ?>
                    <span style="color: #0f5132; background-color: #d1e7dd; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">Paid</span>
                <?php else: ?>
                    <span style="color: #842029; background-color: #f8d7da; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">Arrears</span>
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <p>No active balance record found for your account.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>