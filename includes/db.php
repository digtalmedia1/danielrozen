<?php
function getDbConnection() {
    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // In production, don't leak credentials or detailed errors
        error_log($e->getMessage());
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode(["success" => false, "error" => ["code" => "DB_ERROR", "message" => "Database connection failed"]]);
        exit;
    }
}

// Global PDO instance helper
if (!isset($pdo)) {
    $pdo = getDbConnection();
}
