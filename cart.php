<?php
require_once 'includes/auth.php';
$pdo = getDB();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = (int)($_POST['qty']  ?? 1);

    if ($action === 'add' && $productId > 0) {
        // Check stock
        $stmt = $pdo->prepare("SELECT stock, is_digital, name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();
        if ($prod) {
            $currentQty = $_SESSION['cart'][$productId] ?? 0;
            if (!$prod['is_digital'] && ($currentQty + $qty) > $prod['stock']) {
                $_SESSION['error'] = "Sorry, only {$prod['stock']} units of \"{$prod['name']}\" available.";
            } else {
                addToCart($productId, $qty);
                $_SESSION['success'] = "\"" . $prod['name'] . "\" added to cart!";
            }
        }
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
        header("Location: $redirect");
        exit;
    }

    if ($action === 'update') {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $pid => $q) {
                $pid = (int)$pid; $q = (int)$q;
                if ($pid > 0) {
                    // Validate stock
                    $s = $pdo->prepare("SELECT stock, is_digital FROM products WHERE id=?");
                    $s->execute([$pid]);
                    $pr = $s->fetch();
                    if ($pr && !$pr['is_digital'] && $q > $pr['stock']) $q = $pr['stock'];
                    updateCartQty($pid, $q);
                }
            }
        }
        $_SESSION['success'] = 'Cart updated.';
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove' && $productId > 0) {
        removeFromCart($productId);
        $_SESSION['success'] = 'Item removed from cart.';
        header('Location: cart.php');
        exit;
    }
}

$pageTitle = 'Your Cart';
require_once 'includes/header.php';

$items    = getCartItems();
$subtotal = cartTotal();
$shipping = shippingCost();
$grandTotal = $subtotal + $shipping;
?>

<div class="page-banner">
    <div class="container">
        <h1>Your Shopping Cart</h1>
        <nav class="breadcrumb">
            <a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>Cart</span>
        </nav>
    </div>
</div>

<div class="container" style="padding-bottom:72px;">
    <?php if (empty($items)): ?>
    <div class="empty-state">
        <i class="fas fa-shopping-cart"></i>
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added anything yet.</p>
        <a href="shop.php" class="btn btn-primary mt-2"><i class="fas fa-shopping-bag"></i> Start Shopping</a>
    </div>
    <?php else: ?>
    <div style="display:grid; grid-template-columns:1fr 340px; gap:28px; align-items:start;">

        <!-- Cart Table -->
        <div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <div class="cart-table-wrap">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width:40%">Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item):
                                $p = $item['product']; ?>
                            <tr>
                                <td>
                                    <div class="cart-product-info">
                                        <div class="cart-product-img">
                                            <?php
                                            $id = (int)$p['id'];
                                            $brand = $p['brand'] ?? '';
                                            $name = $p['name'] ?? '';
                                            $imagePath = "assets/images/default.jpg"; // Default fallback

                                            // 1. Try Descriptive: "Brand Name.png"
                                            $descName = $brand . " " . $name . ".png";
                                            if (file_exists("assets/images/" . $descName)) {
                                                $imagePath = "assets/images/" . $descName;
                                            } 
                                            // 1b. Try Descriptive (Singular): "Brand Name (no 's').png"
                                            else if (substr($name, -1) === 's' && file_exists("assets/images/" . $brand . " " . rtrim($name, 's') . ".png")) {
                                                $imagePath = "assets/images/" . $brand . " " . rtrim($name, 's') . ".png";
                                            }
                                            // 2. Try Descriptive: "BrandName.png" (e.g. GibsonGibson)
                                            else if (file_exists("assets/images/" . $brand . $name . ".png")) {
                                                $imagePath = "assets/images/" . $brand . $name . ".png";
                                            }
                                            // 3. Try Product ID
                                            else if (file_exists("assets/images/{$id}.png")) {
                                                $imagePath = "assets/images/{$id}.png";
                                            }
                                            // 4. Try database filename
                                            else {
                                                $dbImg = $p['image'] ?? '';
                                                if ($dbImg && file_exists("assets/images/" . $dbImg)) {
                                                    $imagePath = "assets/images/" . $dbImg;
                                                }
                                            }
                                            ?>
                                            <img src="<?= $imagePath ?>" alt="<?= e($p['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                        <div>
                                            <div class="cart-product-name"><?= e($p['name']) ?></div>
                                            <div class="cart-product-brand"><?= e($p['brand'] ?? '') ?></div>
                                            <?php if ($p['is_digital']): ?>
                                            <span class="badge badge-info" style="margin-top:4px;font-size:11px;"><i class="fas fa-download"></i> Digital</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>£<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <?php if ($p['is_digital']): ?>
                                    <input type="hidden" name="quantities[<?= $p['id'] ?>]" value="1">
                                    <span class="badge badge-info">×1</span>
                                    <?php else: ?>
                                    <input type="number" name="quantities[<?= $p['id'] ?>]" value="<?= $item['quantity'] ?>"
                                           min="0" max="<?= $p['stock'] ?>" class="qty-input-sm">
                                    <?php endif; ?>
                                </td>
                                <td><strong>£<?= number_format($item['subtotal'], 2) ?></strong></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Remove"
                                            onclick="return confirm('Remove this item?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-dark"><i class="fas fa-sync"></i> Update Cart</button>
                    <a href="shop.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="cart-sidebar">
            <h3>Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal</span><span>£<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span><?= $shipping > 0 ? '£'.number_format($shipping,2) : '<span style="color:var(--success)">FREE</span>' ?></span>
            </div>
            <?php if ($shipping > 0 && !hasPhysicalItems() === false): ?>
            <?php $diff = FREE_SHIPPING_THRESHOLD - $subtotal; if ($diff > 0): ?>
            <div class="shipping-note">
                <i class="fas fa-truck"></i> Add £<?= number_format($diff,2) ?> more for free shipping!
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <div class="summary-row total">
                <span>Total</span><span>£<?= number_format($grandTotal, 2) ?></span>
            </div>
            <a href="checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top:18px;">
                <i class="fas fa-lock"></i> Proceed to Checkout
            </a>
            <div style="display:flex;justify-content:center;gap:14px;margin-top:16px;font-size:22px;color:var(--grey-light);">
                <i class="fab fa-cc-visa"></i><i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-paypal"></i><i class="fas fa-shield-alt"></i>
            </div>
            <p style="text-align:center;font-size:12px;color:var(--grey);margin-top:8px;"><i class="fas fa-lock"></i> Secure SSL checkout</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
