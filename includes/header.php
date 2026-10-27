<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Rent Payment System'; ?></title>
    <!-- Link to stylesheet in assets folder -->
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
<div class="navbar">
    <h1>Rent Payment Confirmation System</h1>
    <a href="logout.php" class="btn-logout">Log Out</a>
</div>
<?php endif; ?>

<div class="container">