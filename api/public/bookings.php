<?php
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/db.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data["name"]) || empty($data["phone"]) || empty($data["slot_id"])) { jsonResponse(["error" => "Please fill in all required fields"], 400); }
    $stmt = $pdo->prepare("SELECT * FROM availability_slots WHERE id = ? AND status = \"available\"");
    $stmt->execute([$data["slot_id"]]);
    $slot = $stmt->fetch();
    if (!$slot) { jsonResponse(["error" => "This time slot is no longer available"], 400); }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (customer_name, customer_phone, customer_email, service_type, booking_date, start_time, end_time, customer_note, availability_slot_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \"pending\")");
        $startTime = new DateTime($slot["start_datetime"]);
        $endTime = new DateTime($slot["end_datetime"]);
        $stmt->execute([ sanitize($data["name"]), sanitize($data["phone"]), sanitize($data["email"] ?? null), sanitize($data["service"] ?? "General"), $startTime->format("Y-m-d"), $startTime->format("H:i:s"), $endTime->format("H:i:s"), sanitize($data["note"] ?? null), $slot["id"] ]);
        $stmt = $pdo->prepare("UPDATE availability_slots SET status = \"pending\" WHERE id = ?");
        $stmt->execute([$slot["id"]]);
        $pdo->commit();
        jsonResponse(["success" => true]);
    } catch (Exception $e) { $pdo->rollBack(); jsonResponse(["error" => "Something went wrong. Please try again."], 500); }
}