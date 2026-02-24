<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$pdo = getDB();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$user = getUser();
$cartCount = cartCount();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? SITE_NAME) ?> | Melody Masters</title>
    <meta name="description" content="<?= e($pageDesc ?? 'Melody Masters - Your premier online music instrument shop. Guitars, keyboards, drums and more.') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <span><i class="fas fa-phone"></i> +44 207 946 0123</span>
        <span><i class="fas fa-envelope"></i> hello@melodymasters.co.uk</span>
        <span class="ms-auto"><i class="fas fa-truck"></i> Free UK shipping on orders over £100</span>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>index.php" class="logo">
            <i class="fas fa-music"></i>
            <span>Melody<strong>Masters</strong></span>
        </a>

        <!-- Nav Links -->
        <ul class="nav-links" id="navLinks">
            <li><a href="<?= BASE_URL ?>index.php" class="<?= $currentPage==='index'?'active':'' ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>shop.php" class="<?= $currentPage==='shop'?'active':'' ?>">Shop</a></li>
            <li class="dropdown">
                <a href="#" class="<?= $currentPage==='shop'&&isset($_GET['cat'])?'active':'' ?>">
                    Categories <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="<?= BASE_URL ?>shop.php?cat=<?= e($cat['slug']) ?>">
                            <i class="fas fa-angle-right"></i> <?= e($cat['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        </ul>

        <!-- Nav Actions -->
        <div class="nav-actions">
            <!-- Cart -->
            <a href="<?= BASE_URL ?>cart.php" class="cart-icon" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <!-- User Menu -->
            <?php if ($user): ?>
            <div class="user-dropdown">
                <button class="btn-user">
                    <i class="fas fa-user-circle"></i>
                    <span><?= e(explode(' ', $user['name'])[0]) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="user-menu">
                    <li><a href="<?= BASE_URL ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> My Account</a></li>
                    <?php if (isAdmin()): ?>
                    <li><a href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="divider"></li>
                    <li><a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
            <?php else: ?>
            <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-nav">Login</a>
            <a href="<?= BASE_URL ?>register.php" class="btn btn-primary-nav">Register</a>
            <?php endif; ?>

            <!-- Mobile Toggle -->
            <button class="nav-toggle" id="navToggle" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php if (isset($_SESSION['success'])): ?>
<div class="flash flash-success"><i class="fas fa-check-circle"></i> <?= e($_SESSION['success']) ?></div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<div class="flash flash-error"><i class="fas fa-exclamation-circle"></i> <?= e($_SESSION['error']) ?></div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<main>
