<?php
require_once __DIR__ . '/config/db.php';

// Landlord details to insert
$username = 'landlord1';
$password = 'password123'; // Plain text password you will type at login
$fullName = 'Mwenda Neema';
$email    = 'landlord@example.com';
$phone    = '0712345678';
$role     = 'Landlord';

// 1. Encrypt password safely using PHP
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // 2. Insert into users table
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, full_name, email, phone_no, role) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$username, $hashedPassword, $fullName, $email, $phone, $role]);

    echo "Landlord account created successfully! You can now log in with:<br>";
    echo "Username: <strong>$username</strong><br>";
    echo "Password: <strong>$password</strong>";
} catch (PDOException $e) {
    echo "Error creating account: " . $e->getMessage();
}
?>