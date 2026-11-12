<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$tenant_id = (int)($_GET['id'] ?? 0);

// Fetch existing details
$stmt = $pdo->prepare("
    SELECT t.tenant_id, t.house_number, t.expected_monthly_rent, t.move_in_date,
           u.user_id, u.full_name, u.email, u.phone_no
    FROM tenant t
    JOIN users u ON t.user_id = u.user_id
    WHERE t.tenant_id = ? AND t.is_active = 1
");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();

if (!$tenant) {
    header("Location: tenants_list.php");
    exit();
}

// The move-in date is fixed at registration and can never be edited here
// (the database also refuses any change, see lock_move_in_date.sql).

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tenant'])) {
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_no     = trim($_POST['phone_no'] ?? '');
    $house_number = trim($_POST['house_number'] ?? '');
    $rent         = trim($_POST['expected_monthly_rent'] ?? '');


    if (!empty($full_name) && !empty($email) && !empty($house_number) && !empty($rent)) {
        try {
            $pdo->beginTransaction();

            // Update users table
            $upUser = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone_no = ? WHERE user_id = ?");
            $upUser->execute([$full_name, $email, $phone_no, $tenant['user_id']]);

            // Update tenant table
            $upTenant = $pdo->prepare("UPDATE tenant SET house_number = ?, expected_monthly_rent = ? WHERE tenant_id = ?");
            $upTenant->execute([$house_number, $rent, $tenant_id]);

            $pdo->commit();

            $_SESSION['flash'] = "<div class='success-box'>Tenant details updated successfully!</div>";
            header("Location: tenants_list.php");
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error updating tenant details.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

$pageTitle = "Edit Tenant";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Edit Tenant Details</h2>

    <?php if (!empty($error)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="edit_tenant.php?id=<?php echo $tenant_id; ?>" method="POST">
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($tenant['full_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($tenant['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone_no">Phone Number:</label>
            <input type="text" id="phone_no" name="phone_no" value="<?php echo htmlspecialchars($tenant['phone_no'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="house_number">House Number:</label>
            <input type="text" id="house_number" name="house_number" value="<?php echo htmlspecialchars($tenant['house_number']); ?>" required>
        </div>

        <div class="form-group">
            <label for="expected_monthly_rent">Expected Monthly Rent:</label>
            <input type="number" id="expected_monthly_rent" name="expected_monthly_rent" min="0" step="0.01" value="<?php echo htmlspecialchars($tenant['expected_monthly_rent']); ?>" required>
        </div>

        <div class="form-group">
            <label for="move_in_date">Move-in Date:</label>
            <input type="date" id="move_in_date" name="move_in_date" value="<?php echo htmlspecialchars($tenant['move_in_date']); ?>" required>
        </div>

        <button type="submit" name="update_tenant" class="btn-action">Update Tenant</button>
        <a href="tenants_list.php" class="btn-action btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>