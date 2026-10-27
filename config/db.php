<?php
// Database configuration
$host    = 'localhost';
$dbname  = 'rent_tracking_system';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options for security, error handling, and performance
$options = [
    // Throw PDOException on errors (essential for error checking & debugging)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Fetch associative arrays by default ($row['column_name'])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Disable emulated prepared statements to ensure real prepared statements (SQL Injection defense)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch (PDOException $e) {
    // Stop execution and display connection error message
    die("Database Connection Failed: " . $e->getMessage());
}
?>
