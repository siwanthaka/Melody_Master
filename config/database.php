<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'melody_masters');
define('BASE_URL', 'http://localhost/advance web assigment/');
define('SITE_NAME', 'Melody Masters');
define('SHIPPING_COST', 10.00);
define('FREE_SHIPPING_THRESHOLD', 100.00);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;color:red;padding:20px;">
                <h2>Database Connection Error</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Please ensure XAMPP MySQL is running and the database <strong>' . DB_NAME . '</strong> exists.</p>
            </div>');
        }
    }
    return $pdo;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
