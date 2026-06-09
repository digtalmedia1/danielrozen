<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/db.php";
requireLogin();

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC");
    jsonResponse(["success" => true, "data" => $stmt->fetchAll()]);
}

if ($method === "POST") {
    verifyCsrfToken();
    $action = $_GET["action"] ?? "";
    $data = json_decode(file_get_contents("php://input"), true);
    $id = filter_var($data["id"] ?? 0, FILTER_VALIDATE_INT);

    if (!$id && $action !== "manual_create") {
        jsonResponse(["success" => false, "error" => ["code" => "INVALID_ID", "message" => "Invalid booking ID"]], 400);
    }

    if ($action === "update_status") {
        $newStatus = sanitize($data["status"] ?? "");
        $allowedStatuses = ['confirmed', 'completed', 'cancelled', 'rejected'];

        if (!in_array($newStatus, $allowedStatuses)) {
            jsonResponse(["success" => false, "error" => ["code" => "INVALID_STATUS", "message" => "Invalid status"]], 400);
        }

        $pdo->beginTransaction();
        try {
            // Get current booking and slot info
            $stmt = $pdo->prepare("SELECT availability_slot_id, status FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            if (!$booking) throw new Exception("Booking not found");

            $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            if ($booking['availability_slot_id']) {
                $slotStatus = 'available';
                if ($newStatus === 'confirmed' || $newStatus === 'completed') {
                    $slotStatus = 'booked';
                } elseif ($newStatus === 'cancelled' && isset($data['block_slot']) && $data['block_slot']) {
                    $slotStatus = 'blocked';
                }

                $stmt = $pdo->prepare("UPDATE availability_slots SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$slotStatus, $booking['availability_slot_id']]);
            }

            logAudit($pdo, 'booking_status_updated', ['booking_id' => $id, 'status' => $newStatus]);
            $pdo->commit();
            jsonResponse(["success" => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(["success" => false, "error" => ["code" => "UPDATE_FAILED", "message" => $e->getMessage()]], 500);
        }
    }

    if ($action === "manual_create") {
        // Manual admin booking bypasses min_advance/max_future but respects overlap
        $name = sanitize($data["name"] ?? "");
        $phone = sanitize($data["phone"] ?? "");
        $email = sanitize($data["email"] ?? "");
        $service = sanitize($data["service"] ?? "General");
        $start = sanitize($data["start_datetime"] ?? "");
        $end = sanitize($data["end_datetime"] ?? "");
        $note = sanitize($data["note"] ?? "");

        if (!$name || !$phone || !$start || !$end) {
            jsonResponse(["success" => false, "error" => ["code" => "MISSING_FIELDS", "message" => "Required fields missing"]], 400);
        }

        $pdo->beginTransaction();
        try {
            // 1. Check for overlapping non-available slots (booked, blocked, pending)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM availability_slots
                                   WHERE ((start_datetime < ? AND end_datetime > ?)
                                   OR (start_datetime < ? AND end_datetime > ?))
                                   AND status != 'available'");
            $stmt->execute([$end, $start, $end, $start]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Time slot overlaps with an existing booking or block");
            }

            // 2. Identify and handle overlapping available slots
            // Any available slot that overlaps must be removed or modified to prevent duplicate public booking
            $stmt = $pdo->prepare("DELETE FROM availability_slots
                                   WHERE ((start_datetime < ? AND end_datetime > ?)
                                   OR (start_datetime < ? AND end_datetime > ?))
                                   AND status = 'available'");
            $stmt->execute([$end, $start, $end, $start]);
            $removedSlotsCount = $stmt->rowCount();

            // 3. Create the new slot as booked
            $stmt = $pdo->prepare("INSERT INTO availability_slots (start_datetime, end_datetime, status, admin_note) VALUES (?, ?, 'booked', 'Manual booking')");
            $stmt->execute([$start, $end]);
            $slotId = $pdo->lastInsertId();

            // 4. Create booking as confirmed
            $stmt = $pdo->prepare("INSERT INTO bookings (customer_name, customer_phone, customer_email, service_type, booking_date, start_time, end_time, status, admin_note, availability_slot_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?)");
            $startTime = new DateTime($start);
            $endTime = new DateTime($end);
            $stmt->execute([
                $name, $phone, $email, $service,
                $startTime->format('Y-m-d'), $startTime->format('H:i:s'), $endTime->format('H:i:s'),
                $note, $slotId
            ]);
            $bookingId = $pdo->lastInsertId();

            logAudit($pdo, 'manual_booking_created', [
                'booking_id' => $bookingId,
                'slot_id' => $slotId,
                'removed_available_slots' => $removedSlotsCount
            ]);

            $pdo->commit();
            jsonResponse(["success" => true, "data" => ["booking_id" => $bookingId]]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            jsonResponse(["success" => false, "error" => ["code" => "MANUAL_BOOKING_FAILED", "message" => $e->getMessage()]], 500);
        }
    }
}
