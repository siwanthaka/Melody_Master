<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getUser(): ?array {
    if (!isLoggedIn()) return null;
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $redirect = '/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . ltrim($redirect, '/'));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    $user = getUser();
    if (!in_array($user['role'] ?? '', ['admin', 'staff'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    $user = getUser();
    if (!in_array($user['role'] ?? '', $roles)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function isAdmin(): bool {
    $user = getUser();
    return in_array($user['role'] ?? '', ['admin', 'staff']);
}

function cartCount(): int {
    return array_sum($_SESSION['cart'] ?? []);
}

function addToCart(int $productId, int $qty = 1): void {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
}

function removeFromCart(int $productId): void {
    unset($_SESSION['cart'][$productId]);
}

function updateCartQty(int $productId, int $qty): void {
    if ($qty <= 0) {
        removeFromCart($productId);
    } else {
        $_SESSION['cart'][$productId] = $qty;
    }
}

function getCartItems(): array {
    if (empty($_SESSION['cart'])) return [];
    $pdo = getDB();
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    $items = [];
    foreach ($products as $p) {
        $items[] = [
            'product'  => $p,
            'quantity' => $_SESSION['cart'][$p['id']],
            'subtotal' => $p['price'] * $_SESSION['cart'][$p['id']],
        ];
    }
    return $items;
}

function cartTotal(): float {
    $items = getCartItems();
    return array_sum(array_column($items, 'subtotal'));
}

function hasPhysicalItems(): bool {
    $items = getCartItems();
    foreach ($items as $item) {
        if (!$item['product']['is_digital']) return true;
    }
    return false;
}

function shippingCost(): float {
    return cartTotal() >= FREE_SHIPPING_THRESHOLD ? 0.0 : SHIPPING_COST;
}

function userHasPurchased(int $userId, int $productId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status != 'cancelled'
    ");
    $stmt->execute([$userId, $productId]);
    return $stmt->fetchColumn() > 0;
}
