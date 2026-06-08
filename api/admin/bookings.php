<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/db.php";
requireLogin();
$method = $_SERVER["REQUEST_METHOD"];
if ($method === "GET") {
    $stmt = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC");
    jsonResponse($stmt->fetchAll());
} elseif ($method === "POST") {
    $action = $_GET["action"] ?? "";
    $data = json_decode(file_get_contents("php://input"), true);
    if ($action === "update_status") {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$data["status"], $data["id"]]);
        if ($data["status"] === "confirmed") {
            $stmt = $pdo->prepare("SELECT availability_slot_id FROM bookings WHERE id = ?");
            $stmt->execute([$data["id"]]);
            $slot_id = $stmt->fetchColumn();
            if ($slot_id) {
                $stmt = $pdo->prepare("UPDATE availability_slots SET status = \"booked\" WHERE id = ?");
                $stmt->execute([$slot_id]);
            }
        }
        jsonResponse(["success" => true]);
    }
}