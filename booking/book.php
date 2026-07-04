<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$services = $conn->query("
    SELECT service_id, service_name, duration
    FROM services
    ORDER BY service_name
");

$stylists = $conn->query("
    SELECT DISTINCT 
        t.service_id,
        sp.staff_id,
        u.username
    FROM timeslots t
    JOIN staff_profiles sp ON t.staff_id = sp.staff_id
    JOIN users u ON sp.user_id = u.user_id
    WHERE t.service_id IS NOT NULL
    ORDER BY u.username
");

$flash = $_SESSION['booking_flash'] ?? null;
unset($_SESSION['booking_flash']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book Appointment</title>
<link rel="stylesheet" href="../style.css">

<style>
.booking-page {
    max-width: 980px;
    margin: 45px auto;
    padding: 0 20px;
}

.booking-card {
    background: #fff;
    border-radius: 22px;
    padding: 34px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.08);
    border: 1px solid #eee;
}

.booking-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 28px;
}

.booking-title h1 {
    margin: 0;
    font-size: 2rem;
    color: #2d3436;
}

.booking-title p {
    margin: 6px 0 0;
    color: #777;
}

.booking-icon {
    width: 68px;
    height: 68px;
    background: #f0edff;
    color: #6c5ce7;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.booking-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.form-group label {
    font-weight: 600;
    color: #555;
    margin-bottom: 8px;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 14px 15px;
    border-radius: 12px;
    border: 1px solid #ddd;
    background: #fafafa;
    font-size: 0.95rem;
    transition: 0.2s;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #6c5ce7;
    background: white;
    box-shadow: 0 0 0 4px rgba(108,92,231,0.12);
}

.slot-box {
    margin-top: 28px;
    padding: 24px;
    border-radius: 18px;
    background: linear-gradient(135deg, #faf9ff, #f4f1ff);
    border: 1px solid #e5ddff;
}

.slot-box h3 {
    margin: 0 0 16px;
    color: #2d3436;
}

.slot-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.slot-list input[type="radio"] {
    display: none;
}

.slot-list label {
    display: inline-block;
    padding: 12px 18px;
    background: white;
    color: #6c5ce7;
    border: 1px solid #d9d2ff;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 700;
    transition: 0.2s;
}

.slot-list label:hover {
    background: #f0edff;
    transform: translateY(-2px);
}

.slot-list input[type="radio"]:checked + label {
    background: #6c5ce7;
    color: white;
    box-shadow: 0 8px 18px rgba(108,92,231,0.3);
}

.notes-box {
    margin-top: 24px;
}

.notes-box textarea {
    min-height: 100px;
    resize: vertical;
}

.booking-actions {
    display: flex;
    gap: 14px;
    margin-top: 26px;
    align-items: center;
}

.btn-confirm {
    flex: 1;
    padding: 15px 22px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #6c5ce7, #7d6df2);
    color: white;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}

.btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(108,92,231,0.35);
}

.btn-soft {
    padding: 14px 18px;
    border-radius: 14px;
    background: #f0edff;
    color: #6c5ce7;
    text-decoration: none;
    font-weight: 700;
}

.notice-box {
    padding: 14px;
    border-radius: 12px;
    background: #eef2ff;
    color: #3730a3;
    font-weight: 600;
}

.error-box {
    padding: 14px;
    border-radius: 12px;
    background: #ffe1e1;
    color: #b00020;
    font-weight: 600;
}

@media (max-width: 800px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }

    .booking-title {
        align-items: flex-start;
    }

    .booking-icon {
        display: none;
    }

    .booking-actions {
        flex-direction: column;
    }

    .btn-confirm,
    .btn-soft {
        width: 100%;
        text-align: center;
    }
}
</style>
</head>

<body>
<?php include '../header.php'; ?>

<div class="booking-page">
<div class="booking-card">

    <div class="booking-title">
        <div>
            <h1>Book Appointment</h1>
            <p>Choose your service, preferred stylist, date, and available time slot.</p>
        </div>
        <div class="booking-icon">📅</div>
    </div>

    <?php if ($flash): ?>
        <div class="<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['msg']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="process_booking.php">

        <div class="booking-grid">

            <div class="form-group">
                <label>Service</label>
                <select name="service_id" id="service_id" required>
                    <option value="">-- Select Service --</option>
                    <?php while ($service = $services->fetch_assoc()): ?>
                        <option value="<?= (int)$service['service_id'] ?>">
                            <?= htmlspecialchars($service['service_name']) ?>
                            (<?= (int)$service['duration'] ?> min)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Preferred Stylist</label>
                <select name="staff_id" id="staff_id" required>
                    <option value="">-- Select service first --</option>

                    <?php while ($stylist = $stylists->fetch_assoc()): ?>
                        <option 
                            value="<?= (int)$stylist['staff_id'] ?>"
                            data-service="<?= (int)$stylist['service_id'] ?>"
                            style="display:none;"
                        >
                            <?= htmlspecialchars($stylist['username']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Appointment Date</label>
                <input 
                    type="date" 
                    name="booking_date" 
                    id="booking_date" 
                    min="<?= date('Y-m-d') ?>" 
                    max="<?= date('Y-m-d', strtotime('+1 month')) ?>" 
                    required
                >
            </div>

        </div>

        <div class="slot-box">
            <h3>Available Time Slots</h3>
            <div id="slotContainer" class="slot-list">
                <div class="notice-box">Select service, stylist, and date to load available slots.</div>
            </div>
        </div>

        <div class="form-group notes-box">
            <label>Notes (optional)</label>
            <textarea name="notes" placeholder="Anything the stylist should know?"></textarea>
        </div>

        <div class="booking-actions">
            <button type="submit" class="btn-confirm">Confirm Booking</button>
            <a href="my_history.php" class="btn-soft">My Appointments</a>
        </div>

    </form>

</div>
</div>

<script>
const serviceEl = document.getElementById('service_id');
const staffEl = document.getElementById('staff_id');
const dateEl = document.getElementById('booking_date');
const slotContainer = document.getElementById('slotContainer');

function filterStylists() {
    const selectedService = serviceEl.value;
    let found = false;

    staffEl.value = "";

    Array.from(staffEl.options).forEach(option => {
        if (option.value === "") {
            option.textContent = "-- Select Stylist --";
            option.style.display = "block";
            return;
        }

        if (option.dataset.service === selectedService) {
            option.style.display = "block";
            found = true;
        } else {
            option.style.display = "none";
        }
    });

    if (!selectedService) {
        staffEl.options[0].textContent = "-- Select service first --";
    } else if (!found) {
        staffEl.options[0].textContent = "No stylist available for this service";
    }

    slotContainer.innerHTML = "<div class='notice-box'>Select service, stylist, and date to load available slots.</div>";
}

async function loadSlots() {
    const serviceId = serviceEl.value;
    const staffId = staffEl.value;
    const bookingDate = dateEl.value;

    if (!serviceId || !staffId || !bookingDate) {
        slotContainer.innerHTML = "<div class='notice-box'>Select service, stylist, and date to load available slots.</div>";
        return;
    }

    slotContainer.innerHTML = "<div class='notice-box'>Loading available slots...</div>";

    try {
        const res = await fetch('/salon/booking/get_slots.php?service_id=' + encodeURIComponent(serviceId) + '&staff_id=' + encodeURIComponent(staffId) + '&booking_date=' + encodeURIComponent(bookingDate));
        const data = await res.json();

        if (!data.success || data.slots.length === 0) {
            slotContainer.innerHTML = "<div class='error-box'>No available slots for this date.</div>";
            return;
        }

        slotContainer.innerHTML = data.slots.map((slot, index) => `
            <div>
                <input 
                    type="radio" 
                    name="slot_value" 
                    id="slot_${index}" 
                    value="${slot.timeslot_id}|${slot.start}" 
                    required
                >
                <label for="slot_${index}">
                    ${slot.label}
                </label>
            </div>
        `).join("");

    } catch (error) {
        console.error(error);
        slotContainer.innerHTML = "<div class='error-box'>Error loading slots.</div>";
    }
}

serviceEl.addEventListener('change', function () {
    filterStylists();
    loadSlots();
});

staffEl.addEventListener('change', loadSlots);
dateEl.addEventListener('change', loadSlots);
</script>

</body>
</html>