<?php
require_once 'includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: shop.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Location: shop.php'); exit; }

// Reviews
$reviews = $pdo->prepare("SELECT r.*, u.name AS username FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.created_at DESC");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
$avgRating = $reviews ? round(array_sum(array_column($reviews,'rating'))/count($reviews),1) : 0;

// Handle review submission
$reviewError = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to leave a review.';
        header("Location: login.php?redirect=product.php?id=$id");
        exit;
    }
    $uid = $_SESSION['user_id'];
    if (!userHasPurchased($uid, $id)) {
        $reviewError = 'You must purchase this product before reviewing it.';
    } else {
        // Check already reviewed
        $s = $pdo->prepare("SELECT id FROM reviews WHERE product_id=? AND user_id=?");
        $s->execute([$id,$uid]);
        if ($s->fetch()) {
            $reviewError = 'You have already reviewed this product.';
        } else {
            $rating  = max(1,min(5,(int)$_POST['rating']));
            $comment = trim($_POST['comment']);
            $pdo->prepare("INSERT INTO reviews (product_id,user_id,rating,comment) VALUES(?,?,?,?)")
                ->execute([$id,$uid,$rating,$comment]);
            $_SESSION['success'] = 'Review submitted! Thank you.';
            header("Location: product.php?id=$id");
            exit;
        }
    }
}

$pageTitle = $product['name'];
require_once 'includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><?= e($product['name']) ?></h1>
        <nav class="breadcrumb">
            <a href="index.php">Home</a><i class="fas fa-chevron-right"></i>
            <a href="shop.php">Shop</a><i class="fas fa-chevron-right"></i>
            <a href="shop.php?cat=<?= e($product['cat_slug']) ?>"><?= e($product['cat_name']) ?></a>
            <i class="fas fa-chevron-right"></i><span><?= e($product['name']) ?></span>
        </nav>
    </div>
</div>

<div class="container" style="padding-bottom:72px;">
    <div class="product-detail-grid">
        <!-- Image -->
        <?php
        $id = (int)$product['id'];
        $brand = $product['brand'] ?? '';
        $name = $product['name'] ?? '';
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
            $dbImg = $product['image'] ?? '';
            if ($dbImg && file_exists("assets/images/" . $dbImg)) {
                $imagePath = "assets/images/" . $dbImg;
            }
        }
        ?>

        <div class="product-main-img">
    <img src="<?= $imagePath ?>"
         alt="<?= e($product['name']) ?>"
         style="width:100%; max-height:400px; object-fit:cover; border-radius:8px;">
</div>

        <!-- Info -->
        <div class="product-info">
            <p style="color:var(--primary);font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">
                <?= e($product['brand'] ?? '') ?>
            </p>
            <h1><?= e($product['name']) ?></h1>

            <!-- Rating -->
            <div style="display:flex;align-items:center;gap:10px;margin:10px 0 14px;">
                <div class="star-rating">
                    <?php for($i=1;$i<=5;$i++): ?>
                    <i class="fa<?= $i<=$avgRating?'s':'r' ?> fa-star"></i>
                    <?php endfor; ?>
                </div>
                <span style="font-size:13.5px;color:var(--grey);"><?= $avgRating ?>/5 (<?= count($reviews) ?> review<?= count($reviews)!=1?'s':'' ?>)</span>
            </div>

            <div class="product-price">£<?= number_format($product['price'],2) ?></div>

            <!-- Meta -->
            <table class="product-meta-table">
                <tr><td>Category</td><td><a href="shop.php?cat=<?= e($product['cat_slug']) ?>"><?= e($product['cat_name']) ?></a></td></tr>
                <tr><td>Brand</td><td><?= e($product['brand'] ?? 'N/A') ?></td></tr>
                <?php if ($product['is_digital']): ?>
                <tr><td>Type</td><td><span class="badge badge-info"><i class="fas fa-download"></i> Digital Download</span></td></tr>
                <?php else: ?>
                <tr><td>Availability</td><td>
                    <?php if ($product['stock']>3): ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i> In Stock (<?= $product['stock'] ?>)</span>
                    <?php elseif ($product['stock']>0): ?>
                    <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Low Stock – only <?= $product['stock'] ?> left</span>
                    <?php else: ?>
                    <span class="badge badge-danger"><i class="fas fa-times"></i> Out of Stock</span>
                    <?php endif; ?>
                </td></tr>
                <tr><td>Shipping</td><td>
                    <?php if ($product['price'] >= FREE_SHIPPING_THRESHOLD): ?>
                    <span class="in-stock"><i class="fas fa-truck"></i> Free Shipping</span>
                    <?php else: ?>
                    <span>£<?= number_format(SHIPPING_COST,2) ?> or free on orders over £<?= FREE_SHIPPING_THRESHOLD ?></span>
                    <?php endif; ?>
                </td></tr>
                <?php endif; ?>
            </table>

            <!-- Description -->
            <p style="color:var(--grey);font-size:14px;line-height:1.75;margin-bottom:24px;"><?= e($product['description']) ?></p>

            <!-- Add to Cart -->
            <?php if ($product['stock']>0 || $product['is_digital']): ?>
            <form method="POST" action="cart.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <?php if (!$product['is_digital']): ?>
                <div class="qty-selector">
                    <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                    <input type="number" name="qty" value="1" min="1" max="<?= $product['stock'] ?>" class="qty-input" id="qtyField">
                    <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                </div>
                <?php else: ?>
                <input type="hidden" name="qty" value="1">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-<?= $product['is_digital']?'download':'cart-plus' ?>"></i>
                    <?= $product['is_digital']?'Buy & Download':'Add to Cart' ?>
                </button>
            </form>
            <?php else: ?>
            <button class="btn btn-dark btn-lg" disabled><i class="fas fa-ban"></i> Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== REVIEWS ===== -->
    <div class="reviews-section">
        <h2 style="font-size:1.5rem;font-weight:700;color:var(--dark);margin-bottom:28px;">
            Customer Reviews
            <?php if ($reviews): ?><span style="font-size:1rem;color:var(--grey);font-weight:400;">(<?= count($reviews) ?>)</span><?php endif; ?>
        </h2>

        <?php if (empty($reviews)): ?>
        <div class="empty-state"><i class="fas fa-star"></i><h3>No reviews yet</h3><p>Be the first to review this product!</p></div>
        <?php else: ?>
        <?php foreach ($reviews as $r): ?>
        <div class="review-card">
            <div class="review-header">
                <div>
                    <div class="reviewer-name"><?= e($r['username']) ?></div>
                    <div class="star-rating" style="font-size:13px;">
                        <?php for($i=1;$i<=5;$i++): ?>
                        <i class="fa<?= $i<=$r['rating']?'s':'r' ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="review-date"><?= date('j M Y', strtotime($r['created_at'])) ?></div>
            </div>
            <?php if ($r['comment']): ?>
            <p style="font-size:14px;color:var(--dark-3);margin-top:8px;"><?= e($r['comment']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Review Form -->
        <div class="review-form">
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">Write a Review</h3>
            <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info"><i class="fas fa-info-circle"></i> <a href="login.php">Login</a> to leave a review.</div>
            <?php elseif ($reviewError): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($reviewError) ?></div>
            <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Rating</label>
                    <div style="display:flex;gap:8px;">
                        <?php for($i=1;$i<=5;$i++): ?>
                        <label style="cursor:pointer;font-size:24px;color:var(--warning);">
                            <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;">
                            <i class="far fa-star" id="star<?= $i ?>"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience with this product…"></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Review</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeQty(delta) {
    const input = document.getElementById('qtyField');
    if (!input) return;
    const max = parseInt(input.max) || 999;
    input.value = Math.max(1, Math.min(max, parseInt(input.value)+delta));
}
// Star hover
document.querySelectorAll('input[name="rating"]').forEach((radio,i,radios) => {
    radio.parentElement.addEventListener('mouseenter', () => {
        radios.forEach((r,j) => r.parentElement.querySelector('i').className = j<=i?'fas fa-star':'far fa-star');
    });
    radio.parentElement.addEventListener('click', () => {
        radio.checked = true;
    });
    radio.parentElement.parentElement.addEventListener('mouseleave', () => {
        const checked = [...radios].findIndex(r=>r.checked);
        radios.forEach((r,j) => r.parentElement.querySelector('i').className = j<=checked?'fas fa-star':'far fa-star');
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>
