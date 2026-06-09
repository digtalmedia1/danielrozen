<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/db.php";
requireLogin();

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM booking_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    jsonResponse(["success" => true, "data" => $settings]);
}

if ($method === "POST") {
    verifyCsrfToken();
    $data = json_decode(file_get_contents("php://input"), true);

    $allowedSettings = [
        'session_duration',
        'gap_between_sessions',
        'min_advance_booking',
        'max_future_booking',
        'pending_expiration',
        'timezone',
        'system_enabled'
    ];

    $pdo->beginTransaction();
    try {
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedSettings)) continue;

            $stmt = $pdo->prepare("INSERT INTO booking_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->execute([$key, sanitize($value), sanitize($value)]);
        }

        logAudit($pdo, 'settings_updated', $data);
        $pdo->commit();
        jsonResponse(["success" => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(["success" => false, "error" => ["code" => "SETTINGS_UPDATE_FAILED", "message" => $e->getMessage()]], 500);
    }
}
