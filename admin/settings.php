<?php
require_once __DIR__ . "/../includes/functions.php";
requireLogin();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הגדרות מערכת - דניאל רוזן</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <style>
        body { font-family: "Assistant", sans-serif; }
        .sidebar { background: #000; color: #fff; min-height: 100vh; padding: 20px; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 sidebar d-none d-md-block">
                <h3 class="text-center mb-4">Daniel Rozen</h3>
                <a href="index.php">לוח שנה</a>
                <a href="bookings.php">הזמנות</a>
                <a href="settings.php">הגדרות</a>
                <a href="#" id="logoutBtn">התנתקות</a>
            </nav>
            <main class="col-md-10">
                <div class="p-4">
                    <h2>הגדרות מערכת</h2>
                    <form id="settingsForm" class="mt-4" style="max-width: 600px;">
                        <div class="mb-3">
                            <label class="form-label">מערכת פעילה (Public Booking)</label>
                            <select name="system_enabled" class="form-select">
                                <option value="1">פעילה</option>
                                <option value="0">מושבתת</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">משך מפגש ברירת מחדל (דקות)</label>
                            <input type="number" name="session_duration" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">מרווח בין מפגשים (דקות)</label>
                            <input type="number" name="gap_between_sessions" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">מינימום זמן להזמנה מראש (שעות)</label>
                            <input type="number" name="min_advance_booking" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">מקסימום זמן להזמנה מראש (חודשים)</label>
                            <input type="number" name="max_future_booking" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">זמן תפוגה להזמנה ממתינה (דקות)</label>
                            <input type="number" name="pending_expiration" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">אזור זמן</label>
                            <input type="text" name="timezone" class="form-control" value="Asia/Jerusalem" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">שמור הגדרות</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

        async function loadSettings() {
            const response = await fetch("../api/admin/settings.php");
            const res = await response.json();
            if (res.success) {
                const settings = res.data;
                for (const key in settings) {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) input.value = settings[key];
                }
            }
        }

        document.getElementById("settingsForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            const response = await fetch("../api/admin/settings.php", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF_TOKEN },
                body: JSON.stringify(data)
            });
            const res = await response.json();
            if (res.success) alert("ההגדרות נשמרו בהצלחה");
            else alert("שגיאה בשמירה: " + res.error.message);
        });

        document.getElementById("logoutBtn").addEventListener("click", async (e) => {
            e.preventDefault();
            await fetch("../api/auth.php?action=logout", {
                method: "POST",
                headers: { "X-CSRF-Token": CSRF_TOKEN }
            });
            window.location.href = "login.php";
        });

        loadSettings();
    </script>
</body>
</html>
