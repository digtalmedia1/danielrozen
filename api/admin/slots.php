<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/db.php";
requireLogin();

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $start = sanitize($_GET["start"] ?? "");
    $end = sanitize($_GET["end"] ?? "");
    if (!$start || !$end) {
        $stmt = $pdo->query("SELECT * FROM availability_slots ORDER BY start_datetime ASC LIMIT 500");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM availability_slots WHERE start_datetime >= ? AND start_datetime <= ?");
        $stmt->execute([$start, $end]);
    }
    jsonResponse(["success" => true, "data" => $stmt->fetchAll()]);
}

if ($method === "POST" || $method === "PUT" || $method === "DELETE") {
    verifyCsrfToken();
    $data = json_decode(file_get_contents("php://input"), true);

    if ($method === "POST") {
        $start = sanitize($data["start"] ?? "");
        $end = sanitize($data["end"] ?? "");
        $status = sanitize($data["status"] ?? "available");

        if (!$start || !$end) {
            jsonResponse(["success" => false, "error" => ["code" => "INVALID_INPUT", "message" => "Start and end times are required"]], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO availability_slots (start_datetime, end_datetime, status, public_note, admin_note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$start, $end, $status, sanitize($data["public_note"] ?? null), sanitize($data["admin_note"] ?? null)]);
        $id = $pdo->lastInsertId();

        logAudit($pdo, 'slot_created', ['slot_id' => $id]);
        jsonResponse(["success" => true, "data" => ["id" => $id]]);
    }

    if ($method === "PUT") {
        $id = filter_var($data["id"] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) jsonResponse(["success" => false, "error" => ["code" => "INVALID_ID", "message" => "Invalid slot ID"]], 400);

        $stmt = $pdo->prepare("UPDATE availability_slots SET start_datetime = ?, end_datetime = ?, status = ?, public_note = ?, admin_note = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([
            sanitize($data["start"]),
            sanitize($data["end"]),
            sanitize($data["status"]),
            sanitize($data["public_note"] ?? null),
            sanitize($data["admin_note"] ?? null),
            $id
        ]);

        logAudit($pdo, 'slot_updated', ['slot_id' => $id]);
        jsonResponse(["success" => true]);
    }

    if ($method === "DELETE") {
        $id = filter_var($_GET["id"] ?? $data["id"] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) jsonResponse(["success" => false, "error" => ["code" => "INVALID_ID", "message" => "Invalid slot ID"]], 400);

        // Don't delete if it has a booking
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE availability_slot_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(["success" => false, "error" => ["code" => "SLOT_HAS_BOOKING", "message" => "Cannot delete a slot with an associated booking"]], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM availability_slots WHERE id = ?");
        $stmt->execute([$id]);

        logAudit($pdo, 'slot_deleted', ['slot_id' => $id]);
        jsonResponse(["success" => true]);
    }
}
