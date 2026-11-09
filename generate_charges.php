<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/recalculate_balance.php';

// Access control: Landlord only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = trim($_POST['billing_month'] ?? '');   // format Y-m from <input type="month">
    $d = DateTime::createFromFormat('Y-m-d', $month . '-01');
    $validMonth = preg_match('/^\d{4}-\d{2}$/', $month) && $d && $d->format('Y-m') === $month
                  && $d->format('Y-m-d') <= date('Y-m-01');   // no future months

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = "<div class='error-box'>Invalid form submission. Please try again.</div>";
    } elseif (!$validMonth) {
        $message = "<div class='error-box'>Please select a valid month (not in the future).</div>";
    } else {
        $billing_month = $d->format('Y-m-d');   // always the 1st

        try {
            $pdo->beginTransaction();

            $tenants = $pdo->query("SELECT tenant_id, expected_monthly_rent FROM tenant WHERE is_active = 1")->fetchAll();

            // INSERT IGNORE + unique key (tenant_id, billing_month) = no duplicate charges
            $ins = $pdo->prepare("INSERT IGNORE INTO rent_charge (tenant_id, billing_month, amount) VALUES (?, ?, ?)");

            $added = 0;
            foreach ($tenants as $t) {
                $ins->execute([$t['tenant_id'], $billing_month, $t['expected_monthly_rent']]);
                $added += $ins->rowCount();
                recalculateBalance($pdo, (int)$t['tenant_id']);
            }

            $pdo->commit();

            $_SESSION['flash'] = "<div class='success-box'>Rent charges for " . htmlspecialchars($d->format('F Y')) .
                                 " generated. New charges added: $added.</div>";
            header("Location: generate_charges.php");
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<div class='error-box'>A database error occurred. No changes were made.</div>";
        }
    }
}

// Current balances (for checking your work)
$balances = $pdo->query("SELECT full_name, house_number, expected_monthly_rent, total_rent_due,
                                total_paid, outstanding_balance, payment_status
                         FROM v_paid_arrears_report
                         ORDER BY house_number")->fetchAll();

$pageTitle = "Generate Rent Charges";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Generate Monthly Rent Charges</h2>
    <?php echo $message; ?>

    <form action="generate_charges.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="form-group">
            <label for="billing_month">Billing Month:</label>
            <input type="month" id="billing_month" name="billing_month"
                   max="<?php echo date('Y-m'); ?>" value="<?php echo date('Y-m'); ?>" required>
        </div>
        <button type="submit" class="btn-action">Generate Charges</button>
        <a href="dashboard.php" class="btn-action btn-secondary">← Back to Dashboard</a>
    </form>
</div>

<div class="card">
    <h2>Tenant Balances</h2>
    <?php if (count($balances) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>House</th>
                    <th>Monthly Rent</th>
                    <th>Total Due</th>
                    <th>Total Paid</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($balances as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['house_number']); ?></td>
                    <td><?php echo number_format($b['expected_monthly_rent'], 2); ?></td>
                    <td><?php echo number_format($b['total_rent_due'], 2); ?></td>
                    <td><?php echo number_format($b['total_paid'], 2); ?></td>
                    <td><?php echo number_format($b['outstanding_balance'], 2); ?></td>
                    <td><?php echo htmlspecialchars($b['payment_status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No active tenants found.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>