<?php
require_once __DIR__ . "/../includes/functions.php";
if (isLoggedIn()) { header("Location: index.php"); return; }
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התחברות מנהל - דניאל רוזן</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <style>body { background-color: #f8f9fa; font-family: "Assistant", sans-serif; } .login-container { max-width: 400px; margin-top: 100px; } .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); } .btn-primary { background-color: #000; border: none; } .btn-primary:hover { background-color: #333; }</style>
</head>
<body>
    <div class="container login-container">
        <div class="card p-4">
            <h2 class="text-center mb-4">כניסת מנהל</h2>
            <div id="alert" class="alert d-none"></div>
            <form id="loginForm">
                <div class="mb-3"><label class="form-label">אימייל</label><input type="email" class="form-control" id="email" required></div>
                <div class="mb-3"><label class="form-label">סיסמה</label><input type="password" class="form-control" id="password" required></div>
                <button type="submit" class="btn btn-primary w-100">התחבר</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            const alertDiv = document.getElementById("alert");
            try {
                const response = await fetch("../api/auth.php?action=login", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: "email=" + encodeURIComponent(email) + "&password=" + encodeURIComponent(password) });
                const result = await response.json();
                if (result.success) { window.location.href = "index.php"; }
                else { alertDiv.textContent = result.error || "שגיאה בהתחברות"; alertDiv.className = "alert alert-danger"; alertDiv.classList.remove("d-none"); }
            } catch (error) { alertDiv.textContent = "שגיאת שרת"; alertDiv.className = "alert alert-danger"; alertDiv.classList.remove("d-none"); }
        });
    </script>
</body>
</html>