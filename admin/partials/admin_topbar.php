<?php
// admin/partials/admin_topbar.php
$titles = [
    'dashboard'          => 'Dashboard Overview',
    'manage_products'    => 'Manage Products',
    'manage_categories'  => 'Manage Categories',
    'manage_orders'      => 'Manage Orders',
    'manage_users'       => 'Manage Users',
];
$current = basename($_SERVER['PHP_SELF'], '.php');
$user    = getUser();
?>
<div class="admin-topbar">
    <h1><?= $titles[$current] ?? 'Admin' ?></h1>
    <div style="display:flex;align-items:center;gap:14px;">
        <span style="font-size:13.5px;color:var(--grey);">
            <i class="fas fa-user-circle" style="color:var(--primary)"></i>
            <?= e($user['name']??'Admin') ?> — <strong><?= ucfirst($user['role']??'') ?></strong>
        </span>
        <a href="../logout.php" class="btn btn-outline btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
