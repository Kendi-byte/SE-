<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access control: Landlord only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Landlord') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Fetch active tenants
$sql = "SELECT t.tenant_id, u.full_name, u.email, u.phone_no,
               t.house_number, t.expected_monthly_rent
        FROM tenant t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.is_active = 1
        ORDER BY t.tenant_id DESC";

$stmt = $pdo->query($sql);
$tenants = $stmt->fetchAll();

$pageTitle = "Tenant Management";
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Tenant Management</h2>
    <?php echo $message; ?>

    <!-- Navigation & Action Buttons -->
    <div style="margin-bottom: 20px; display: flex; gap: 10px;">
        <a href="add_tenant.php" class="btn-action">+ Add New Tenant</a>
        <a href="dashboard.php" class="btn-action btn-secondary">← Back to Dashboard</a>
    </div>

    <?php if (count($tenants) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>House Number</th>
                    <th>Monthly Rent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $tenant): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tenant['tenant_id']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['email']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['phone_no'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($tenant['house_number']); ?></td>
                    <td><?php echo number_format($tenant['expected_monthly_rent'], 2); ?></td>
                    <td>
                        <a href="edit_tenant.php?id=<?php echo (int)$tenant['tenant_id']; ?>" class="btn-action btn-secondary" style="padding: 4px 8px; font-size: 12px;">Edit</a>
                        <a href="delete_tenant.php?id=<?php echo (int)$tenant['tenant_id']; ?>" 
                           onclick="return confirm('Are you sure you want to remove this tenant?');" 
                           class="btn-action" style="background-color: #d9534f; padding: 4px 8px; font-size: 12px;">Remove</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No active tenants found.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>