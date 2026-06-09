<?php
require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/db.php";

function attemptLogin($identifier, $password, $pdo) {
    $ip = $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";

    // Brute force protection
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND was_successful = 0");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() > 10) {
        return ["success" => false, "error" => ["code" => "TOO_MANY_ATTEMPTS", "message" => "Too many login attempts. Please try again later."]];
    }

    // Support both username and email login
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE (email = ? OR username = ?) AND is_active = 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        // Secure session handling
        session_regenerate_id(true);
        $_SESSION["admin_id"] = $user["id"];
        $_SESSION["admin_name"] = $user["name"];
        $_SESSION['last_activity'] = time();

        $stmt = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$user["id"]]);

        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier, was_successful) VALUES (?, ?, 1)");
        $stmt->execute([$ip, $identifier]);

        logAudit($pdo, 'login_success', ['identifier' => $identifier]);

        return ["success" => true];
    } else {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier, was_successful) VALUES (?, ?, 0)");
        $stmt->execute([$ip, $identifier]);

        logAudit($pdo, 'login_failure', ['identifier' => $identifier]);

        return ["success" => false, "error" => ["code" => "INVALID_CREDENTIALS", "message" => "Invalid username/email or password."]];
    }
}

function logout($pdo = null) {
    if ($pdo) {
        logAudit($pdo, 'logout');
    }
    session_unset();
    session_destroy();
}
