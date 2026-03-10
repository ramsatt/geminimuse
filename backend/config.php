<?php
// ============================================================
// GeminiMuse — Database Configuration
// Fill in your shared hosting credentials before deploying.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'geminimuse');       // your database name
define('DB_USER', 'your_db_user');     // your DB username
define('DB_PASS', 'your_db_password'); // your DB password
define('DB_CHARSET', 'utf8mb4');

// Allowed origins for CORS — add your app domain
define('ALLOWED_ORIGINS', [
    'https://codingtamilan.in',
    'http://localhost:4200',   // Angular dev server
    'capacitor://localhost',   // Capacitor iOS
    'http://localhost',        // Capacitor Android
]);

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }
    return $pdo;
}
