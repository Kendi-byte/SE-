<?php
require_once 'db.php';

if (isset($_POST['add_tenant'])) {

    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $house_number = trim($_POST['house_number']);
    $rent = $_POST['expected_monthly_rent'];

    try {
        $conn->beginTransaction();

        $sql = "INSERT INTO users
                (username, password_hash, full_name, email, phone_no, role)
                VALUES (?, ?, ?, ?, ?, 'Tenant')";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $username, $password, $full_name,
            $email, $phone_no
        ]);

        $user_id = $conn->lastInsertId();

        $sql = "INSERT INTO tenant
                (user_id, house_number, expected_monthly_rent)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $house_number, $rent]);

        $conn->commit();

        header("Location: tenants.php");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Unable to register tenant. Check whether the username or email already exists.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Tenant</title>
</head>
<body>

<h2>Register New Tenant</h2>

<?php if (isset($error)) echo "<p>$error</p>"; ?>

<form method="POST">

    Full Name:
    <input type="text" name="full_name" required><br><br>

    Username:
    <input type="text" name="username" required><br><br>

    Email:
    <input type="email" name="email" required><br><br>

    Phone Number:
    <input type="text" name="phone_no"><br><br>

    Password:
    <input type="password" name="password" required><br><br>

    House Number:
    <input type="text" name="house_number" required><br><br>

    Expected Monthly Rent:
    <input type="number" name="expected_monthly_rent"
           min="0" step="0.01" required><br><br>

    <button type="submit" name="add_tenant">Register Tenant</button>

</form>

<a href="tenants.php">Back</a>

</body>
</html>