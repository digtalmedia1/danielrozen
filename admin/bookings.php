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
    <title>רשימת הזמנות - דניאל רוזן</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <style>
        body { font-family: "Assistant", sans-serif; }
        .sidebar { background: #000; color: #fff; min-height: 100vh; padding: 20px; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 0; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-confirmed { color: #28a745; font-weight: bold; }
        .status-cancelled { color: #dc3545; }
        .status-rejected { color: #6c757d; }
        .status-expired { color: #343a40; }
        .status-completed { color: #007bff; font-weight: bold; }
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
                    <h2>רשימת הזמנות</h2>
                    <div class="table-responsive mt-4">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>מזהה</th>
                                    <th>לקוחה</th>
                                    <th>טלפון</th>
                                    <th>תאריך ושעה</th>
                                    <th>שירות</th>
                                    <th>סטטוס</th>
                                    <th>פעולות</th>
                                </tr>
                            </thead>
                            <tbody id="bookingsTable">
                                <tr><td colspan="7" class="text-center">טוען נתונים...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

        async function loadBookings() {
            try {
                const response = await fetch("../api/admin/bookings.php");
                const res = await response.json();
                if (!res.success) throw new Error(res.error.message);

                const table = document.getElementById("bookingsTable");
                table.innerHTML = "";

                res.data.forEach(b => {
                    const tr = document.createElement("tr");
                    const statusText = {
                        'pending': 'ממתין',
                        'confirmed': 'מאושר',
                        'completed': 'הושלם',
                        'cancelled': 'בוטל',
                        'rejected': 'נדחה',
                        'expired': 'פג תוקף'
                    }[b.status] || b.status;

                    let actions = '';
                    if (b.status === 'pending') {
                        actions += `<button class="btn btn-sm btn-success me-1" onclick="updateStatus(${b.id}, 'confirmed')">אשר</button>`;
                        actions += `<button class="btn btn-sm btn-secondary" onclick="updateStatus(${b.id}, 'rejected')">דחה</button>`;
                    } else if (b.status === 'confirmed') {
                        actions += `<button class="btn btn-sm btn-primary me-1" onclick="updateStatus(${b.id}, 'completed')">השלם</button>`;
                        actions += `<button class="btn btn-sm btn-danger" onclick="updateStatus(${b.id}, 'cancelled')">בטל</button>`;
                    } else if (b.status === 'cancelled') {
                        // Optional: Re-block slot
                        actions += `<small class="text-muted">אין פעולות</small>`;
                    }

                    tr.innerHTML = `
                        <td>${b.id}</td>
                        <td>${b.customer_name}</td>
                        <td>${b.customer_phone}</td>
                        <td>${b.booking_date} ${b.start_time.slice(0,5)}</td>
                        <td>${b.service_type}</td>
                        <td class="status-${b.status}">${statusText}</td>
                        <td>${actions}</td>
                    `;
                    table.appendChild(tr);
                });
            } catch (error) {
                console.error(error);
            }
        }

        async function updateStatus(id, status) {
            let block_slot = false;
            if (status === 'cancelled') {
                block_slot = confirm("האם ברצונך לחסום את חלון הזמן הזה (לא לאפשר הזמנה מחדש)?");
            }

            const response = await fetch("../api/admin/bookings.php?action=update_status", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF_TOKEN },
                body: JSON.stringify({ id, status, block_slot })
            });
            const res = await response.json();
            if (res.success) loadBookings();
            else alert("שגיאה בעדכון: " + res.error.message);
        }

        document.getElementById("logoutBtn").addEventListener("click", async (e) => {
            e.preventDefault();
            await fetch("../api/auth.php?action=logout", {
                method: "POST",
                headers: { "X-CSRF-Token": CSRF_TOKEN }
            });
            window.location.href = "login.php";
        });

        loadBookings();
    </script>
</body>
</html>
