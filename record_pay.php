<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control: Ensure only Landlords can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

$message = '';

// Show one-time message after a successful redirect
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id    = filter_var($_POST['tenant_id'] ?? '', FILTER_VALIDATE_INT);
    $amount_paid  = trim($_POST['amount_paid'] ?? '');
    $payment_date = trim($_POST['payment_date'] ?? '');

    // Validate date: real date, format Y-m-d, not in the future
    $d = DateTime::createFromFormat('Y-m-d', $payment_date);
    $validDate = $d && $d->format('Y-m-d') === $payment_date && $payment_date <= date('Y-m-d');

    // Validate amount: numeric, max 2 decimals, greater than 0
    $validAmount = preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount_paid) && (float)$amount_paid > 0;

    if ($tenant_id && $validAmount && $validDate) {
        try {
            // Make sure the tenant exists and is active
            $check = $pdo->prepare("SELECT 1 FROM tenant WHERE tenant_id = ? AND is_active = 1");
            $check->execute([$tenant_id]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO payment (tenant_id, amount_paid, payment_date, status) VALUES (?, ?, ?, 'Pending')");
                $stmt->execute([$tenant_id, $amount_paid, $payment_date]);

                // Redirect so refreshing the page doesn't resubmit the form
                $_SESSION['flash'] = "<div class='success-box'>Payment recorded successfully. Awaiting confirmation.</div>";
                header("Location: record_pay.php");
                exit();
            }

            $message = "<div class='error-box'>Selected tenant is invalid or inactive.</div>";
        } catch (PDOException $e) {
            $message = "<div class='error-box'>Error recording payment. Please try again.</div>";
        }
    } else {
        $message = "<div class='error-box'>Please fill in all fields with valid data.</div>";
    }
}

// Fetch all active tenants for the dropdown
$tenantsStmt = $pdo->query("SELECT t.tenant_id, u.full_name, t.house_number
                            FROM tenant t
                            JOIN users u ON t.user_id = u.user_id
                            WHERE t.is_active = 1
                            ORDER BY t.house_number");
$tenants = $tenantsStmt->fetchAll();

$pageTitle = "Record Payment";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Record Tenant Payment</h2>
    <?php echo $message; ?>

    <form action="record_pay.php" method="POST">
        <div class="form-group">
            <label for="tenant_id">Select Tenant:</label>
            <select name="tenant_id" id="tenant_id" required>
                <option value="">-- Choose Tenant --</option>
                <?php foreach ($tenants as $t): ?>
                    <option value="<?php echo (int)$t['tenant_id']; ?>">
                        <?php echo htmlspecialchars($t['full_name'] . ' (House: ' . $t['house_number'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount_paid">Amount Paid:</label>
            <input type="number" step="0.01" min="0.01" id="amount_paid" name="amount_paid" required>
        </div>

        <div class="form-group">
            <label for="payment_date">Payment Date:</label>
            <input type="date" id="payment_date" name="payment_date" max="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <button type="submit" class="btn-action">Record Payment</button>
        
    </form>
    
</div>
<div class="card-footer-actions">
    <a href="dashboard.php" class="btn-action btn-secondary">Back</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>