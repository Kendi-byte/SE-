<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control: Tenant only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tenant') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query v_payment_history view filtered by logged-in tenant
$stmt = $pdo->prepare("
    SELECT payment_id, amount_paid, payment_date, status, recorded_at, confirmation_timestamp 
    FROM v_payment_history 
    WHERE user_id = ? 
    ORDER BY recorded_at DESC
");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();

$pageTitle = "My Payment History";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>My Payment History</h2>
    <p style="margin-bottom: 20px; color: #555;">Record of all payments submitted and their verification status.</p>

    <div style="margin-bottom: 20px;">
        <a href="dashboard.php" class="btn-action btn-secondary">← Back to Dashboard</a>
    </div>

    <?php if (count($payments) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Payment Date</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                    <th>Recorded At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td>#<?php echo (int)$p['payment_id']; ?></td>
                    <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
                    <td>KES <?php echo number_format($p['amount_paid'], 2); ?></td>
                    <td>
                        <?php if ($p['status'] === 'Confirmed'): ?>
                            <span style="color: #0f5132; background-color: #d1e7dd; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">Confirmed</span>
                        <?php else: ?>
                            <span style="color: #856404; background-color: #fff3cd; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($p['recorded_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No payment records found.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>