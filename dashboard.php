<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pageTitle = "Dashboard";

if ($_SESSION['role'] === 'Landlord') {
    // Totals across all ACTIVE tenants
    $totals = $pdo->query("SELECT COALESCE(SUM(total_rent_due), 0) AS billed,
                                  COALESCE(SUM(total_paid), 0)     AS collected,
                                  COALESCE(SUM(CASE WHEN outstanding_balance > 0 THEN outstanding_balance ELSE 0 END), 0) AS outstanding
                           FROM v_paid_arrears_report")->fetch();

    $pending = (int) $pdo->query("SELECT COUNT(*) FROM payment WHERE status = 'Pending'")->fetchColumn();

    // Tenants currently in arrears (largest balance first)
    $arrears = $pdo->query("SELECT full_name, house_number, outstanding_balance
                            FROM v_paid_arrears_report
                            WHERE outstanding_balance > 0
                            ORDER BY outstanding_balance DESC
                            LIMIT 5")->fetchAll();
} else {
    // Tenant: own balance + last 5 payments
    $stmt = $pdo->prepare("SELECT house_number, total_rent_due, total_paid, outstanding_balance, payment_status
                           FROM v_paid_arrears_report WHERE tenant_id = ?");
    $stmt->execute([$_SESSION['tenant_id'] ?? 0]);
    $balance = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT amount_paid, payment_date, status
                           FROM v_payment_history WHERE user_id = ?
                           ORDER BY recorded_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recent = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
    <p><?php echo $_SESSION['role'] === 'Landlord' ? 'Here is where your building stands today.' : 'Here is your rent account at a glance.'; ?></p>
</div>

<?php if ($_SESSION['role'] === 'Landlord'): ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Total rent billed</div>
            <div class="stat-value">KES <?php echo number_format($totals['billed'], 2); ?></div>
        </div>
        <div class="stat-card stat-ok">
            <div class="stat-label">Collected (confirmed)</div>
            <div class="stat-value">KES <?php echo number_format($totals['collected'], 2); ?></div>
        </div>
        <div class="stat-card <?php echo $totals['outstanding'] > 0 ? 'stat-bad' : 'stat-ok'; ?>">
            <div class="stat-label">Outstanding</div>
            <div class="stat-value">KES <?php echo number_format($totals['outstanding'], 2); ?></div>
        </div>
        <a class="stat-card stat-link <?php echo $pending > 0 ? 'stat-warn' : ''; ?>" href="confirm_pay.php">
            <div class="stat-label">Awaiting confirmation</div>
            <div class="stat-value"><?php echo $pending; ?></div>
            <div class="stat-sub"><?php echo $pending > 0 ? 'Review and confirm &rarr;' : 'Nothing to confirm'; ?></div>
        </a>
    </div>

    <div class="card">
        <h3>Tenants in arrears</h3>
        <?php if (count($arrears) > 0): ?>
            <table class="data-table">
                <thead><tr><th>House</th><th>Tenant</th><th>Balance</th></tr></thead>
                <tbody>
                <?php foreach ($arrears as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['house_number']); ?></td>
                        <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                        <td><strong>KES <?php echo number_format($a['outstanding_balance'], 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer-actions">
                <a href="report.php" class="btn-action btn-secondary">View full report</a>
            </div>
        <?php else: ?>
            <p>No tenants are in arrears.</p>
        <?php endif; ?>
    </div>

<?php else: ?>

    <?php if ($balance): ?>
        <?php $owing = $balance['outstanding_balance'] > 0; ?>
        <div class="stat-grid">
            <div class="stat-card <?php echo $owing ? 'stat-bad' : 'stat-ok'; ?>">
                <div class="stat-label">Outstanding balance</div>
                <div class="stat-value">KES <?php echo number_format($balance['outstanding_balance'], 2); ?></div>
                <div class="stat-sub"><span class="badge <?php echo $owing ? 'badge-arrears' : 'badge-paid'; ?>"><?php echo $owing ? 'Arrears' : 'Paid'; ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total rent billed</div>
                <div class="stat-value">KES <?php echo number_format($balance['total_rent_due'], 2); ?></div>
            </div>
            <div class="stat-card stat-ok">
                <div class="stat-label">Total paid (confirmed)</div>
                <div class="stat-value">KES <?php echo number_format($balance['total_paid'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">House</div>
                <div class="stat-value"><?php echo htmlspecialchars($balance['house_number']); ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="card"><p>No balance record found for your account.</p></div>
    <?php endif; ?>

    <div class="card">
        <h3>Recent payments</h3>
        <?php if (count($recent) > 0): ?>
            <table class="data-table">
                <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
                        <td>KES <?php echo number_format($p['amount_paid'], 2); ?></td>
                        <td><span class="badge <?php echo $p['status'] === 'Confirmed' ? 'badge-paid' : 'badge-pending'; ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer-actions">
                <a href="payment_history.php" class="btn-action btn-secondary">View full history</a>
            </div>
        <?php else: ?>
            <p>No payments recorded yet.</p>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
