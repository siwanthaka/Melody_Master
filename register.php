<?php
require_once 'includes/auth.php';
$pdo = getDB();

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $phone    = trim($_POST['phone']    ?? '');

    if (!$name)                        $errors[] = 'Full name is required.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($password) < 8)         $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)        $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (name,email,password,role,phone) VALUES(?,?,?,'customer',?)")
                ->execute([$name,$email,$hash,$phone]);
            $uid = $pdo->lastInsertId();
            $_SESSION['user_id'] = $uid;
            $_SESSION['user']    = ['id'=>$uid,'name'=>$name,'email'=>$email,'role'=>'customer'];
            $_SESSION['success'] = "Welcome, $name! Your account has been created.";
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Register | Melody Masters</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="auth-page">
<div class="container">
<div class="form-card">
    <a href="index.php" class="logo" style="margin-bottom:20px;display:flex;">
        <i class="fas fa-music"></i><span>Melody<strong>Masters</strong></span>
    </a>
    <h2>Create Account</h2>
    <p class="subtitle">Join thousands of musicians today</p>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?><div><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="form-group">
            <label>Full Name</label>
            <div class="input-icon-wrap">
                <i class="fas fa-user"></i>
                <input type="text" name="name" class="form-control" value="<?= e($_POST['name']??'') ?>" placeholder="John Smith" required>
            </div>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-icon-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>" placeholder="john@example.com" required>
            </div>
        </div>
        <div class="form-group">
            <label>Phone (optional)</label>
            <div class="input-icon-wrap">
                <i class="fas fa-phone"></i>
                <input type="tel" name="phone" class="form-control" value="<?= e($_POST['phone']??'') ?>" placeholder="+44 7700 000000">
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
            </div>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-icon-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-user-plus"></i> Create Account
        </button>
    </form>

    <div class="form-divider">or</div>
    <p style="text-align:center;font-size:14px;color:var(--grey);">
        Already have an account? <a href="login.php">Sign in</a>
    </p>
</div>
</div>
</div>
</body>
</html>
