<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

$customerStmt = $conn->prepare("
    SELECT customer_id 
    FROM customers 
    WHERE user_id = ?
    LIMIT 1
");
$customerStmt->bind_param("i", $user_id);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();

if (!$customer) {
    die("Customer profile not found.");
}

$customer_id = (int)$customer['customer_id'];

$stmt = $conn->prepare("
    SELECT 
        b.*,
        s.service_name,
        s.duration,
        u.username AS stylist_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN staff_profiles sp ON b.staff_id = sp.staff_id
    JOIN users u ON sp.user_id = u.user_id
    WHERE b.booking_id = ? 
      AND b.customer_id = ?
");
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking || !in_array($booking['status'], ['pending','confirmed'])) {
    header("Location: my_history.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reschedule Booking</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        .page-wrap {
            max-width: 850px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .reschedule-card {
            background: white;
            border-radius: 22px;
            padding: 34px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.08);
            border: 1px solid #eee;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            margin-bottom: 28px;
        }

        .page-header h1 {
            margin: 0;
            color: #2d3436;
            font-size: 2rem;
        }

        .page-header p {
            margin: 7px 0 0;
            color: #777;
        }

        .icon-box {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: #f0edff;
            color: #6c5ce7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 25px;
        }

        .info-card {
            background: #faf9ff;
            border: 1px solid #e7e1ff;
            padding: 16px;
            border-radius: 16px;
        }

        .info-card .label {
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 6px;
        }

        .info-card .value {
            color: #2d3436;
            font-weight: 800;
        }

        .form-group label {
            font-weight: 700;
            color: #555;
            margin-bottom: 8px;
        }

        input[type="date"] {
            width: 100%;
            padding: 14px 15px;
            border-radius: 12px;
            border: 1px solid #ddd;
            background: #fafafa;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        input[type="date"]:focus {
            outline: none;
            border-color: #6c5ce7;
            background: white;
            box-shadow: 0 0 0 4px rgba(108,92,231,0.12);
        }

        .slot-box {
            margin-top: 25px;
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

        .actions {
            display: flex;
            gap: 14px;
            margin-top: 28px;
            flex-wrap: wrap;
        }

        .btn-main,
        .btn-soft {
            display: inline-block;
            text-decoration: none;
            border: none;
            border-radius: 14px;
            padding: 14px 20px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-main {
            flex: 1;
            background: linear-gradient(135deg, #6c5ce7, #7d6df2);
            color: white;
            font-size: 1rem;
        }

        .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(108,92,231,0.35);
        }

        .btn-soft {
            background: #f0edff;
            color: #6c5ce7;
        }

        @media (max-width: 750px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                align-items: flex-start;
            }

            .icon-box {
                display: none;
            }

            .btn-main,
            .btn-soft {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="page-wrap">
    <div class="reschedule-card">

        <div class="page-header">
            <div>
                <h1>Reschedule Booking</h1>
                <p>Choose a new date and available time slot for your appointment.</p>
            </div>
            <div class="icon-box">🔁</div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="label">Service</div>
                <div class="value"><?= htmlspecialchars($booking['service_name']) ?></div>
            </div>

            <div class="info-card">
                <div class="label">Stylist</div>
                <div class="value"><?= htmlspecialchars($booking['stylist_name']) ?></div>
            </div>

            <div class="info-card">
                <div class="label">Current Date</div>
                <div class="value"><?= date('d M Y', strtotime($booking['booking_date'])) ?></div>
            </div>
        </div>

        <form method="POST" action="update_booking.php">

            <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
            <input type="hidden" name="service_id" id="service_id" value="<?= (int)$booking['service_id'] ?>">
            <input type="hidden" name="staff_id" id="staff_id" value="<?= (int)$booking['staff_id'] ?>">

            <div class="form-group">
                <label>New Appointment Date</label>
                <input 
                    type="date" 
                    name="booking_date" 
                    id="booking_date"
                    min="<?= date('Y-m-d') ?>"
                    max="<?= date('Y-m-d', strtotime('+1 month')) ?>"
                    value="<?= htmlspecialchars($booking['booking_date']) ?>"
                    required
                >
            </div>

            <div class="slot-box">
                <h3>Available Time Slots</h3>
                <div id="slotContainer" class="slot-list">
                    <div class="notice-box">Loading available slots...</div>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn-main">Update Booking</button>
                <a href="my_history.php" class="btn-soft">Back</a>
            </div>

        </form>

    </div>
</div>

<script>
const serviceId = document.getElementById('service_id').value;
const staffId = document.getElementById('staff_id').value;
const dateEl = document.getElementById('booking_date');
const slotContainer = document.getElementById('slotContainer');

async function loadSlots() {
    const bookingDate = dateEl.value;

    if (!bookingDate) {
        slotContainer.innerHTML = "<div class='notice-box'>Choose a date to load slots.</div>";
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

dateEl.addEventListener('change', loadSlots);
loadSlots();
</script>

</body>
</html>