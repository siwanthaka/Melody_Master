<?php
require_once 'includes/auth.php';
requireLogin();
$pdo  = getDB();
$user = getUser();
$uid  = $_SESSION['user_id'];

// Active tab
$tab = $_GET['tab'] ?? 'orders';

// Handle profile update
$profileMsg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name) {
        $pdo->prepare("UPDATE users SET name=?,phone=?,address=? WHERE id=?")
            ->execute([$name,$phone,$address,$uid]);
        $_SESSION['user']['name'] = $name;
        $profileMsg = 'Profile updated successfully!';
    }
}

// Fetch orders
$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$orders->execute([$uid]);
$orders = $orders->fetchAll();

// Fetch profile
$profile = $pdo->prepare("SELECT * FROM users WHERE id=?");
$profile->execute([$uid]);
$profile = $profile->fetch();

// Fetch downloadable items (digital products in delivered/processing orders)
$downloads = $pdo->prepare("
    SELECT oi.*, p.name AS pname, dp.file_path, dp.file_name, o.status, o.id AS oid
    FROM order_items oi
    JOIN orders o ON o.id=oi.order_id
    JOIN products p ON p.id=oi.product_id
    JOIN digital_products dp ON dp.product_id=p.id
    WHERE o.user_id=? AND o.status != 'cancelled'
    ORDER BY o.created_at DESC
");
$downloads->execute([$uid]);
$downloads = $downloads->fetchAll();

$pageTitle = 'My Account';
require_once 'includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>My Account</h1>
        <nav class="breadcrumb">
            <a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>Dashboard</span>
        </nav>
    </div>
</div>

<div class="container" style="padding-bottom:72px;">
<div class="dashboard-grid">

    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div style="padding:22px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
            <div style="width:46px;height:46px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0;">
                <?= strtoupper($profile['name'][0]) ?>
            </div>
            <div>
                <div style="font-weight:600;font-size:15px;color:var(--dark);"><?= e($profile['name']) ?></div>
                <div style="font-size:12.5px;color:var(--grey);"><?= ucfirst($profile['role']) ?></div>
            </div>
        </div>
        <nav class="dash-nav">
            <a href="?tab=orders"    class="<?= $tab==='orders'?'active':'' ?>"><i class="fas fa-box"></i> My Orders</a>
            <a href="?tab=downloads" class="<?= $tab==='downloads'?'active':'' ?>"><i class="fas fa-download"></i> Downloads</a>
            <a href="?tab=profile"   class="<?= $tab==='profile'?'active':'' ?>"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <div class="divider"></div>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="dash-content">

        <?php if ($tab==='orders'): ?>
        <div class="dash-card">
            <h3><i class="fas fa-box" style="color:var(--primary)"></i> Order History</h3>
            <?php if (empty($orders)): ?>
            <div class="empty-state"><i class="fas fa-box-open"></i><h3>No orders yet</h3><p>Start shopping to see your orders here.</p><a href="shop.php" class="btn btn-primary mt-2">Shop Now</a></div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead><tr>
                    <th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th>
                </tr></thead>
                <tbody>
                <?php foreach ($orders as $o):
                    $oi = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id=?");
                    $oi->execute([$o['id']]);
                    $itemCount = $oi->fetchColumn();
                ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= date('j M Y', strtotime($o['created_at'])) ?></td>
                    <td><?= $itemCount ?> item<?= $itemCount!=1?'s':'' ?></td>
                    <td><strong>£<?= number_format($o['total'],2) ?></strong></td>
                    <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($tab==='downloads'): ?>
        <div class="dash-card">
            <h3><i class="fas fa-download" style="color:var(--primary)"></i> Digital Downloads</h3>
            <?php if (empty($downloads)): ?>
            <div class="empty-state"><i class="fas fa-file-download"></i><h3>No downloads available</h3><p>Digital products you purchase will appear here.</p></div>
            <?php else: ?>
            <?php foreach ($downloads as $dl): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--bg);border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:10px;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <i class="fas fa-file-pdf" style="font-size:28px;color:var(--danger);"></i>
                    <div>
                        <div style="font-weight:600;color:var(--dark);font-size:14px;"><?= e($dl['pname']) ?></div>
                        <div style="font-size:12.5px;color:var(--grey);">Order #<?= $dl['oid'] ?></div>
                    </div>
                </div>
                <a href="download.php?id=<?= $dl['product_id'] ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($tab==='profile'): ?>
        <div class="dash-card">
            <h3><i class="fas fa-user-edit" style="color:var(--primary)"></i> Edit Profile</h3>
            <?php if ($profileMsg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($profileMsg) ?></div>
            <?php endif; ?>
            <form method="POST" style="max-width:480px;">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($profile['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" value="<?= e($profile['email']) ?>" disabled>
                    <small style="color:var(--grey);font-size:12.5px;">Email cannot be changed.</small>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($profile['phone']??'') ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= e($profile['address']??'') ?></textarea>
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    <span style="font-size:13px;color:var(--grey);">Role: <strong><?= ucfirst($profile['role']) ?></strong></span>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
