<?php
// admin/partials/admin_sidebar.php
$currentAdmin = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-logo">
        <a href="../index.php" class="logo logo-light">
            <i class="fas fa-music"></i>
            <span>Melody<strong>Masters</strong></span>
        </a>
        <div style="font-size:11px;color:rgba(255,255,255,.35);margin-top:4px;margin-left:32px;">Admin Panel</div>
    </div>

    <nav class="admin-nav">
        <div class="nav-section">Main</div>
        <a href="dashboard.php"        class="<?= $currentAdmin==='dashboard'?'active':'' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="nav-section">Catalogue</div>
        <a href="manage_products.php"   class="<?= $currentAdmin==='manage_products'?'active':'' ?>">
            <i class="fas fa-guitar"></i> Products
        </a>
        <a href="manage_categories.php" class="<?= $currentAdmin==='manage_categories'?'active':'' ?>">
            <i class="fas fa-tags"></i> Categories
        </a>

        <div class="nav-section">Store</div>
        <a href="manage_orders.php"    class="<?= $currentAdmin==='manage_orders'?'active':'' ?>">
            <i class="fas fa-shopping-bag"></i> Orders
        </a>
        <a href="manage_users.php"     class="<?= $currentAdmin==='manage_users'?'active':'' ?>">
            <i class="fas fa-users"></i> Users
        </a>

        <div class="nav-section" style="margin-top:auto;"></div>
        <a href="../index.php"><i class="fas fa-store"></i> View Store</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
