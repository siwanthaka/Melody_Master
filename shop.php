<?php
$pageTitle = 'Shop';
require_once 'includes/header.php';

// Filters
$catSlug     = $_GET['cat']   ?? '';
$brand       = $_GET['brand'] ?? '';
$minPrice    = isset($_GET['min']) ? (float)$_GET['min'] : 0;
$maxPrice    = isset($_GET['max']) ? (float)$_GET['max'] : 9999;
$sort        = $_GET['sort']  ?? 'created_at_desc';
$search      = trim($_GET['q'] ?? '');

// Build query
$where  = ['1=1'];
$params = [];

if ($catSlug) {
    $where[] = 'c.slug = ?'; $params[] = $catSlug;
}
if ($brand) {
    $where[] = 'p.brand = ?'; $params[] = $brand;
}
$where[] = 'p.price >= ?'; $params[] = $minPrice;
$where[] = 'p.price <= ?'; $params[] = $maxPrice;
if ($search) {
    $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$whereStr = implode(' AND ', $where);

$sortMap = [
    'price_asc'    => 'p.price ASC',
    'price_desc'   => 'p.price DESC',
    'name_asc'     => 'p.name ASC',
    'created_at_desc' => 'p.created_at DESC',
];
$orderBy = $sortMap[$sort] ?? 'p.created_at DESC';

// Pagination
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE $whereStr");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Brands for filter
$brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

// Current category info
$currentCat = null;
if ($catSlug) {
    $s = $pdo->prepare("SELECT * FROM categories WHERE slug=?");
    $s->execute([$catSlug]);
    $currentCat = $s->fetch();
}

function buildUrl(array $override = []): string {
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'shop.php?' . http_build_query($params);
}
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container">
        <h1><?= $currentCat ? e($currentCat['name']) : ($search ? 'Search: '.e($search) : 'All Products') ?></h1>
        <nav class="breadcrumb">
            <a href="index.php">Home</a><i class="fas fa-chevron-right"></i>
            <?php if ($currentCat): ?>
            <a href="shop.php">Shop</a><i class="fas fa-chevron-right"></i>
            <span><?= e($currentCat['name']) ?></span>
            <?php else: ?>
            <span>Shop</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div class="container" style="padding-bottom: 72px;">
    <div class="shop-layout">
        <!-- ===== PRODUCT GRID ===== -->
        <div>
            <div class="shop-header">
                <p class="shop-meta">Showing <strong><?= $total ?></strong> product<?= $total!=1?'s':'' ?>
                    <?php if ($currentCat): ?> in <strong><?= e($currentCat['name']) ?></strong><?php endif; ?>
                </p>
                <select class="sort-select" onchange="window.location='<?= buildUrl() ?>&sort='+this.value">
                    <option value="created_at_desc" <?= $sort==='created_at_desc'?'selected':'' ?>>Newest First</option>
                    <option value="price_asc"       <?= $sort==='price_asc'?'selected':'' ?>>Price: Low → High</option>
                    <option value="price_desc"      <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
                    <option value="name_asc"        <?= $sort==='name_asc'?'selected':'' ?>>Name A–Z</option>
                </select>
            </div>

            <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No products found</h3>
                <p>No products match your criteria.</p>
                <a href="shop.php" class="btn btn-primary mt-2">View All Products</a>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php 
                $idx = $offset + 1;
                foreach ($products as $p): 
                    include __DIR__ . '/includes/product_card.php'; 
                    $idx++;
                endforeach; 
                ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="<?= buildUrl(['page' => $i]) ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
