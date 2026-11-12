<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control: Landlord only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Query the database view
$sql = "SELECT tenant_id, full_name, house_number, expected_monthly_rent, 
               total_rent_due, total_paid, outstanding_balance, payment_status 
        FROM v_paid_arrears_report 
        ORDER BY house_number ASC";

$stmt = $pdo->query($sql);
$reports = $stmt->fetchAll();

$pageTitle = "Financial & Arrears Report";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Tenant Payment & Arrears Report</h2>
    <p style="margin-bottom: 20px; color: #5c534b;">Overview of outstanding balances and overall payment statuses.</p>

    <?php echo $message; ?>

    <div style="margin-bottom: 20px;">
        <a href="generate_charges.php" class="btn-action">Generate Monthly Rent Report</a>
    </div>

    <?php if (count($reports) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>House</th>
                    <th>Tenant Name</th>
                    <th>Monthly Rent</th>
                    <th>Total Rent Due</th>
                    <th>Total Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['house_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo number_format($row['expected_monthly_rent'], 2); ?></td>
                    <td><?php echo number_format($row['total_rent_due'], 2); ?></td>
                    <td><?php echo number_format($row['total_paid'], 2); ?></td>
                    <td>
                        <strong>
                            <?php echo number_format($row['outstanding_balance'], 2); ?>
                        </strong>
                    </td>
                    <td>
                        <?php if ($row['payment_status'] === 'Paid'): ?>
                            <span style="color: #1f5c35; background-color: #e6f2ea; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">Paid</span>
                        <?php else: ?>
                            <span style="color: #8a2424; background-color: #f9e6e6; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">Arrears</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No tenant records found.</p>
    <?php endif; ?>

    
</div>
<!-- Bottom Right Back Button -->
    <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
        <a href="dashboard.php" class="btn-action btn-secondary" style="margin-right: 0;">Back</a>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>