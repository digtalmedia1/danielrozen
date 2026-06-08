<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_GET["action"] ?? "";
    if ($action === "login") {
        $email = sanitize($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $result = attemptLogin($email, $password, $pdo);
        if ($result["success"]) { jsonResponse(["success" => true]); }
        else { jsonResponse(["error" => $result["message"]], 401); }
    } elseif ($action === "logout") { logout(); jsonResponse(["success" => true]); }
}
jsonResponse(["error" => "Invalid request"], 400);