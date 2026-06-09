<?php
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/db.php";
if (isLoggedIn()) { header("Location: index.php"); exit; }

// Check if any admin exists
$showInstaller = false;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    if ($stmt->fetchColumn() == 0 && !file_exists(__DIR__ . "/../install/install.lock")) {
        $showInstaller = true;
    }
} catch (Exception $e) {
    // DB might not be installed yet
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התחברות מנהל - דניאל רוזן</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: "Assistant", sans-serif; }
        .login-container { max-width: 400px; margin-top: 100px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #000; border: none; }
        .btn-primary:hover { background-color: #333; }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card p-4">
            <h2 class="text-center mb-4">כניסת מנהל</h2>
            <div id="alert" class="alert d-none"></div>
            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning">החיבור פג עקב חוסר פעילות. אנא התחבר שוב.</div>
            <?php endif; ?>
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">שם משתמש או אימייל</label>
                    <input type="text" class="form-control" id="identifier" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">סיסמה</label>
                    <input type="password" class="form-control" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">התחבר</button>

                <?php if ($showInstaller): ?>
                <div class="text-center mt-3 pt-3 border-top">
                    <p class="small text-muted">לא הוגדר מנהל מערכת עדיין</p>
                    <a href="../install/create-admin.php" class="btn btn-outline-dark btn-sm w-100">יצירת מנהל ראשוני</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <script>
        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const identifier = document.getElementById("identifier").value;
            const password = document.getElementById("password").value;
            const alertDiv = document.getElementById("alert");

            try {
                const response = await fetch("../api/auth.php?action=login", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "identifier=" + encodeURIComponent(identifier) + "&password=" + encodeURIComponent(password)
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = "index.php";
                } else {
                    alertDiv.textContent = (result.error && result.error.message) || "שגיאה בהתחברות";
                    alertDiv.className = "alert alert-danger";
                    alertDiv.classList.remove("d-none");
                }
            } catch (error) {
                alertDiv.textContent = "שגיאת שרת";
                alertDiv.className = "alert alert-danger";
                alertDiv.classList.remove("d-none");
            }
        });
    </script>
</body>
</html>
