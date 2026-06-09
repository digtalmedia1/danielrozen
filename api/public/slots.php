<?php
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/db.php";

// Run expiration cleanup
try {
    $pdo->beginTransaction();
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
    }
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

// Fetch settings for limits
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM booking_settings WHERE setting_key IN ('min_advance_booking', 'max_future_booking', 'system_enabled')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (($settings['system_enabled'] ?? '1') !== '1') {
    jsonResponse(["success" => true, "data" => []]);
}

$minAdvance = (int)($settings['min_advance_booking'] ?? 24);
$maxFuture = (int)($settings['max_future_booking'] ?? 3);

$minDate = (new DateTime())->modify("+$minAdvance hours")->format('Y-m-d H:i:s');
$maxDate = (new DateTime())->modify("+$maxFuture months")->format('Y-m-d H:i:s');

$stmt = $pdo->prepare("SELECT id, start_datetime, end_datetime, public_note FROM availability_slots
                       WHERE status = 'available'
                       AND start_datetime >= ?
                       AND start_datetime <= ?
                       ORDER BY start_datetime ASC");
$stmt->execute([$minDate, $maxDate]);

jsonResponse(["success" => true, "data" => $stmt->fetchAll()]);
