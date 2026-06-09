<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הזמנת תור - דניאל רוזן</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../assets/theme/css/style.css">
    <style>
        body { font-family: "Assistant", sans-serif; background: #000; color: #fff; }
        .booking-container { max-width: 800px; margin: 50px auto; padding: 20px; background: #111; border-radius: 15px; min-height: 400px; }
        .slot-item { cursor: pointer; border: 1px solid #444; padding: 15px; margin: 10px 0; border-radius: 10px; text-align: center; transition: 0.3s; background: #1a1a1a; }
        .slot-item:hover { background: #333; border-color: #fff; transform: translateY(-2px); }
        .slot-item.selected { background: #fff; color: #000; }
        .form-control, .form-select { background: #222; color: #fff; border-color: #444; }
        .form-control:focus, .form-select:focus { background: #333; color: #fff; border-color: #666; box-shadow: none; }
        .btn-light { font-weight: bold; padding: 12px; }
        .badge-note { font-size: 0.8rem; color: #aaa; margin-top: 5px; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="booking-container">
            <h2 class="text-center mb-5">הזמנת תור אישי</h2>

            <div id="step1">
                <h4 class="mb-4">1. בחרי מועד פנוי (קביעת מועד):</h4>
                <div id="slotsList" class="row">
                    <div class="col-12 text-center py-5">טוען מועדים פנויים...</div>
                </div>
            </div>

            <div id="step2" class="d-none">
                <h4 class="mb-4">2. פרטי ההזמנה:</h4>
                <div id="selectedSlotInfo" class="alert alert-info mb-4"></div>
                <form id="bookingForm">
                    <input type="hidden" id="selectedSlotId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">שם מלא</label>
                            <input type="text" id="name" class="form-control" placeholder="השם שלך" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">טלפון</label>
                            <input type="tel" id="phone" class="form-control" placeholder="מספר נייד" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">אימייל (אופציונלי - לקבלת פרטי ההזמנה)</label>
                        <input type="email" id="email" class="form-control" placeholder="your@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">סוג שירות</label>
                        <select id="service" class="form-select">
                            <option value="עיסוי אירוטי">עיסוי אירוטי</option>
                            <option value="סשן אישי">סשן אישי</option>
                            <option value="ליווי">ליווי</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">הערות ובקשות מיוחדות</label>
                        <textarea id="note" class="form-control" rows="3" placeholder="כאן תוכלי לכתוב כל מה שחשוב לי לדעת..."></textarea>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="policy" required>
                        <label class="form-check-label ms-2" for="policy">אני מאשרת את פרטי ההזמנה</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" id="submitBtn" class="btn btn-light">שלחי בקשת הזמנה</button>
                        <button type="button" class="btn btn-link text-white text-decoration-none" onclick="showStep(1)">חזרה לבחירת מועד</button>
                    </div>
                </form>
            </div>

            <div id="success" class="d-none text-center py-5">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <h3>הבקשה נשלחה בהצלחה!</h3>
                <p class="lead mt-3">חלון הזמן נשמר עבורך ל-30 דקות הקרובות.</p>
                <p>אחזור אלייך בהקדם להשלמת ההזמנה ואישור סופי.</p>
                <a href="/" class="btn btn-outline-light mt-4">חזרה לאתר</a>
            </div>
        </div>
    </div>

    <script>
        async function loadSlots() {
            try {
                const response = await fetch("../api/public/slots.php");
                const res = await response.json();
                const container = document.getElementById("slotsList");
                container.innerHTML = "";

                if (!res.success || res.data.length === 0) {
                    container.innerHTML = "<div class='col-12 text-center py-5'><p>אין מועדים פנויים כרגע להזמנה ישירה.</p><p>ניתן ליצור קשר טלפוני לתיאום פגישה.</p></div>";
                    return;
                }

                res.data.forEach(s => {
                    const date = new Date(s.start_datetime);
                    const dateStr = date.toLocaleDateString("he-IL", { weekday: "long", month: "long", day: "numeric" });
                    const timeStr = date.toLocaleTimeString("he-IL", { hour: "2-digit", minute: "2-digit" });

                    const col = document.createElement("div");
                    col.className = "col-md-4 col-sm-6";
                    col.innerHTML = `
                        <div class="slot-item" onclick="selectSlot(${s.id}, '${dateStr}', '${timeStr}')">
                            <div class="fw-bold text-white">${dateStr}</div>
                            <div class="fs-5">${timeStr}</div>
                            ${s.public_note ? `<span class="badge-note">${s.public_note}</span>` : ""}
                        </div>
                    `;
                    container.appendChild(col);
                });
            } catch (e) {
                document.getElementById("slotsList").innerHTML = "<div class='col-12 text-center py-5'>שגיאה בטעינת נתונים</div>";
            }
        }

        function selectSlot(id, dateStr, timeStr) {
            document.getElementById("selectedSlotId").value = id;
            document.getElementById("selectedSlotInfo").innerHTML = `<strong>מועד נבחר:</strong> ${dateStr}, בשעה ${timeStr}`;
            showStep(2);
        }

        function showStep(n) {
            document.getElementById("step1").classList.toggle("d-none", n !== 1);
            document.getElementById("step2").classList.toggle("d-none", n !== 2);
            document.getElementById("success").classList.toggle("d-none", n !== 3);
            if (n === 1) loadSlots();
        }

        document.getElementById("bookingForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.innerHTML = "שולח...";

            const data = {
                slot_id: document.getElementById("selectedSlotId").value,
                name: document.getElementById("name").value,
                phone: document.getElementById("phone").value,
                email: document.getElementById("email").value,
                service: document.getElementById("service").value,
                note: document.getElementById("note").value
            };

            try {
                const response = await fetch("../api/public/bookings.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });
                const res = await response.json();
                if (res.success) {
                    showStep(3);
                } else {
                    alert(res.error.message || "שגיאה בשליחת הבקשה");
                    btn.disabled = false;
                    btn.innerHTML = "שלחי בקשת הזמנה";
                }
            } catch (error) {
                alert("שגיאת תקשורת");
                btn.disabled = false;
                btn.innerHTML = "שלחי בקשת הזמנה";
            }
        });

        loadSlots();
    </script>
</body>
</html>
