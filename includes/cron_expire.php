<?php
// Hostinger cron-compatible script
require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/db.php";

echo "Running booking expiration cleanup...\n";

try {
    $pdo->beginTransaction();

    // Find expired pending bookings
    $stmt = $pdo->prepare("SELECT availability_slot_id, id FROM bookings WHERE status = 'pending' AND expires_at < NOW()");
    $stmt->execute();
    $expired = $stmt->fetchAll();

    $count = 0;
    foreach ($expired as $b) {
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'expired', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$b['id']]);

        // Return slot to available
        if ($b['availability_slot_id']) {
            $stmt = $pdo->prepare("UPDATE availability_slots SET status = 'available', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$b['availability_slot_id']]);
        }

        logAudit($pdo, 'system_expire_booking', ['booking_id' => $b['id'], 'slot_id' => $b['availability_slot_id']]);
        $count++;
    }

    $pdo->commit();
    echo "Successfully expired $count bookings.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Cron Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
