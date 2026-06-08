<?php
require_once __DIR__ . "/../includes/functions.php";
requireLogin();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול הגדרות - דניאל רוזן</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <style>body { font-family: "Assistant", sans-serif; } .sidebar { background: #000; color: #fff; min-height: 100vh; padding: 20px; } .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 0; }</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 sidebar d-none d-md-block"><h3 class="text-center mb-4">Daniel Rozen</h3><a href="index.php">לוח שנה</a><a href="bookings.php">הזמנות</a><a href="settings.php">הגדרות</a></nav>
            <main class="col-md-10"><div class="p-4"><h2>הגדרות מערכת</h2><form id="settingsForm" class="mt-4" style="max-width: 600px;"><div class="mb-3"><label class="form-label">משך מפגש ברירת מחדל (דקות)</label><input type="number" name="session_duration" class="form-control"></div><div class="mb-3"><label class="form-label">מרווח בין מפגשים (דקות)</label><input type="number" name="gap_between_sessions" class="form-control"></div><button type="submit" class="btn btn-primary">שמור הגדרות</button></form></div></main>
        </div>
    </div>
    <script>
        async function loadSettings() { const response = await fetch("../api/admin/settings.php"); const settings = await response.json(); for (const key in settings) { const input = document.querySelector("[name=" + key + "]"); if (input) input.value = settings[key]; } }
        document.getElementById("settingsForm").addEventListener("submit", async (e) => { e.preventDefault(); const formData = new FormData(e.target); const data = Object.fromEntries(formData.entries()); await fetch("../api/admin/settings.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) }); alert("ההגדרות נשמרו בהצלחה"); });
        loadSettings();
    </script>
</body>
</html>