<?php
// Start session management
session_start();

// Include PDO database connection
require_once __DIR__ . '/config/db.php';

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            // 1. Query user from database using prepared statement (SQL Injection prevention)
            $stmt = $pdo->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 2. Verify user exists and check password hash
            if ($user && password_verify($password, $user['password_hash'])) {
                // Store user session variables
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // If user is a Tenant, fetch and store their specific tenant_id
                if ($user['role'] === 'Tenant') {
                    $tenantStmt = $pdo->prepare("SELECT tenant_id FROM tenant WHERE user_id = ?");
                    $tenantStmt->execute([$user['user_id']]);
                    $tenant = $tenantStmt->fetch();
                    $_SESSION['tenant_id'] = $tenant['tenant_id'] ?? null;
                }

                // Redirect to dashboard upon successful login
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 380px;
        }
        .login-card h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background-color: #004D40;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #00332c;
        }
        .error-box {
            background-color: #f8d7da;
            color: #842029;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>

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

        <button type="submit" class="btn-submit">Log In</button>
    </form>
</div>

</body>
</html>