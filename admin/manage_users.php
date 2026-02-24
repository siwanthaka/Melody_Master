<?php
// admin/manage_users.php
require_once '../includes/auth.php';
requireAdmin();
$pdo = getDB();

$msg = ''; $error = '';

// Update user role
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = $_POST['role'] ?? 'customer';
    $allowed = ['customer','staff','admin'];
    // Prevent demoting self
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot change your own role.';
    } elseif (in_array($role, $allowed)) {
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
        $msg = 'User role updated.';
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    if ($did === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$did]);
        $msg = 'User deleted.';
    }
}

$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$where = 'WHERE 1=1';
$params = [];
if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($roleFilter) {
    $where .= " AND role=?"; $params[] = $roleFilter;
}
$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count FROM users u $where ORDER BY u.created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manage Users | Melody Masters</title>
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

<!-- Filters -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <form method="GET" style="display:flex;gap:8px;">
        <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search name or email…" style="width:250px;">
        <select name="role" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <option value="admin"    <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
            <option value="staff"    <?= $roleFilter==='staff'?'selected':'' ?>>Staff</option>
            <option value="customer" <?= $roleFilter==='customer'?'selected':'' ?>>Customer</option>
        </select>
        <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if ($search || $roleFilter): ?><a href="manage_users.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
</div>

<div class="admin-table-card">
    <div class="admin-table-header">
        <h3>Users (<?= count($users) ?>)</h3>
    </div>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
                <th>Role</th><th>Orders</th><th>Joined</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--grey);">No users found</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u):
            $isSelf = $u['id'] === (int)$_SESSION['user_id'];
            $roleColors = ['admin'=>'badge-danger','staff'=>'badge-warning','customer'=>'badge-info'];
        ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:34px;height:34px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:600;flex-shrink:0;">
                        <?= strtoupper($u['name'][0]) ?>
                    </div>
                    <strong><?= e($u['name']) ?></strong>
                    <?php if ($isSelf): ?><span class="badge badge-success">You</span><?php endif; ?>
                </div>
            </td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['phone'] ?? '–') ?></td>
            <td>
                <?php if (!$isSelf): ?>
                <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role" class="form-control" style="padding:4px 8px;font-size:12.5px;width:auto;" onchange="this.form.submit()">
                        <?php foreach (['customer','staff','admin'] as $r): ?>
                        <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="update_role" value="1">
                </form>
                <?php else: ?>
                <span class="badge <?= $roleColors[$u['role']] ?? 'badge-info' ?>"><?= ucfirst($u['role']) ?></span>
                <?php endif; ?>
            </td>
            <td><?= $u['order_count'] ?></td>
            <td><?= date('j M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if (!$isSelf): ?>
                <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete user <?= e(addslashes($u['name'])) ?>? This will delete all their orders.')">
                    <i class="fas fa-trash"></i>
                </a>
                <?php else: ?>
                <span style="color:var(--grey);font-size:12px;">N/A</span>
                <?php endif; ?>
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
