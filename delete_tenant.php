<?php
require_once 'db.php';

if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    $stmt = $conn->prepare(
        "SELECT user_id FROM tenant WHERE tenant_id = ?"
    );

    $stmt->execute([$id]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tenant) {
        $stmt = $conn->prepare(
            "DELETE FROM users WHERE user_id = ?"
        );

        $stmt->execute([$tenant['user_id']]);
    }
}

header("Location: tenants.php");
exit();
?>