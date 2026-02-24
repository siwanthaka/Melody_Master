<?php
require_once 'includes/auth.php';
$pdo = getDB();

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $redirect = $_POST['redirect']      ?? 'index.php';

    if (!$email || !$password) {
        $errors[] = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            $_SESSION['success'] = 'Welcome back, ' . $user['name'] . '!';
            // Safe redirect
            $safeRedirect = filter_var($redirect, FILTER_VALIDATE_URL) ? $redirect : 'index.php';
            header("Location: $safeRedirect");
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login | Melody Masters</title>
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
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to your account</p>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?><div><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-icon-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>" placeholder="your@email.com" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Your password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px;">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
    </form>

    <div style="background:var(--primary-light);border-radius:var(--radius-sm);padding:12px;margin-top:14px;font-size:13px;color:var(--dark-3);">
        <strong>Demo accounts:</strong><br>
        Admin: <code>admin@melodymaster.com</code> / <code>password</code><br>
        Customer: <code>james@example.com</code> / <code>password</code>
    </div>

    <div class="form-divider">or</div>
    <p style="text-align:center;font-size:14px;color:var(--grey);">
        New here? <a href="register.php">Create an account</a>
    </p>
</div>
</div>
</div>
</body>
</html>
