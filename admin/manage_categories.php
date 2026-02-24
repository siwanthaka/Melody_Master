<?php
// admin/manage_categories.php
require_once '../includes/auth.php';
requireAdmin();
$pdo = getDB();

$msg = ''; $error = '';

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Check if products exist
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
    $cnt->execute([$did]);
    if ($cnt->fetchColumn() > 0) {
        $error = 'Cannot delete: products exist in this category.';
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$did]);
        $msg = 'Category deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $cid  = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',trim($_POST['slug']??$name)));
    $desc = trim($_POST['description'] ?? '');
    
    // Handle image upload
    $imgName = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = 'cat_' . $slug . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
            $imgName = $newFilename;
        }
    }

    if (!$name) { $error = 'Category name is required.'; }
    else {
        if ($cid) {
            $pdo->prepare("UPDATE categories SET name=?,slug=?,description=?,image=? WHERE id=?")
                ->execute([$name,$slug,$desc,$imgName,$cid]);
        } else {
            $pdo->prepare("INSERT INTO categories (name,slug,description,image) VALUES(?,?,?,?)")
                ->execute([$name,$slug,$desc,$imgName]);
        }
        $msg = 'Category saved!';
        header("Location: manage_categories.php?saved=1"); exit;
    }
}

if (isset($_GET['saved'])) $msg = 'Category saved!';

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) AS product_count FROM categories c ORDER BY c.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manage Categories | Melody Masters</title>
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <!-- Form -->
    <div class="admin-table-card">
        <div class="admin-table-header">
            <h3><?= $edit ? 'Edit Category' : 'Add Category' ?></h3>
            <?php if ($edit): ?><a href="manage_categories.php" class="btn btn-outline btn-sm">Add New</a><?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding:22px;">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
            <input type="hidden" name="current_image" value="<?= e($edit['image']??'') ?>">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e($edit['name']??'') ?>" required>
            </div>
            <div class="form-group">
                <label>Slug (URL-friendly)</label>
                <input type="text" name="slug" class="form-control" value="<?= e($edit['slug']??'') ?>" placeholder="auto-generated">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($edit['description']??'') ?></textarea>
            </div>
            <div class="form-group">
                <label>Category Image</label>
                <?php if (!empty($edit['image'])): ?>
                    <div style="margin-bottom:8px;">
                        <img src="../assets/images/<?= e($edit['image']) ?>" alt="" style="height:50px; border-radius:4px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
        </form>
    </div>

    <!-- List -->
    <div class="admin-table-card">
        <div class="admin-table-header"><h3>All Categories (<?= count($cats) ?>)</h3></div>
        <table class="admin-table">
            <thead><tr><th>#</th><th>Name</th><th>Slug</th><th>Products</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($cats as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><strong><?= e($c['name']) ?></strong></td>
                <td><code style="font-size:12px;color:var(--grey)"><?= e($c['slug']) ?></code></td>
                <td><?= $c['product_count'] ?></td>
                <td>
                    <div class="action-btns">
                        <a href="?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                        <?php if ($c['product_count']==0): ?>
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
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
</body>
</html>
