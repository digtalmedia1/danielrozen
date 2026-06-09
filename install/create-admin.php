<?php
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/db.php";

$lockFile = __DIR__ . "/install.lock";

// Security checks
if (file_exists($lockFile)) {
    die("Installation locked. Please delete the /install/ directory.");
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    if ($stmt->fetchColumn() > 0) {
        if (!file_exists($lockFile)) file_put_contents($lockFile, date('Y-m-d H:i:s'));
        die("Administrator already exists. Installation locked.");
    }
} catch (Exception $e) {
    // Table might not exist, proceed to see if it works later or fails gracefully
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $token = $_POST['install_token'] ?? '';

    $installToken = getenv('INSTALL_TOKEN');

    $errors = [];
    if (!$installToken || !hash_equals($installToken, $token)) {
        $errors[] = "Invalid INSTALL_TOKEN.";
    }
    if (!$username || !$name || !$email) {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 12) {
        $errors[] = "Password must be at least 12 characters.";
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain uppercase, lowercase, number and special character.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, name, email, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $name, $email, $hash]);

            file_put_contents($lockFile, date('Y-m-d H:i:s'));
            logAudit($pdo, 'first_admin_created', ['username' => $username]);

            $success = "Administrator created successfully! Redirecting to login...";
            header("refresh:3;url=../admin/login.php");
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>התקנת מערכת - דניאל רוזן</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <style>body { background-color: #f8f9fa; font-family: "Assistant", sans-serif; }</style>
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <div class="card p-4 shadow border-0" style="border-radius: 15px;">
            <h2 class="mb-4 text-center">יצירת מנהל ראשוני</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success text-center"><?php echo $success; ?></div>
                <div class="text-center mt-3">
                    <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3"><label class="form-label">INSTALL_TOKEN (מתוך קובץ .env)</label><input type="password" name="install_token" class="form-control" required></div>
                    <hr>
                    <div class="mb-3"><label class="form-label">שם מלא</label><input type="text" name="name" class="form-control" value="<?php echo sanitize($_POST['name'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label">שם משתמש</label><input type="text" name="username" class="form-control" value="<?php echo sanitize($_POST['username'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label">אימייל</label><input type="email" name="email" class="form-control" value="<?php echo sanitize($_POST['email'] ?? ''); ?>" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">סיסמה</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">אימות סיסמה</label><input type="password" name="confirm_password" class="form-control" required></div>
                    </div>
                    <div class="small text-muted mb-4">
                        הסיסמה חייבת להכיל לפחות 12 תווים, אות גדולה, אות קטנה, מספר ותו מיוחד.
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2">צור חשבון מנהל</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
