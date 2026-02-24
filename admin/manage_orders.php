<?php
// admin/manage_orders.php
require_once '../includes/auth.php';
requireAdmin();
$pdo = getDB();

$msg = '';

// Update order status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? 'pending';
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,$oid]);
        $msg = "Order #$oid status updated to ".ucfirst($status).".";
    }
}

$filter = $_GET['status'] ?? '';
$where  = $filter ? "WHERE o.status='".addslashes($filter)."'" : '';

$orders = $pdo->query("SELECT o.*,u.name AS uname,u.email FROM orders o JOIN users u ON u.id=o.user_id $where ORDER BY o.created_at DESC")->fetchAll();

// View single order
$viewOrder = null; $viewItems = [];
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT o.*,u.name AS uname,u.email,u.phone AS uphne FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
    $s->execute([(int)$_GET['edit']]);
    $viewOrder = $s->fetch();
    if ($viewOrder) {
        $si = $pdo->prepare("SELECT oi.*,p.name AS pname FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
        $si->execute([$viewOrder['id']]);
        $viewItems = $si->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manage Orders | Melody Masters</title>
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
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div><?php endif; ?>

<!-- Order Detail Modal-style View -->
<?php if ($viewOrder): ?>
<div class="admin-table-card" style="margin-bottom:24px;">
    <div class="admin-table-header">
        <h3>Order #<?= $viewOrder['id'] ?> Details</h3>
        <a href="manage_orders.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
    <div style="padding:22px;display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div>
            <h4 style="color:var(--primary);margin-bottom:10px;">Customer</h4>
            <p><strong><?= e($viewOrder['uname']) ?></strong><br>
            <?= e($viewOrder['email']) ?></p>
            <h4 style="color:var(--primary);margin:14px 0 10px;">Shipping Address</h4>
            <p><?= e($viewOrder['shipping_name']) ?><br>
            <?= e($viewOrder['shipping_address']) ?><br>
            <?= e($viewOrder['shipping_city']) ?>, <?= e($viewOrder['shipping_postcode']) ?><br>
            <?= e($viewOrder['shipping_phone']) ?></p>
        </div>
        <div>
            <h4 style="color:var(--primary);margin-bottom:10px;">Update Status</h4>
            <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;">
                <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                <select name="status" class="form-control" style="width:auto;">
                    <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $viewOrder['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </form>
            <h4 style="color:var(--primary);margin:14px 0 10px;">Summary</h4>
            <p>Date: <?= date('j M Y H:i', strtotime($viewOrder['created_at'])) ?><br>
            Subtotal: £<?= number_format($viewOrder['total']-$viewOrder['shipping'],2) ?><br>
            Shipping: £<?= number_format($viewOrder['shipping'],2) ?><br>
            <strong>Total: £<?= number_format($viewOrder['total'],2) ?></strong></p>
        </div>
    </div>
    <div style="padding:0 22px 22px;">
        <h4 style="color:var(--primary);margin-bottom:12px;">Items</h4>
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($viewItems as $i): ?>
            <tr>
                <td><?= e($i['pname']) ?></td>
                <td>×<?= $i['quantity'] ?></td>
                <td>£<?= number_format($i['price'],2) ?></td>
                <td>£<?= number_format($i['price']*$i['quantity'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
    <?php foreach ([''=> 'All','pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
    <a href="manage_orders.php?status=<?= $val ?>" class="btn btn-sm <?= $filter===$val?'btn-primary':'btn-outline' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
</div>

<!-- Orders Table -->
<div class="admin-table-card">
    <div class="admin-table-header"><h3>Orders (<?= count($orders) ?>)</h3></div>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Shipping</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--grey);">No orders found</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><strong>#<?= $o['id'] ?></strong></td>
            <td>
                <div style="font-weight:500"><?= e($o['uname']) ?></div>
                <div style="font-size:12px;color:var(--grey)"><?= e($o['email']) ?></div>
            </td>
            <td>£<?= number_format($o['total'],2) ?></td>
            <td><?= $o['shipping']>0 ? '£'.number_format($o['shipping'],2) : '<span style="color:var(--success)">Free</span>' ?></td>
            <td><?= date('j M Y', strtotime($o['created_at'])) ?></td>
            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td><a href="?edit=<?= $o['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
</div>
</div>
</body>
</html>
