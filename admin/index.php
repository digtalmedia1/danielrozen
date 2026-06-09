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
    <title>ניהול הזמנות - דניאל רוזן</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        body { font-family: "Assistant", sans-serif; }
        #calendar { max-width: 1100px; margin: 40px auto; }
        .sidebar { background: #000; color: #fff; min-height: 100vh; padding: 20px; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 0; }
        .sidebar a:hover { color: #ccc; }
        .fc-event { cursor: pointer; }
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>ניהול זמינות והזמנות</h2>
                        <button class="btn btn-dark" id="manualBookingBtn">הזמנה ידנית</button>
                    </div>
                    <div id="calendar"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- Slot Modal -->
    <div class="modal fade" id="slotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ניהול חלון זמן</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="slotForm">
                        <input type="hidden" id="slotId">
                        <div class="mb-3">
                            <label class="form-label">זמן התחלה</label>
                            <input type="datetime-local" id="startTime" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">זמן סיום</label>
                            <input type="datetime-local" id="endTime" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">סטטוס</label>
                            <select id="slotStatus" class="form-select">
                                <option value="available">זמין</option>
                                <option value="blocked">חסום</option>
                                <option value="pending" disabled>ממתין להשלמה (נעול)</option>
                                <option value="booked" disabled>מוזמן</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">הערה ללקוחה</label>
                            <textarea id="publicNote" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">הערה פנימית</label>
                            <textarea id="adminNote" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deleteSlotBtn">מחק</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="button" class="btn btn-primary" id="saveSlotBtn">שמור</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Booking Modal -->
    <div class="modal fade" id="manualBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">הזמנה ידנית</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="manualBookingForm">
                        <div class="mb-3"><label class="form-label">שם לקוחה</label><input type="text" id="mName" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">טלפון</label><input type="text" id="mPhone" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">אימייל</label><input type="email" id="mEmail" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">שירות</label><input type="text" id="mService" class="form-control" value="עיסוי"></div>
                        <div class="row">
                            <div class="col-md-6"><div class="mb-3"><label class="form-label">התחלה</label><input type="datetime-local" id="mStart" class="form-control" required></div></div>
                            <div class="col-md-6"><div class="mb-3"><label class="form-label">סיום</label><input type="datetime-local" id="mEnd" class="form-control" required></div></div>
                        </div>
                        <div class="mb-3"><label class="form-label">הערות</label><textarea id="mNote" class="form-control"></textarea></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="button" class="btn btn-primary" id="submitManualBooking">צור הזמנה</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/he.js"></script>
    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

        document.addEventListener("DOMContentLoaded", function() {
            const calendarEl = document.getElementById("calendar");
            const slotModal = new bootstrap.Modal(document.getElementById("slotModal"));
            const manualModal = new bootstrap.Modal(document.getElementById("manualBookingModal"));

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth",
                locale: "he",
                direction: "rtl",
                headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,timeGridWeek,timeGridDay" },
                events: function(info, successCallback, failureCallback) {
                    fetch(`../api/admin/slots.php?start=${info.startStr}&end=${info.endStr}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                const events = res.data.map(slot => ({
                                    id: slot.id,
                                    title: slot.status === 'available' ? 'פנוי' : (slot.status === 'booked' ? 'מוזמן' : (slot.status === 'pending' ? 'ממתין' : 'חסום')),
                                    start: slot.start_datetime,
                                    end: slot.end_datetime,
                                    backgroundColor: slot.status === 'available' ? '#28a745' : (slot.status === 'booked' ? '#dc3545' : (slot.status === 'pending' ? '#ffc107' : '#6c757d')),
                                    extendedProps: {
                                        status: slot.status,
                                        public_note: slot.public_note,
                                        admin_note: slot.admin_note
                                    }
                                }));
                                successCallback(events);
                            }
                        });
                },
                selectable: true,
                select: function(info) {
                    document.getElementById("slotForm").reset();
                    document.getElementById("slotId").value = "";
                    document.getElementById("startTime").value = info.startStr.slice(0, 16);
                    document.getElementById("endTime").value = info.endStr.slice(0, 16);
                    document.getElementById("deleteSlotBtn").classList.add("d-none");
                    document.getElementById("slotStatus").disabled = false;
                    slotModal.show();
                },
                eventClick: function(info) {
                    const ev = info.event;
                    document.getElementById("slotId").value = ev.id;
                    document.getElementById("startTime").value = ev.startStr.slice(0, 16);
                    document.getElementById("endTime").value = ev.endStr.slice(0, 16);
                    document.getElementById("slotStatus").value = ev.extendedProps.status;
                    document.getElementById("publicNote").value = ev.extendedProps.public_note || "";
                    document.getElementById("adminNote").value = ev.extendedProps.admin_note || "";

                    document.getElementById("deleteSlotBtn").classList.toggle("d-none", ev.extendedProps.status === 'booked');
                    document.getElementById("slotStatus").disabled = (ev.extendedProps.status === 'booked' || ev.extendedProps.status === 'pending');

                    slotModal.show();
                }
            });

            calendar.render();

            document.getElementById("saveSlotBtn").addEventListener("click", async () => {
                const id = document.getElementById("slotId").value;
                const data = {
                    id: id,
                    start: document.getElementById("startTime").value,
                    end: document.getElementById("endTime").value,
                    status: document.getElementById("slotStatus").value,
                    public_note: document.getElementById("publicNote").value,
                    admin_note: document.getElementById("adminNote").value
                };
                const method = id ? "PUT" : "POST";
                const res = await fetch("../api/admin/slots.php", {
                    method: method,
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF_TOKEN },
                    body: JSON.stringify(data)
                });
                if ((await res.json()).success) { slotModal.hide(); calendar.refetchEvents(); }
                else alert("שגיאה בשמירה");
            });

            document.getElementById("deleteSlotBtn").addEventListener("click", async () => {
                if (!confirm("האם למחוק חלון זמן זה?")) return;
                const id = document.getElementById("slotId").value;
                const res = await fetch(`../api/admin/slots.php?id=${id}`, {
                    method: "DELETE",
                    headers: { "X-CSRF-Token": CSRF_TOKEN }
                });
                if ((await res.json()).success) { slotModal.hide(); calendar.refetchEvents(); }
                else alert("לא ניתן למחוק סלוט מוזמן או שיש שגיאה אחרת");
            });

            document.getElementById("manualBookingBtn").addEventListener("click", () => {
                document.getElementById("manualBookingForm").reset();
                manualModal.show();
            });

            document.getElementById("submitManualBooking").addEventListener("click", async () => {
                const data = {
                    name: document.getElementById("mName").value,
                    phone: document.getElementById("mPhone").value,
                    email: document.getElementById("mEmail").value,
                    service: document.getElementById("mService").value,
                    start_datetime: document.getElementById("mStart").value,
                    end_datetime: document.getElementById("mEnd").value,
                    note: document.getElementById("mNote").value
                };
                const res = await fetch("../api/admin/bookings.php?action=manual_create", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF_TOKEN },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) { manualModal.hide(); calendar.refetchEvents(); }
                else alert(result.error.message);
            });

            document.getElementById("logoutBtn").addEventListener("click", async (e) => {
                e.preventDefault();
                await fetch("../api/auth.php?action=logout", {
                    method: "POST",
                    headers: { "X-CSRF-Token": CSRF_TOKEN }
                });
                window.location.href = "login.php";
            });
        });
    </script>
</body>
</html>
