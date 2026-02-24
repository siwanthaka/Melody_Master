<?php
// admin/manage_products.php
require_once '../includes/auth.php';
requireAdmin();
$pdo = getDB();

$msg = ''; $error = '';

// DELETE
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$did]);
    $msg = 'Product deleted.';
}

// SAVE (add / edit)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pid   = (int)($_POST['id'] ?? 0);
    $catId = (int)$_POST['category_id'];
    $name  = trim($_POST['name']  ?? '');
    $slug  = strtolower(preg_replace('/[^a-z0-9]+/i','-',trim($_POST['slug']??$name)));
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $brand = trim($_POST['brand'] ?? '');
    $isDigital = isset($_POST['is_digital']) ? 1 : 0;
    $featured  = isset($_POST['featured'])   ? 1 : 0;
    $fileData  = trim($_POST['digital_file_path'] ?? '');
    $fileName  = trim($_POST['digital_file_name'] ?? '');

    // Handle image upload
    $imgName = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = 'prod_' . $slug . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
            $imgName = $newFilename;
        }
    }

    if (!$name || !$catId || $price < 0) {
        $error = 'Name, category and price are required.';
    } else {
        if ($pid) {
            $pdo->prepare("UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,stock=?,brand=?,image=?,is_digital=?,featured=? WHERE id=?")
                ->execute([$catId,$name,$slug,$desc,$price,$stock,$brand,$imgName,$isDigital,$featured,$pid]);
        } else {
            $pdo->prepare("INSERT INTO products (category_id,name,slug,description,price,stock,brand,image,is_digital,featured) VALUES(?,?,?,?,?,?,?,?,?,?)")
                ->execute([$catId,$name,$slug,$desc,$price,$stock,$brand,$imgName,$isDigital,$featured]);
            $pid = $pdo->lastInsertId();
        }
        // Digital file info
        if ($isDigital && $fileData && $fileName) {
            $existing = $pdo->prepare("SELECT id FROM digital_products WHERE product_id=?");
            $existing->execute([$pid]);
            if ($existing->fetch()) {
                $pdo->prepare("UPDATE digital_products SET file_path=?,file_name=? WHERE product_id=?")
                    ->execute([$fileData,$fileName,$pid]);
            } else {
                $pdo->prepare("INSERT INTO digital_products (product_id,file_path,file_name) VALUES(?,?,?)")
                    ->execute([$pid,$fileData,$fileName]);
            }
        }
        $msg = 'Product saved successfully!';
        header("Location: manage_products.php?saved=1");
        exit;
    }
}

if (isset($_GET['saved'])) $msg = 'Product saved successfully!';

// Edit mode
$edit = null;
$editDigital = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $s = $pdo->prepare("SELECT * FROM products WHERE id=?"); $s->execute([$eid]);
    $edit = $s->fetch();
    if ($edit && $edit['is_digital']) {
        $s2 = $pdo->prepare("SELECT * FROM digital_products WHERE product_id=?"); $s2->execute([$eid]);
        $editDigital = $s2->fetch();
    }
}

// All products
$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE p.name LIKE '%".addslashes($search)."%' OR p.brand LIKE '%".addslashes($search)."%'" : '';
$products = $pdo->query("SELECT p.*,c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id $where ORDER BY p.created_at DESC")->fetchAll();
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manage Products | Melody Masters</title>
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
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>

<!-- ADD/EDIT FORM -->
<div class="admin-table-card" style="margin-bottom:28px;">
    <div class="admin-table-header">
        <h3><?= $edit ? 'Edit Product #'.$edit['id'] : 'Add New Product' ?></h3>
        <?php if ($edit): ?><a href="manage_products.php" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Add New</a><?php endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" style="padding:22px;">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        <input type="hidden" name="current_image" value="<?= e($edit['image']??'') ?>">
        <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e($edit['name']??'') ?>" required>
            </div>
            <div class="form-group">
                <label>Slug (URL)</label>
                <input type="text" name="slug" class="form-control" value="<?= e($edit['slug']??'') ?>" placeholder="auto-generated">
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select…</option>
                    <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($edit['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Price (£) *</label>
                <input type="number" name="price" class="form-control" value="<?= $edit['price']??'' ?>" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control" value="<?= $edit['stock']??0 ?>" min="0">
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" class="form-control" value="<?= e($edit['brand']??'') ?>">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($edit['description']??'') ?></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_digital" <?= (!empty($edit['is_digital']))?'checked':'' ?> id="isDigitalCb">
                    Digital Product (no shipping, instant download)
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="featured" <?= (!empty($edit['featured']))?'checked':'' ?>>
                    Featured on Homepage
                </label>
            </div>
            <div class="form-group">
                <label>Product Image</label>
                <?php if (!empty($edit['image'])): ?>
                    <div style="margin-bottom:8px;">
                        <img src="../assets/images/<?= e($edit['image']) ?>" alt="" style="height:50px; border-radius:4px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
        </div>
        <!-- Digital fields -->
        <div id="digitalFields" style="<?= empty($edit['is_digital'])?'display:none':'' ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>File Path (relative)</label>
                    <input type="text" name="digital_file_path" class="form-control" value="<?= e($editDigital['file_path']??'') ?>" placeholder="downloads/file.pdf">
                </div>
                <div class="form-group">
                    <label>Download Filename</label>
                    <input type="text" name="digital_file_name" class="form-control" value="<?= e($editDigital['file_name']??'') ?>" placeholder="Product_File.pdf">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $edit?'Update Product':'Add Product' ?></button>
    </form>
</div>

<!-- PRODUCT LIST -->
<div class="admin-table-card">
    <div class="admin-table-header">
        <h3>All Products (<?= count($products) ?>)</h3>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search…" style="width:220px;">
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($search): ?><a href="manage_products.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
        </form>
    </div>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Type</th><th>Featured</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><strong><?= e($p['name']) ?></strong><br><small style="color:var(--grey)"><?= e($p['brand']??'') ?></small></td>
            <td><?= e($p['cat_name']) ?></td>
            <td>£<?= number_format($p['price'],2) ?></td>
            <td>
                <?php if ($p['is_digital']): ?>
                <span class="badge badge-info">∞</span>
                <?php elseif ($p['stock']==0): ?>
                <span class="badge badge-danger">0</span>
                <?php elseif ($p['stock']<=5): ?>
                <span class="badge badge-warning"><?= $p['stock'] ?></span>
                <?php else: ?>
                <span class="badge badge-success"><?= $p['stock'] ?></span>
                <?php endif; ?>
            </td>
            <td><?= $p['is_digital']?'<span class="badge badge-info"><i class="fas fa-download"></i> Digital</span>':'Physical' ?></td>
            <td><?= $p['featured']?'<i class="fas fa-star" style="color:var(--warning)"></i>':'<i class="fas fa-star" style="color:var(--border)"></i>' ?></td>
            <td>
                <div class="action-btns">
                    <a href="manage_products.php?edit=<?= $p['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                    <a href="manage_products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
</div>
</div>
<script>
document.getElementById('isDigitalCb').addEventListener('change', function() {
    document.getElementById('digitalFields').style.display = this.checked ? '' : 'none';
});
</script>
</body>
</html>
