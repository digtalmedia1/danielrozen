<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/db.php";
requireLogin();
$method = $_SERVER["REQUEST_METHOD"];
if ($method === "GET") {
    $start = $_GET["start"] ?? "";
    $end = $_GET["end"] ?? "";
    $stmt = $pdo->prepare("SELECT * FROM availability_slots WHERE start_datetime >= ? AND end_datetime <= ?");
    $stmt->execute([$start, $end]);
    jsonResponse($stmt->fetchAll());
} elseif ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO availability_slots (start_datetime, end_datetime, status, public_note, admin_note) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data["start"], $data["end"], $data["status"] ?? "available", $data["public_note"] ?? null, $data["admin_note"] ?? null]);
    jsonResponse(["success" => true, "id" => $pdo->lastInsertId()]);
} elseif ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE availability_slots SET start_datetime = ?, end_datetime = ?, status = ?, public_note = ?, admin_note = ? WHERE id = ?");
    $stmt->execute([$data["start"], $data["end"], $data["status"], $data["public_note"], $data["admin_note"], $data["id"]]);
    jsonResponse(["success" => true]);
} elseif ($method === "DELETE") {
    $id = $_GET["id"] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM availability_slots WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(["success" => true]);
}