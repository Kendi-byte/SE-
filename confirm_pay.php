<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

// CSRF token (created once per session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

// Show one-time message after a successful redirect
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Handle confirmation action (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = filter_var($_POST['payment_id'] ?? '', FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = "<div class='error-box'>Invalid form submission. Please try again.</div>";
    } elseif (!$payment_id) {
        $message = "<div class='error-box'>Invalid payment.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the pending payment row so it can't be confirmed twice
            $payStmt = $pdo->prepare("SELECT tenant_id, amount_paid FROM payment WHERE payment_id = ? AND status = 'Pending' FOR UPDATE");
            $payStmt->execute([$payment_id]);
            $payment = $payStmt->fetch();

            if ($payment) {
                // Update payment status
                $updatePay = $pdo->prepare("UPDATE payment SET status = 'Confirmed', confirmation_timestamp = NOW(), confirmed_by = ? WHERE payment_id = ? AND status = 'Pending'");
                $updatePay->execute([$_SESSION['user_id'], $payment_id]);

                // Update balance record
                $updateBal = $pdo->prepare("UPDATE balance_record SET total_paid = total_paid + ? WHERE tenant_id = ?");
                $updateBal->execute([$payment['amount_paid'], $payment['tenant_id']]);

                $pdo->commit();

                // Redirect so refreshing the page doesn't resubmit
                $_SESSION['flash'] = "<div class='success-box'>Payment successfully confirmed! Outstanding balance updated.</div>";
                header("Location: confirm_pay.php");
                exit();
            } else {
                $pdo->rollBack();
                $message = "<div class='error-box'>Payment not found or already confirmed.</div>";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<div class='error-box'>A database error occurred. No changes were made.</div>";
        }
    }
}

// Fetch all pending payments
$pendingStmt = $pdo->query("SELECT p.payment_id, p.amount_paid, p.payment_date, u.full_name, t.house_number
                            FROM payment p
                            JOIN tenant t ON p.tenant_id = t.tenant_id
                            JOIN users u ON t.user_id = u.user_id
                            WHERE p.status = 'Pending'
                            ORDER BY p.payment_date ASC");
$pendingPayments = $pendingStmt->fetchAll();

$pageTitle = "Confirm Payments";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Pending Payments</h2>
    <?php echo $message; ?>

    <?php if (count($pendingPayments) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>House</th>
                    <th>Date Paid</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingPayments as $pay): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pay['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($pay['house_number']); ?></td>
                        <td><?php echo htmlspecialchars($pay['payment_date']); ?></td>
                        <td><?php echo number_format($pay['amount_paid'], 2); ?></td>
                        <td>
                            <form action="confirm_pay.php" method="POST" style="display:inline;"
                                  onsubmit="return confirm('Confirm receipt of this payment?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="payment_id" value="<?php echo (int)$pay['payment_id']; ?>">
                                <button type="submit" class="btn-action">Confirm</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending payments to confirm.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>