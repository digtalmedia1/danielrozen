<?php
require_once __DIR__ . "/../includes/functions.php";
requireLogin();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול הזמנות - דניאל רוזן</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>body { font-family: "Assistant", sans-serif; } #calendar { max-width: 1100px; margin: 40px auto; } .sidebar { background: #000; color: #fff; min-height: 100vh; padding: 20px; } .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 0; } .sidebar a:hover { color: #ccc; }</style>
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
            <main class="col-md-10"><div class="p-4"><h2>ניהול זמינות והזמנות</h2><div id="calendar"></div></div></main>
        </div>
    </div>
    <div class="modal fade" id="slotModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">ניהול חלון זמן</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="slotForm"><input type="hidden" id="slotId"><div class="mb-3"><label class="form-label">זמן התחלה</label><input type="datetime-local" id="startTime" class="form-control" required></div><div class="mb-3"><label class="form-label">זמן סיום</label><input type="datetime-local" id="endTime" class="form-control" required></div><div class="mb-3"><label class="form-label">סטטוס</label><select id="slotStatus" class="form-control"><option value="available">זמין</option><option value="blocked">חסום</option><option value="booked">מוזמן</option></select></div><div class="mb-3"><label class="form-label">הערה ללקוחה</label><input type="text" id="publicNote" class="form-control"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-danger" id="deleteSlotBtn">מחק</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button><button type="button" class="btn btn-primary" id="saveSlotBtn">שמור</button></div></div></div></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/he.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const calendarEl = document.getElementById("calendar");
            const slotModal = new bootstrap.Modal(document.getElementById("slotModal"));
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth", locale: "he", direction: "rtl",
                headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,timeGridWeek,timeGridDay" },
                events: "../api/admin/slots.php", selectable: true,
                select: function(info) {
                    document.getElementById("slotForm").reset();
                    document.getElementById("slotId").value = "";
                    document.getElementById("startTime").value = info.startStr.slice(0, 16);
                    document.getElementById("endTime").value = info.endStr.slice(0, 16);
                    document.getElementById("deleteSlotBtn").style.display = "none";
                    slotModal.show();
                },
                eventClick: function(info) {
                    const ev = info.event;
                    document.getElementById("slotId").value = ev.id;
                    document.getElementById("startTime").value = ev.startStr.slice(0, 16);
                    document.getElementById("endTime").value = ev.endStr.slice(0, 16);
                    document.getElementById("slotStatus").value = ev.extendedProps.status || "available";
                    document.getElementById("publicNote").value = ev.extendedProps.public_note || "";
                    document.getElementById("deleteSlotBtn").style.display = "block";
                    slotModal.show();
                }
            });
            calendar.render();
            document.getElementById("saveSlotBtn").addEventListener("click", async () => {
                const id = document.getElementById("slotId").value;
                const data = { id: id, start: document.getElementById("startTime").value, end: document.getElementById("endTime").value, status: document.getElementById("slotStatus").value, public_note: document.getElementById("publicNote").value };
                const method = id ? "PUT" : "POST";
                await fetch("../api/admin/slots.php", { method: method, headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) });
                slotModal.hide(); calendar.refetchEvents();
            });
            document.getElementById("deleteSlotBtn").addEventListener("click", async () => {
                if (!confirm("האם למחוק חלון זמן זה?")) return;
                const id = document.getElementById("slotId").value;
                await fetch("../api/admin/slots.php?id=" + id, { method: "DELETE" });
                slotModal.hide(); calendar.refetchEvents();
            });
            document.getElementById("logoutBtn").addEventListener("click", async () => {
                await fetch("../api/auth.php?action=logout", { method: "POST" });
                window.location.href = "login.php";
            });
        });
    </script>
</body>
</html>
