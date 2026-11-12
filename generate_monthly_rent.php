<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/db.php';

// Access control: Landlord only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_rent'])) {
    $billing_month_input = trim($_POST['billing_month'] ?? '');

    if (!empty($billing_month_input)) {
        $billing_month = $billing_month_input . '-01'; // YYYY-MM-01

        try {
            $pdo->beginTransaction();

            // Fetch active tenants
            $stmt = $pdo->query("SELECT tenant_id, expected_monthly_rent FROM tenant WHERE is_active = 1");
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $added_count = 0;

            foreach ($tenants as $t) {
                $tenant_id = (int)$t['tenant_id'];
                $monthly_rent = (float)$t['expected_monthly_rent'];

                // Check if charge already exists for this tenant & billing month
                $chk = $pdo->prepare("SELECT 1 FROM rent_charge WHERE tenant_id = ? AND billing_month = ?");
                $chk->execute([$tenant_id, $billing_month]);

                if (!$chk->fetch()) {
                    // Insert into rent_charge table
                    $ins = $pdo->prepare("INSERT INTO rent_charge (tenant_id, billing_month, amount) VALUES (?, ?, ?)");
                    $ins->execute([$tenant_id, $billing_month, $monthly_rent]);

                    // Update total_rent_due in balance_record
                    $upd = $pdo->prepare("UPDATE balance_record SET total_rent_due = total_rent_due + ? WHERE tenant_id = ?");
                    $upd->execute([$monthly_rent, $tenant_id]);

                    $added_count++;
                }
            }

            $pdo->commit();

            if ($added_count > 0) {
                $_SESSION['flash'] = "<div class='success-box'>Successfully generated rent charges for $added_count tenant(s).</div>";
            } else {
                $_SESSION['flash'] = "<div class='error-box'>Rent charges for $billing_month_input have already been generated.</div>";
            }

            header("Location: report.php");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "<div class='error-box'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='error-box'>Please select a valid month.</div>";
    }
}

$pageTitle = "Generate Monthly Rent";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Generate Monthly Rent Charges</h2>
    <p style="margin-bottom: 20px; color: #555;">Charge active tenants their expected monthly rent for a selected billing cycle.</p>

    <?php echo $message; ?>

    <form action="generate_monthly_rent.php" method="POST">
        <div class="form-group">
            <label for="billing_month">Select Month & Year:</label>
            <input type="month" id="billing_month" name="billing_month" value="<?php echo date('Y-m'); ?>" required>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button type="submit" name="generate_rent" class="btn-action">Apply Rent Charges</button>
            <a href="report.php" class="btn-action btn-secondary">Back to Reports</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>