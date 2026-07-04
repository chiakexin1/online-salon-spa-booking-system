<?php
session_start();
include 'db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'stylist', 'admin'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Staff';

$profile = [];
$staff_id = 0;
$bookings = [];

$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.phone,
        s.staff_id,
        s.specialization,
        s.bio,
        s.work_date,
        s.start_time,
        s.end_time
    FROM users u
    LEFT JOIN staff_profiles s ON u.user_id = s.user_id
    WHERE u.user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    die("Staff profile not found.");
}

$staff_id = isset($profile['staff_id']) ? (int)$profile['staff_id'] : 0;

if ($staff_id > 0) {
    try {
        $stmtBooking = $conn->prepare("
            SELECT 
                b.booking_id,
                b.booking_date,
                b.start_time,
                b.end_time,
                b.status,
                b.notes,
                s.service_name,
                u.username AS customer_name
            FROM bookings b
            LEFT JOIN services s ON b.service_id = s.service_id
            LEFT JOIN customers c ON b.customer_id = c.customer_id
            LEFT JOIN users u ON c.user_id = u.user_id
            WHERE b.staff_id = ?
              AND b.status IN ('pending', 'confirmed')
              AND CONCAT(b.booking_date, ' ', b.end_time) >= NOW()
            ORDER BY b.booking_date ASC, b.start_time ASC
            LIMIT 5
        ");
        $stmtBooking->bind_param("i", $staff_id);
        $stmtBooking->execute();
        $resultBooking = $stmtBooking->get_result();

        while ($row = $resultBooking->fetch_assoc()) {
            $bookings[] = $row;
        }
    } catch (mysqli_sql_exception $e) {
        $bookings = [];
    }
}

$today = date('Y-m-d');

$is_working_today = false;
$today_start = null;
$today_end = null;

$stmtSchedule = $conn->prepare("
    SELECT start_time, end_time
    FROM schedules   
    WHERE user_id = ?
    AND work_date = ?
    ORDER BY start_time ASC
");

$stmtSchedule->bind_param("is", $user_id, $today);
$stmtSchedule->execute();
$resultSchedule = $stmtSchedule->get_result();

if ($row = $resultSchedule->fetch_assoc()) {
    $is_working_today = true;
    $today_start = $row['start_time'];
    $today_end = $row['end_time'];
}

function fmt_time($time) {
    return $time && $time !== '00:00:00' ? date('g:i A', strtotime($time)) : '—';
}

function status_badge($status) {
    $status = strtolower($status);

    if ($status === 'confirmed') {
        return "<span class='status-badge confirmed'>Confirmed</span>";
    } elseif ($status === 'pending') {
        return "<span class='status-badge pending'>Pending</span>";
    } elseif ($status === 'completed') {
        return "<span class='status-badge completed'>Completed</span>";
    } elseif ($status === 'cancelled') {
        return "<span class='status-badge cancelled'>Cancelled</span>";
    }

    return "<span class='status-badge'>" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - Salon System</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #2d3436;
        }

        .dashboard-main {
            max-width: 1120px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .staff-header {
            background: linear-gradient(135deg, #6c5ce7, #8e7cff);
            color: white;
            padding: 32px;
            border-radius: 22px;
            margin-bottom: 24px;
            box-shadow: 0 12px 32px rgba(108,92,231,0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .staff-header h1 {
            margin: 0 0 8px;
            font-size: 2rem;
        }

        .staff-header p {
            margin: 0;
            opacity: 0.95;
        }

        .profile-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .profile-chip {
            background: rgba(255,255,255,0.18);
            color: white;
            padding: 6px 13px;
            border-radius: 999px;
            font-size: 0.82rem;
        }

        .btn-edit {
            padding: 11px 22px;
            background: white;
            color: #6c5ce7;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            white-space: nowrap;
            transition: 0.2s;
        }

        .btn-edit:hover {
            background: #f0edff;
            transform: translateY(-2px);
        }

        .schedule-strip {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            border: 1px solid #eee;
        }

        .s-label {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .s-value {
            color: #2d3436;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .divider {
            width: 1px;
            height: 38px;
            background: #eee;
        }

        .working-badge,
        .off-badge {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-block;
        }

        .working-badge {
            background: #d4edda;
            color: #155724;
        }

        .off-badge {
            background: #f0f0f0;
            color: #666;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 20px;
            margin-bottom: 26px;
        }

        .info-card {
            background: white;
            border-radius: 18px;
            padding: 24px 18px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            border: 1px solid #eee;
            text-align: center;
            transition: 0.2s;
        }

        .info-card:hover {
            transform: translateY(-4px);
            border-color: #d6d0f9;
            box-shadow: 0 12px 26px rgba(108,92,231,0.14);
        }

        .info-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .card-icon {
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .info-card h3 {
            margin: 0 0 6px;
            font-size: 1rem;
        }

        .info-card p {
            margin: 0;
            color: #777;
            font-size: 0.85rem;
        }

        .module-badge {
            display: inline-block;
            margin-top: 10px;
            background: #e8f5e9;
            color: #2e7d32;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 700;
        }

        .section-card {
            background: white;
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            border: 1px solid #eee;
            margin-bottom: 26px;
        }

        .section-card h3 {
            margin: 0 0 6px;
            font-size: 1.35rem;
        }

        .section-card .sub {
            color: #888;
            font-size: 0.9rem;
            margin: 0 0 20px;
        }

        .appointment-list {
            display: grid;
            gap: 15px;
        }

        .appointment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 18px 20px;
            border-radius: 16px;
            background: #faf9ff;
            border: 1px solid #e5ddff;
            transition: 0.2s;
        }

        .appointment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108,92,231,0.12);
        }

        .appointment-title {
            font-weight: 900;
            color: #2d3436;
            margin-bottom: 6px;
        }

        .appointment-meta {
            color: #666;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .status-badge {
            padding: 7px 14px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.78rem;
            white-space: nowrap;
            display: inline-block;
        }

        .status-badge.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 38px 20px;
            border: 2px dashed #ddd;
            border-radius: 14px;
            background: #fafafa;
            color: #888;
        }

        .bio-label {
            display: inline-block;
            background: #f0edff;
            color: #6c5ce7;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .bio-text {
            color: #555;
            line-height: 1.7;
            margin: 0;
        }

        .edit-link {
            margin-left: auto;
        }

        .edit-link a {
            color: #6c5ce7;
            font-size: 0.88rem;
            text-decoration: none;
            font-weight: 700;
        }

        .edit-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 700px) {
            .staff-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .divider {
                display: none;
            }

            .appointment-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .staff-header h1 {
                font-size: 1.55rem;
            }
        }
    </style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="dashboard-main">

    <div class="staff-header">
        <div>
            <h1>Hello, <?= htmlspecialchars($profile['username'] ?? $username) ?>! 👋</h1>

            <p>
                <?php if (!empty($profile['specialization'])): ?>
                    Specialization:
                    <strong><?= htmlspecialchars($profile['specialization']) ?></strong>
                <?php else: ?>
                    ⚠ You haven't set your specialization yet.
                <?php endif; ?>
            </p>

            <div class="profile-row">
                <?php if (!empty($profile['email'])): ?>
                    <span class="profile-chip">📧 <?= htmlspecialchars($profile['email']) ?></span>
                <?php endif; ?>

                <?php if (!empty($profile['phone'])): ?>
                    <span class="profile-chip">📞 <?= htmlspecialchars($profile['phone']) ?></span>
                <?php endif; ?>

                <span class="profile-chip">👤 <?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></span>
            </div>
        </div>

        <a href="update_staff_profile.php" class="btn-edit">✏️ Edit Profile</a>
    </div>

    <div class="schedule-strip">
        <div>
            <div class="s-label">Today</div>
            <div class="s-value"><?= date('l, d M Y') ?></div>
        </div>

        <div class="divider"></div>

        <div>
            <div class="s-label">Status</div>
            <div>
                <?php if ($is_working_today): ?>
                    <span class="working-badge">✅ Working Today</span>
                <?php else: ?>
                    <span class="off-badge">Day Off / Not Scheduled</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_working_today): ?>
            <div class="divider"></div>
            <div>
                <div class="s-label">Shift Hours</div>
                <div class="s-value">
                    <?= fmt_time($today_start) ?> — <?= fmt_time($today_end) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="edit-link">
            <a href="update_profile.php">Edit personal info →</a>
        </div>
    </div>

    <div class="info-grid">

        <div class="info-card">
            <a href="manage_schedules.php">
                <div class="card-icon">📅</div>
                <h3>Manage Schedules</h3>
                <p>Set your working hours</p>
            </a>
        </div>

        <div class="info-card">
            <a href="service/staff_service_catalogue.php">
                <div class="card-icon">💇</div>
                <h3>Service Catalogue</h3>
                <p>Manage salon services</p>
                <span class="module-badge">✅ Active</span>
            </a>
        </div>

        <div class="info-card">
            <a href="service/timeslots.php">
                <div class="card-icon">🕒</div>
                <h3>Timeslots</h3>
                <p>Manage weekly schedule</p>
                <span class="module-badge">✅ Active</span>
            </a>
        </div>

        <div class="info-card">
            <a href="feedback/staff_feedback.php">
                <div class="card-icon">⭐</div>
                <h3>My Reviews</h3>
                <p>See customer feedback</p>
            </a>
        </div>

        <div class="info-card">
            <a href="update_staff_profile.php">
                <div class="card-icon">⚙️</div>
                <h3>Profile Settings</h3>
                <p>Update bio & specialization</p>
            </a>
        </div>

    </div>

    <div class="section-card">
        <h3>Upcoming Appointments</h3>
        <p class="sub">Your next 5 bookings assigned to you.</p>

        <?php if (!empty($bookings)): ?>
            <div class="appointment-list">
                <?php foreach ($bookings as $b): ?>
                    <div class="appointment-item">
                        <div>
                            <div class="appointment-title">
                                <?= htmlspecialchars($b['service_name'] ?? 'Service') ?>
                            </div>

                            <div class="appointment-meta">
                                Booking ID: <strong>#<?= htmlspecialchars($b['booking_id']) ?></strong><br>
                                Customer:
                                <strong><?= htmlspecialchars($b['customer_name'] ?? 'Customer') ?></strong><br>
                                Date:
                                <?= date('d M Y', strtotime($b['booking_date'])) ?><br>
                                Time:
                                <?= fmt_time($b['start_time']) ?> —
                                <?= fmt_time($b['end_time']) ?>

                                <?php if (!empty($b['notes'])): ?>
                                    <br>Notes: <?= htmlspecialchars($b['notes']) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?= status_badge($b['status']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                📋 No upcoming appointments assigned to you.
            </div>
        <?php endif; ?>
    </div>

    <div class="section-card">
        <h3>About Me</h3>

        <?php if (!empty($profile['specialization'])): ?>
            <div class="bio-label">
                <?= htmlspecialchars($profile['specialization']) ?>
            </div>
        <?php endif; ?>

        <p class="bio-text">
            <?= !empty($profile['bio']) ? nl2br(htmlspecialchars($profile['bio'])) : "No bio added yet." ?>
        </p>
    </div>

</div>

</body>
</html>