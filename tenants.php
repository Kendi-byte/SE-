<?php
require_once 'db.php';

$sql = "SELECT t.tenant_id, u.full_name, u.email, u.phone_no,
               t.house_number, t.expected_monthly_rent
        FROM tenant t
        JOIN users u ON t.user_id = u.user_id
        ORDER BY t.tenant_id DESC";

$stmt = $conn->query($sql);
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tenant Management</title>
</head>
<body>

<h2>Tenant Management</h2>

<a href="add_tenant.php">Add New Tenant</a>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>House Number</th>
        <th>Monthly Rent</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($tenants as $tenant): ?>
    <tr>
        <td><?= htmlspecialchars($tenant['tenant_id']) ?></td>
        <td><?= htmlspecialchars($tenant['full_name']) ?></td>
        <td><?= htmlspecialchars($tenant['email']) ?></td>
        <td><?= htmlspecialchars($tenant['phone_no'] ?? '') ?></td>
        <td><?= htmlspecialchars($tenant['house_number']) ?></td>
        <td><?= htmlspecialchars($tenant['expected_monthly_rent']) ?></td>
        <td>
            <a href="edit_tenant.php?id=<?= $tenant['tenant_id'] ?>">Edit</a>

            <a href="delete_tenant.php?id=<?= $tenant['tenant_id'] ?>"
               onclick="return confirm('Delete this tenant?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>