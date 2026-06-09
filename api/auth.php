<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$method = $_SERVER["REQUEST_METHOD"];
$action = $_GET["action"] ?? "";

if ($method === "POST") {
    if ($action === "login") {
        $identifier = sanitize($_POST["identifier"] ?? $_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $result = attemptLogin($identifier, $password, $pdo);
        if ($result["success"]) {
            jsonResponse(["success" => true]);
        } else {
            jsonResponse($result, 401);
        }
    } elseif ($action === "logout") {
        verifyCsrfToken();
        logout($pdo);
        jsonResponse(["success" => true]);
    }
}

if ($method === "GET" && $action === "check") {
    jsonResponse(["success" => true, "isLoggedIn" => isLoggedIn()]);
}

jsonResponse(["success" => false, "error" => ["code" => "INVALID_REQUEST", "message" => "Invalid request"]], 400);
