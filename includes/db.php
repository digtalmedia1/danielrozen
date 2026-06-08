<?php
require_once __DIR__ . "/functions.php";
try {
    $dbConfig = require __DIR__ . "/../config/database.php";
    $dsn = "mysql:host={$dbConfig["host"]};port={$dbConfig["port"]};dbname={$dbConfig["name"]};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $dbConfig["user"], $dbConfig["pass"], $options);
} catch (PDOException $e) {
    if (strpos($_SERVER["REQUEST_URI"], "/api/") !== false) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }
    die("Database connection failed.");
}
