<?php
require_once 'includes/auth.php';
requireLogin('login.php?redirect=checkout.php');
$pdo = getDB();

// Show order confirmation FIRST (cart is already cleared at this point)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $oid = (int)($_GET['order'] ?? 0);
    $pageTitle = 'Order Confirmed';
    require_once 'includes/header.php';
    ?>
    <div class="container">
    <div class="confirm-box">
        <div class="confirm-icon"><i class="fas fa-check-circle"></i></div>
        <h2>Order Confirmed!</h2>
        <p>Thank you for your purchase. Your order <strong>#<?= $oid ?></strong> has been placed successfully.</p>
        <p style="margin-bottom:8px;">You'll receive a confirmation shortly. Digital downloads are available in your <a href="dashboard.php">account dashboard</a>.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-user"></i> View My Orders</a>
            <a href="shop.php" class="btn btn-outline"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
        </div>
    </div>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$items = getCartItems();
if (empty($items)) { header('Location: cart.php'); exit; }

$subtotal   = cartTotal();
$shipping   = shippingCost();
$grandTotal = $subtotal + $shipping;

$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name     = trim($_POST['name']     ?? '');
    $address  = trim($_POST['address']  ?? '');
    $city     = trim($_POST['city']     ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $notes    = trim($_POST['notes']    ?? '');

    if (!$name)     $errors[] = 'Full name is required.';
    if (!$address)  $errors[] = 'Address is required.';
    if (!$city)     $errors[] = 'City is required.';
    if (!$postcode) $errors[] = 'Postcode is required.';
    if (!$phone)    $errors[] = 'Phone number is required.';

    // Stock check
    foreach ($items as $item) {
        $p = $item['product'];
        if (!$p['is_digital'] && $item['quantity'] > $p['stock']) {
            $errors[] = "Insufficient stock for \"{$p['name']}\". Only {$p['stock']} available.";
        }
    }

    if (empty($errors)) {
        $uid = $_SESSION['user_id'];
        $pdo->beginTransaction();
        try {
            // Create order
            $pdo->prepare("INSERT INTO orders (user_id,total,shipping,status,shipping_name,shipping_address,shipping_city,shipping_postcode,shipping_phone,notes) VALUES(?,?,?,'pending',?,?,?,?,?,?)")
                ->execute([$uid, $grandTotal, $shipping, $name, $address, $city, $postcode, $phone, $notes]);
            $orderId = $pdo->lastInsertId();

            // Order items + update stock
            foreach ($items as $item) {
                $p = $item['product'];
                $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES(?,?,?,?)")
                    ->execute([$orderId, $p['id'], $item['quantity'], $p['price']]);
                if (!$p['is_digital']) {
                    $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?")
                        ->execute([$item['quantity'], $p['id']]);
                }
            }
            $pdo->commit();
            $_SESSION['cart'] = [];
            header("Location: checkout.php?success=1&order=$orderId");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
require_once 'includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Checkout</h1>
        <nav class="breadcrumb">
            <a href="index.php">Home</a><i class="fas fa-chevron-right"></i>
            <a href="cart.php">Cart</a><i class="fas fa-chevron-right"></i>
            <span>Checkout</span>
        </nav>
    </div>
</div>

<div class="container" style="padding-bottom:72px;">
    <?php if ($errors): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
    <div class="checkout-grid">
        <!-- Shipping Form -->
        <div class="checkout-card">
            <h3><i class="fas fa-map-marker-alt" style="color:var(--primary)"></i> Shipping Details</h3>
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= e($_SESSION['user']['name']??'') ?>" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Street Address *</label>
                    <input type="text" name="address" class="form-control" placeholder="123 Music Street" required>
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Postcode *</label>
                    <input type="text" name="postcode" class="form-control" placeholder="SW1A 1AA" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+44 7700 000000" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Order Notes (optional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Any special instructions…"></textarea>
                </div>
            </div>

            <h3 style="margin-top:8px;"><i class="fas fa-credit-card" style="color:var(--primary)"></i> Payment</h3>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> This is a demo store. No real payment is processed.</div>
        </div>

        <!-- Order Summary -->
        <div>
            <div class="cart-sidebar">
                <h3>Order Summary</h3>
                <?php foreach ($items as $item): $p = $item['product']; ?>
                <div class="summary-row" style="font-size:13px;">
                    <span><?= e(mb_strimwidth($p['name'],0,32,'…')) ?> ×<?= $item['quantity'] ?></span>
                    <span>£<?= number_format($item['subtotal'],2) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="summary-row" style="border-top:1px solid var(--border);padding-top:10px;margin-top:10px;">
                    <span>Subtotal</span><span>£<?= number_format($subtotal,2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?= $shipping>0 ? '£'.number_format($shipping,2) : '<span style="color:var(--success)">FREE</span>' ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span><span>£<?= number_format($grandTotal,2) ?></span>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:18px;">
                    <i class="fas fa-lock"></i> Place Order
                </button>
            </div>
        </div>
    </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
