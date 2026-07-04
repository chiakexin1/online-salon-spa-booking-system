<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* Fetch user profile */
$stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$username = $user['username'] ?? ($_SESSION['username'] ?? 'Customer');

/* Fetch customer preferences */
$prefs = [
    "fav_service_name" => null,
    "fav_stylist_name" => null,
    "fav_stylist_spec" => null,
    "notes" => null
];

try {
    $stmt_p = $conn->prepare("
        SELECT 
            c.customer_id,
            c.notes,
            s.service_name AS fav_service_name,
            u.username AS fav_stylist_name,
            sp.specialization AS fav_stylist_spec
        FROM customers c
        LEFT JOIN services s ON c.favourite_service = s.service_id
        LEFT JOIN users u ON c.favourite_stylist = u.user_id
        LEFT JOIN staff_profiles sp ON u.user_id = sp.user_id
        WHERE c.user_id = ?
    ");
    $stmt_p->bind_param("i", $user_id);
    $stmt_p->execute();
    $customerData = $stmt_p->get_result()->fetch_assoc();

    if ($customerData) {
        $prefs = $customerData;
        $customer_id = (int)$customerData['customer_id'];
    } else {
        $customer_id = 0;
    }
} catch (mysqli_sql_exception $e) {
    $customer_id = 0;
}

/* Fetch upcoming appointments */
$upcoming = [];

if ($customer_id > 0) {
    try {
        $stmt_b = $conn->prepare("
            SELECT 
                b.booking_id,
                b.booking_date,
                b.start_time,
                b.end_time,
                b.status,
                s.service_name,
                u.username AS stylist_name
            FROM bookings b
            JOIN services s ON b.service_id = s.service_id
            JOIN staff_profiles sp ON b.staff_id = sp.staff_id
            JOIN users u ON sp.user_id = u.user_id
            WHERE b.customer_id = ?
              AND b.booking_date >= CURDATE()
              AND b.status IN ('pending','confirmed')
            ORDER BY b.booking_date ASC, b.start_time ASC
            LIMIT 3
        ");
        $stmt_b->bind_param("i", $customer_id);
        $stmt_b->execute();
        $result_b = $stmt_b->get_result();

        while ($row = $result_b->fetch_assoc()) {
            $upcoming[] = $row;
        }
    } catch (mysqli_sql_exception $e) {
        $upcoming = [];
    }
}

/* Reviews count */
$review_count = 0;

try {
    $stmt_r = $conn->prepare("SELECT COUNT(*) AS total FROM feedback WHERE customer_id = ?");
    $stmt_r->bind_param("i", $customer_id);
    $stmt_r->execute();
    $review_count = $stmt_r->get_result()->fetch_assoc()['total'] ?? 0;
} catch (mysqli_sql_exception $e) {
    $review_count = 0;
}

function statusBadge($status) {
    $color = "#6c5ce7";
    $bg = "#f0edff";

    if ($status === "confirmed") {
        $color = "#16803c";
        $bg = "#e6f7ec";
    }

    if ($status === "pending") {
        $color = "#9a6a00";
        $bg = "#fff4d6";
    }

    if ($status === "cancelled") {
        $color = "#b00020";
        $bg = "#ffe1e1";
    }

    if ($status === "completed") {
        $color = "#0c5460";
        $bg = "#d1ecf1";
    }

    return "<span style='background:$bg;color:$color;padding:5px 12px;border-radius:999px;font-size:0.8rem;font-weight:700;'>"
        . htmlspecialchars(ucfirst($status)) .
        "</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard - Salon System</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .dashboard-main {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #6c5ce7, #8e7cff);
            color: white;
            padding: 32px;
            border-radius: 22px;
            box-shadow: 0 12px 35px rgba(108,92,231,0.25);
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .welcome-card h1 {
            margin: 0;
            font-size: 2rem;
        }

        .welcome-card p {
            margin: 8px 0 0;
            opacity: 0.9;
        }

        .profile-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .chip {
            background: rgba(255,255,255,0.18);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
        }

        .avatar {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 26px 20px;
            border-radius: 18px;
            text-decoration: none;
            color: #2d3436;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            border: 1px solid #eee;
            transition: 0.2s;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(108,92,231,0.18);
            border-color: #6c5ce7;
        }

        .action-card .icon {
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .action-card h3 {
            margin: 0 0 8px;
            color: #2d3436;
        }

        .action-card p {
            margin: 0;
            color: #777;
            font-size: 0.9rem;
        }

        .section-card {
            background: white;
            padding: 28px;
            border-radius: 22px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border: 1px solid #eee;
            margin-bottom: 26px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
        }

        .section-header h2 {
            margin: 0;
            color: #2d3436;
            font-size: 1.25rem;
        }

        .section-header a {
            color: #6c5ce7;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .pref-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .pref-box {
            flex: 1;
            min-width: 180px;
            background: #faf9ff;
            border: 1px solid #e5ddff;
            border-radius: 16px;
            padding: 16px;
        }

        .pref-label {
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 6px;
        }

        .pref-value {
            font-weight: 800;
            color: #2d3436;
        }

        .booking-list {
            display: grid;
            gap: 14px;
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            padding: 16px;
            background: #faf9ff;
            border: 1px solid #e5ddff;
            border-radius: 16px;
        }

        .booking-title {
            font-weight: 800;
            color: #2d3436;
        }

        .booking-meta {
            color: #777;
            font-size: 0.9rem;
            margin-top: 4px;
            line-height: 1.5;
        }

        .empty-state {
            text-align: center;
            padding: 35px 20px;
            border: 2px dashed #ddd;
            border-radius: 18px;
            color: #888;
        }

        .btn-main {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 18px;
            border-radius: 12px;
            background: #6c5ce7;
            color: white;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 700px) {
            .booking-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .avatar {
                display: none;
            }
        }
    </style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="dashboard-main">

    <div class="welcome-card">
        <div>
            <h1>Welcome, <?= htmlspecialchars($username) ?> 👋</h1>
            <p>Manage your bookings, profile, preferences, and feedback from here.</p>

            <div class="profile-chips">
                <span class="chip">📧 <?= htmlspecialchars($user['email'] ?? '') ?></span>
                <span class="chip">📞 <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'No phone' ?></span>
                <span class="chip">👤 Customer</span>
            </div>
        </div>

        <div class="avatar">🧖</div>
    </div>

    <div class="action-grid">

        <a href="booking/book.php" class="action-card">
            <div class="icon">📅</div>
            <h3>Book Appointment</h3>
            <p>Make a new appointment</p>
        </a>

        <a href="update_profile.php" class="action-card">
            <div class="icon">👤</div>
            <h3>My Profile</h3>
            <p>Update your information</p>
        </a>

        <a href="booking/my_history.php" class="action-card">
            <div class="icon">🕒</div>
            <h3>Booking History</h3>
            <p>View past treatments</p>
        </a>

        <a href="feedback/customer_feedbackdashboard.php" class="action-card">
            <div class="icon">⭐</div>
            <h3>Feedback</h3>
            <p>Rate your experiences</p>
        </a>

    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>💖 My Preferences</h2>
            <a href="update_profile.php">Edit preferences →</a>
        </div>

        <?php if ($prefs && ($prefs["fav_service_name"] || $prefs["fav_stylist_name"] || $prefs["notes"])): ?>
            <div class="pref-grid">

                <?php if ($prefs["fav_service_name"]): ?>
                    <div class="pref-box">
                        <div class="pref-label">Favourite Service</div>
                        <div class="pref-value"><?= htmlspecialchars($prefs["fav_service_name"]) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($prefs["fav_stylist_name"]): ?>
                    <div class="pref-box">
                        <div class="pref-label">Favourite Stylist</div>
                        <div class="pref-value"><?= htmlspecialchars($prefs["fav_stylist_name"]) ?></div>

                        <?php if (!empty($prefs["fav_stylist_spec"])): ?>
                            <div style="color:#888; font-size:0.85rem; margin-top:4px;">
                                <?= htmlspecialchars($prefs["fav_stylist_spec"]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($prefs["notes"]): ?>
                    <div class="pref-box" style="flex:2;">
                        <div class="pref-label">My Notes</div>
                        <div style="color:#555; line-height:1.5;">
                            <?= nl2br(htmlspecialchars($prefs["notes"])) ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No preferences set yet.</p>
                <a href="update_profile.php" class="btn-main">Set your preferences →</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>🗓️ Upcoming Appointments</h2>
            <a href="booking/my_history.php">View all →</a>
        </div>

        <?php if (count($upcoming) === 0): ?>

            <div class="empty-state">
                <p>You have no upcoming appointments.</p>
                <a href="booking/book.php" class="btn-main">Try booking a service →</a>
            </div>

        <?php else: ?>

            <div class="booking-list">
                <?php foreach ($upcoming as $b): ?>
                    <div class="booking-item">
                        <div>
                            <div class="booking-title">
                                <?= htmlspecialchars($b['service_name']) ?>
                            </div>

                            <div class="booking-meta">
                                Stylist: <?= htmlspecialchars($b['stylist_name']) ?><br>
                                <?= date('d M Y', strtotime($b['booking_date'])) ?>,
                                <?= date('g:i A', strtotime($b['start_time'])) ?> -
                                <?= date('g:i A', strtotime($b['end_time'])) ?>
                            </div>
                        </div>

                        <div>
                            <?= statusBadge($b['status']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    
</div>

</body>
</html>