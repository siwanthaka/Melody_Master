<?php
// admin/dashboard.php
require_once '../includes/auth.php';
requireAdmin();
$pdo = getDB();

// Stats
$revenue  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled'")->fetchColumn();
$orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5 AND is_digital=0")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*,u.name AS uname FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Admin Dashboard | Melody Masters</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'partials/admin_sidebar.php'; ?>

<div class="admin-main">
    <?php include 'partials/admin_topbar.php'; ?>
    <div class="admin-content">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-sterling-sign"></i></div>
                <div class="stat-info"><div class="value">£<?= number_format($revenue,0) ?></div><div class="label">Total Revenue</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-info"><div class="value"><?= $orders ?></div><div class="label">Total Orders</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-info"><div class="value"><?= $users ?></div><div class="label">Customers</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-guitar"></i></div>
                <div class="stat-info"><div class="value"><?= $products ?></div><div class="label">Products</div></div>
            </div>
            <?php if ($lowStock > 0): ?>
            <div class="stat-card" style="border-left:3px solid var(--warning);">
                <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info"><div class="value" style="color:var(--warning);"><?= $lowStock ?></div><div class="label">Low Stock Items</div></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h3>Recent Orders</h3>
                <a href="manage_orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <table class="admin-table">
                <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Shipping</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= e($o['uname']) ?></td>
                    <td>£<?= number_format($o['total'],2) ?></td>
                    <td>£<?= number_format($o['shipping'],2) ?></td>
                    <td><?= date('j M Y', strtotime($o['created_at'])) ?></td>
                    <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td><a href="manage_orders.php?edit=<?= $o['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Low Stock Warning -->
        <?php if ($lowStock > 0):
            $lowItems = $pdo->query("SELECT * FROM products WHERE stock > 0 AND stock <= 5 AND is_digital=0 ORDER BY stock ASC LIMIT 5")->fetchAll();
        ?>
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h3><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Low Stock Alert</h3>
                <a href="manage_products.php" class="btn btn-outline btn-sm">Manage Products</a>
            </div>
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Brand</th><th>Stock</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($lowItems as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['brand']) ?></td>
                    <td><span class="badge badge-warning"><?= $item['stock'] ?> left</span></td>
                    <td><a href="manage_products.php?edit=<?= $item['id'] ?>" class="btn btn-primary btn-sm">Update Stock</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>
</div>
</body>
</html>
