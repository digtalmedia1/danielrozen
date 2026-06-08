<?php
require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/db.php";

function attemptLogin($email, $password, $pdo) {
    $ip = $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND was_successful = 0");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() > 5) {
        return ["success" => false, "message" => "Too many login attempts. Please try again later."];
    }
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["admin_id"] = $user["id"];
        $_SESSION["admin_name"] = $user["name"];
        $stmt = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$user["id"]]);
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, was_successful) VALUES (?, ?, 1)");
        $stmt->execute([$ip, $email]);
        return ["success" => true];
    } else {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, was_successful) VALUES (?, ?, 0)");
        $stmt->execute([$ip, $email]);
        return ["success" => false, "message" => "Invalid email or password."];
    }
}

function logout() {
    session_unset();
    session_destroy();
}
