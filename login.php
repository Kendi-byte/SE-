<?php
session_start();
require_once __DIR__ . '/config/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'Tenant') {
                    $tenantStmt = $pdo->prepare("SELECT tenant_id FROM tenant WHERE user_id = ?");
                    $tenantStmt->execute([$user['user_id']]);
                    $tenant = $tenantStmt->fetch();
                    $_SESSION['tenant_id'] = $tenant['tenant_id'] ?? null;
                }

                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error_message = "An error occurred during authentication.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rent Payment System</title>
    <!-- Linked to your CSS file in the assets folder -->
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="login-body">

<div class="login-card">
    <h2>Rent Payment System</h2>

    <?php if (!empty($error_message)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn-action" style="width: 100%;">Log In</button>
    </form>
</div>

</body>
</html>