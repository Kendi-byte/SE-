<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tenant'])) {
    $full_name    = trim($_POST['full_name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_no     = trim($_POST['phone_no'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $house_number = trim($_POST['house_number'] ?? '');
    $rent         = trim($_POST['expected_monthly_rent'] ?? '');

    if (!empty($full_name) && !empty($username) && !empty($email) && !empty($password) && !empty($house_number) && !empty($rent)) {
        try {
            $pdo->beginTransaction();

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // 1. Insert into users table
            $sqlUser = "INSERT INTO users (username, password_hash, full_name, email, phone_no, role) 
                        VALUES (?, ?, ?, ?, ?, 'Tenant')";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([$username, $password_hash, $full_name, $email, $phone_no]);

            $user_id = $pdo->lastInsertId();

            // 2. Insert into tenant table (Database trigger trg_tenant_create_balance will create balance_record)
            $sqlTenant = "INSERT INTO tenant (user_id, house_number, expected_monthly_rent) 
                          VALUES (?, ?, ?)";
            $stmtTenant = $pdo->prepare($sqlTenant);
            $stmtTenant->execute([$user_id, $house_number, $rent]);

            $pdo->commit();

            $_SESSION['flash'] = "<div class='success-box'>Tenant registered successfully!</div>";
            header("Location: tenants_list.php");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Unable to register tenant. Check if username or email is already taken.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

$pageTitle = "Add New Tenant";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Register New Tenant</h2>

    <?php if (!empty($error)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="add_tenant.php" method="POST">
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required>
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="phone_no">Phone Number:</label>
            <input type="text" id="phone_no" name="phone_no">
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="house_number">House Number:</label>
            <input type="text" id="house_number" name="house_number" required>
        </div>

        <div class="form-group">
            <label for="expected_monthly_rent">Expected Monthly Rent:</label>
            <input type="number" id="expected_monthly_rent" name="expected_monthly_rent" min="0" step="0.01" required>
        </div>

        <button type="submit" name="add_tenant" class="btn-action">Register Tenant</button>
        <a href="tenants_list.php" class="btn-action btn-secondary">Back to List</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>