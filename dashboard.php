<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rent Payment System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #004D40;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            margin: 0;
            font-size: 20px;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            background-color: #d9534f;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .container {
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn-action {
            display: inline-block;
            padding: 10px 15px;
            background-color: #004D40;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>Rent Payment Confirmation System</h1>
    <a href="logout.php">Log Out</a>
</div>

<div class="container">
    <div class="card">
        <h2>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
        <p>Logged in as: <strong><?php echo htmlspecialchars($role); ?></strong></p>
    </div>

    <?php if ($role === 'Landlord'): ?>
        <!-- Landlord Control Options -->
        <div class="card">
            <h3>Landlord Management Portal</h3>
            <a href="tenants_list.php" class="btn-action">Manage Tenants</a>
            <a href="record_pay.php" class="btn-action">Record Payment</a>
            <a href="confirm_pay.php" class="btn-action">Confirm Payments</a>
            <a href="report.php" class="btn-action">View Arrears Report</a>
        </div>
    <?php else: ?>
        <!-- Tenant Control Options -->
        <div class="card">
            <h3>Tenant Portal</h3>
            <a href="payment_history.php" class="btn-action">View Payment History</a>
            <a href="current_balance.php" class="btn-action">View Outstanding Balance</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>