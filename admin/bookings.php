<?php
require_once __DIR__ . "/../includes/functions.php";
requireLogin();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>רשימת הזמנות - דניאל רוזן</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <style>body { font-family: "Assistant", sans-serif; } .sidebar { background: #000; color: #fff; min-height: 100vh; padding: 20px; } .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 0; } .status-pending { color: orange; } .status-confirmed { color: green; } .status-cancelled { color: red; }</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 sidebar d-none d-md-block"><h3 class="text-center mb-4">Daniel Rozen</h3><a href="index.php">לוח שנה</a><a href="bookings.php">הזמנות</a><a href="settings.php">הגדרות</a></nav>
            <main class="col-md-10"><div class="p-4"><h2>הזמנות חדשות וממתינות</h2><div class="table-responsive mt-4"><table class="table table-hover"><thead><tr><th>לקוחה</th><th>טלפון</th><th>תאריך ושעה</th><th>סטטוס</th><th>פעולות</th></tr></thead><tbody id="bookingsTable"></tbody></table></div></div></main>
        </div>
    </div>
    <script>
        async function loadBookings() {
            const response = await fetch("../api/admin/bookings.php");
            const bookings = await response.json();
            const table = document.getElementById("bookingsTable");
            table.innerHTML = "";
            bookings.forEach(b => {
                const tr = document.createElement("tr");
                tr.innerHTML = "<td>" + b.customer_name + "</td><td>" + b.customer_phone + "</td><td>" + b.booking_date + " " + b.start_time + "</td><td class=\"status-" + b.status + "\">" + b.status + "</td><td>" + (b.status === "pending" ? "<button class=\"btn btn-sm btn-success me-1\" onclick=\"updateStatus(" + b.id + ", \"confirmed\")\">אשר</button>" : "") + (b.status !== "cancelled" ? "<button class=\"btn btn-sm btn-danger\" onclick=\"updateStatus(" + b.id + ", \"cancelled\")\">בטל</button>" : "") + "</td>";
                table.appendChild(tr);
            });
        }
        async function updateStatus(id, status) { await fetch("../api/admin/bookings.php?action=update_status", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id, status }) }); loadBookings(); }
        loadBookings();
    </script>
</body>
</html>