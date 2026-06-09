<?php
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/db.php";

// Run expiration cleanup before returning slots or processing booking
function cleanupExpiredPending($pdo) {
    $pdo->beginTransaction();
    try {
        // Find expired bookings
        $stmt = $pdo->prepare("SELECT availability_slot_id, id FROM bookings WHERE status = 'pending' AND expires_at < NOW()");
        $stmt->execute();
        $expired = $stmt->fetchAll();

        foreach ($expired as $b) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'expired' WHERE id = ?");
            $stmt->execute([$b['id']]);

            if ($b['availability_slot_id']) {
                $stmt = $pdo->prepare("UPDATE availability_slots SET status = 'available' WHERE id = ?");
                $stmt->execute([$b['availability_slot_id']]);
            }
            logAudit($pdo, 'system_expire_booking', ['booking_id' => $b['id']]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    cleanupExpiredPending($pdo);

    $data = json_decode(file_get_contents("php://input"), true);

    // Validation
    $name = sanitize($data["name"] ?? "");
    $phone = sanitize($data["phone"] ?? "");
    $email = sanitize($data["email"] ?? "");
    $service = sanitize($data["service"] ?? "");
    $slotId = filter_var($data["slot_id"] ?? 0, FILTER_VALIDATE_INT);
    $note = sanitize($data["note"] ?? "");

    if (!$name || !$phone || !$slotId) {
        jsonResponse(["success" => false, "error" => ["code" => "MISSING_FIELDS", "message" => "Please fill in all required fields"]], 400);
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(["success" => false, "error" => ["code" => "INVALID_EMAIL", "message" => "Invalid email format"]], 400);
    }

    // Check system enabled
    $stmt = $pdo->prepare("SELECT setting_value FROM booking_settings WHERE setting_key = 'system_enabled'");
    $stmt->execute();
    if ($stmt->fetchColumn() !== '1') {
        jsonResponse(["success" => false, "error" => ["code" => "SYSTEM_DISABLED", "message" => "Booking system is currently disabled"]], 403);
    }

    // Get pending expiration setting
    $stmt = $pdo->prepare("SELECT setting_value FROM booking_settings WHERE setting_key = 'pending_expiration'");
    $stmt->execute();
    $expireMinutes = (int)($stmt->fetchColumn() ?: 30);

    $pdo->beginTransaction();
    try {
        // Atomic slot reservation
        $stmt = $pdo->prepare("UPDATE availability_slots SET status = 'pending', updated_at = NOW() WHERE id = ? AND status = 'available'");
        $stmt->execute([$slotId]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            jsonResponse(["success" => false, "error" => ["code" => "SLOT_UNAVAILABLE", "message" => "This time slot is no longer available"]], 400);
        }

        // Get slot details for booking record
        $stmt = $pdo->prepare("SELECT * FROM availability_slots WHERE id = ?");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        $startTime = new DateTime($slot["start_datetime"]);
        $endTime = new DateTime($slot["end_datetime"]);
        $expiresAt = (new DateTime())->modify("+$expireMinutes minutes")->format("Y-m-d H:i:s");

        $stmt = $pdo->prepare("INSERT INTO bookings (customer_name, customer_phone, customer_email, service_type, booking_date, start_time, end_time, customer_note, availability_slot_id, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([
            $name, $phone, $email, $service ?: "General",
            $startTime->format("Y-m-d"), $startTime->format("H:i:s"), $endTime->format("H:i:s"),
            $note, $slotId, $expiresAt
        ]);

        $bookingId = $pdo->lastInsertId();
        logAudit($pdo, 'public_booking_created', ['booking_id' => $bookingId, 'slot_id' => $slotId]);

        $pdo->commit();
        jsonResponse(["success" => true, "data" => ["booking_id" => $bookingId]]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        jsonResponse(["success" => false, "error" => ["code" => "SERVER_ERROR", "message" => "Something went wrong. Please try again."]], 500);
    }
}
