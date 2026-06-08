<?php
require_once __DIR__ . "/../../includes/functions.php";
require_once __DIR__ . "/../../includes/db.php";
$stmt = $pdo->query("SELECT id, start_datetime, end_datetime, public_note FROM availability_slots WHERE status = \"available\" AND start_datetime > NOW() ORDER BY start_datetime ASC");\jsonResponse($stmt->fetchAll());