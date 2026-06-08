<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/db.php";
requireLogin();
$method = $_SERVER["REQUEST_METHOD"];
if ($method === "GET") {
    $stmt = $pdo->query("SELECT * FROM booking_settings");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) { $settings[$row["setting_key"]] = $row["setting_value"]; }
    jsonResponse($settings);
} elseif ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    foreach ($data as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO booking_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    jsonResponse(["success" => true]);
}