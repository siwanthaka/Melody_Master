<?php
$pageTitle = 'Home';
$pageDesc  = 'Melody Masters – Your premier online music instrument shop. Guitars, keyboards, drums and more.';
require_once 'includes/header.php';

// Featured products
$featured = $pdo->query("SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.featured = 1 LIMIT 8")->fetchAll();
// All categories
$allCats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$catIcons = [
    'guitars'    => 'fas fa-guitar',
    'keyboards'  => 'fas fa-keyboard',
    'drums'      => 'fas fa-drum',
    'wind-brass' => 'fas fa-wind',
    'studio-dj'  => 'fas fa-headphones',
    'sheet-music'=> 'fas fa-music',
];
?>

<!-- ======== HERO ======== -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Find Your Perfect <span>Sound</span> Today</h1>
            <p>Browse our wide range of musical instruments, studio gear and digital downloads. From beginner to professional — we have everything you need.</p>
            <div class="hero-btns">
                <a href="shop.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
                <a href="shop.php?cat=sheet-music" class="btn btn-outline btn-lg">
                    <i class="fas fa-download"></i> Digital Downloads
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="num">500+</div>
                    <div class="lbl">Products</div>
                </div>
                <div class="hero-stat">
                    <div class="num">50+</div>
                    <div class="lbl">Top Brands</div>
                </div>
                <div class="hero-stat">
                    <div class="num">10K+</div>
                    <div class="lbl">Happy Musicians</div>
                </div>
                <div class="hero-stat">
                    <div class="num">Free</div>
                    <div class="lbl">Shipping £100+</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======== CATEGORIES ======== -->
<section style="padding: 40px 0; background: #fff; border-bottom: 1px solid #e0e0e0;">
    <div class="container">
        <div class="section-header center">
            <div class="section-divider"></div>
            <h2>Shop by Category</h2>
            <p>Browse our wide selection across all major instrument families and studio gear.</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($allCats as $cat): 
                $icon = $catIcons[$cat['slug']] ?? 'fas fa-music';
                $imgPath = !empty($cat['image']) ? 'assets/images/' . $cat['image'] : '';
                $hasImage = !empty($imgPath) && file_exists($imgPath);
            ?>
            <a href="shop.php?cat=<?= e($cat['slug']) ?>" class="category-card">
                <?php if ($hasImage): ?>
                    <div class="cat-img">
                        <img src="<?= e($imgPath) ?>" alt="<?= e($cat['name']) ?>">
                    </div>
                <?php else: ?>
                    <div class="cat-icon"><i class="<?= $icon ?>"></i></div>
                <?php endif; ?>
                <h3><?= e($cat['name']) ?></h3>
                <p><?= e(mb_strimwidth($cat['description'], 0, 45, '…')) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======== FEATURED PRODUCTS ======== -->
<section style="padding: 40px 0; background: #f4f4f4;">
    <div class="container">
        <div class="section-header" style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <div class="section-divider"></div>
                <h2>Featured Instruments</h2>
                <p>Hand-picked favourites loved by our customers.</p>
            </div>
            <a href="shop.php" class="btn btn-outline">View All &rarr;</a>
        </div>
        <div class="products-grid">
            <?php foreach ($featured as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======== WHY US BANNER ======== -->
<div class="why-us-banner">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;text-align:center;">
            <?php $perks = [
                ['fas fa-truck',       'Free Shipping',    'On orders over £100'],
                ['fas fa-shield-alt',  'Secure Payment',   'SSL encrypted checkout'],
                ['fas fa-undo',        '30-Day Returns',   'Hassle-free returns'],
                ['fas fa-headset',     'Expert Support',   'Mon–Sat 9am–6pm'],
                ['fas fa-certificate', 'Genuine Products', 'Authorised UK dealer'],
            ]; foreach ($perks as $perk): ?>
            <div>
                <i class="<?= $perk[0] ?> perk-icon"></i>
                <h4><?= $perk[1] ?></h4>
                <p><?= $perk[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
