<?php
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), "#") === 0) continue;
        list($name, $value) = explode("=", $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . "=" . trim($value));
    }
}
loadEnv(__DIR__ . "/../.env");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonResponse($data, $statusCode = 200) {
    header("Content-Type: application/json");
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function generateCsrfToken() {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function verifyCsrfToken($token) {
    return !empty($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION["admin_id"]);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (strpos($_SERVER["REQUEST_URI"], "/api/") !== false) {
            jsonResponse(["error" => "Unauthorized"], 401);
        }
        header("Location: /admin/login.php");
        exit;
    }
}
