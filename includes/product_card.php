<?php
// includes/product_card.php
// expects $p = product row

$stockBadge = '';
if ($p['is_digital']) {
    $stockBadge = '<span class="product-badge digital"><i class="fas fa-download"></i> Digital</span>';
} elseif ($p['stock'] == 0) {
    $stockBadge = '<span class="product-badge" style="background:var(--danger)">Out of Stock</span>';
} elseif ($p['stock'] <= 3) {
    $stockBadge = '<span class="product-badge low">Low Stock</span>';
} else {
    $stockBadge = '<span class="product-badge">In Stock</span>';
}

// Image resolution: Prioritize Descriptive filenames, then Grid/ID
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
// 3. Try Grid Index (if passed from shop.php)
else if (isset($idx) && file_exists("assets/images/{$idx}.png")) {
    $imagePath = "assets/images/{$idx}.png";
}
// 4. Try Product ID
else if (file_exists("assets/images/{$id}.png")) {
    $imagePath = "assets/images/{$id}.png";
}
// 5. Try database filename
else {
    $dbImg = $p['image'] ?? '';
    if ($dbImg && file_exists("assets/images/" . $dbImg)) {
        $imagePath = "assets/images/" . $dbImg;
    }
}
?>

<div class="product-card">
    <a href="product.php?id=<?= (int)$p['id'] ?>">
        <div class="product-card-img">
            <?= $stockBadge ?>
            <img src="<?= $imagePath ?>"
                 alt="<?= e($p['name']) ?>"
                 style="width:100%; height:200px; object-fit:cover;">
        </div>
    </a>

    <div class="product-card-body">
        <div class="product-brand"><?= e($p['brand'] ?? '') ?></div>

        <div class="product-name">
            <a href="product.php?id=<?= (int)$p['id'] ?>">
                <?= e($p['name']) ?>
            </a>
        </div>

        <div class="product-price">
            £<?= number_format($p['price'], 2) ?>
        </div>

        <?php if ($p['stock'] > 0 || $p['is_digital']): ?>
        <form method="POST" action="cart.php">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="qty" value="1">
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
        </form>
        <?php else: ?>
        <button class="btn btn-dark btn-block" disabled>Out of Stock</button>
        <?php endif; ?>
    </div>
</div>