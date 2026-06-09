<?php
// Set timezone as early as possible
date_default_timezone_set('Asia/Jerusalem');

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), "#") === 0) continue;
        $parts = explode("=", $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}
loadEnv(__DIR__ . "/../.env");

// Secure session configuration
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // Inactivity timeout (30 minutes) - Only for logged in admins
    if (isset($_SESSION['admin_id'])) {
        $timeout = 1800;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            session_unset();
            session_destroy();
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                header("Content-Type: application/json; charset=UTF-8");
                http_response_code(401);
                echo json_encode(["success" => false, "error" => ["code" => "SESSION_EXPIRED", "message" => "Session expired"]]);
                exit;
            }
            header("Location: /admin/login.php?expired=1");
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

startSecureSession();

function jsonResponse($data, $statusCode = 200) {
    header("Content-Type: application/json; charset=UTF-8");
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

function verifyCsrfToken() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
        jsonResponse(["success" => false, "error" => ["code" => "CSRF_ERROR", "message" => "Invalid CSRF token"]], 403);
    }
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION["admin_id"]);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (strpos($_SERVER["REQUEST_URI"], "/api/") !== false) {
            jsonResponse(["success" => false, "error" => ["code" => "UNAUTHORIZED", "message" => "Unauthorized"]], 401);
        }
        header("Location: /admin/login.php");
        exit;
    }
}

function logAudit($pdo, $action, $details = null) {
    $userId = $_SESSION['admin_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details ? json_encode($details) : null, $ip]);
}
