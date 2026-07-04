<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Panel - Salon System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-main {
            max-width: 1000px; /* FIX: was missing semicolon, so margin below was ignored */
            margin: 40px auto;
            padding: 20px;
        }
        .welcome-section {
            margin-bottom: 30px;
            text-align: center;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .menu-card {
            background: white;
            padding: 30px 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1px solid #eee;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(108, 92, 231, 0.2);
            border-color: var(--primary-color);
        }
        .menu-card h3 { margin: 10px 0 5px; color: #333; }
        .menu-card p  { color: #777; font-size: 0.9rem; margin: 0; }
        .icon-box {
            width: 60px;
            height: 60px;
            background: #f0edff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="admin-main">
        <div class="welcome-section">
            <h1>Admin Control Panel</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>. What would you like to manage today?</p>
        </div>

        <div class="admin-grid">
            <a href="user_management.php" class="menu-card">
                <div class="icon-box">👥</div>
                <h3>User Management</h3>
                <p>View, edit, and delete customers or staff members.</p>
            </a>

            <a href="manage_schedules.php" class="menu-card">
                <div class="icon-box">🗓️</div>
                <h3>Staff Schedules</h3>
                <p>Set and manage stylist working days.</p>
            </a>
            
            <a href="booking/manage_bookings.php" class="menu-card">
                <div class="icon-box">📅</div>
                <h3>Appointments</h3>
                <p>Manage customer bookings and salon schedule.</p>
            </a>

            <!-- ✅ INTEGRATED: Links to Service Module -->
            <a href="service/staff_service_catalogue.php" class="menu-card">
                <div class="icon-box">💇</div>
                <h3>Service Catalogue</h3>
                <p>Add, edit, and manage salon services and pricing.</p>
            </a>

            <!-- ✅ INTEGRATED: Links to Timeslots in Service Module -->
            <a href="service/timeslots.php" class="menu-card">
                <div class="icon-box">🕒</div>
                <h3>Staff Timeslots</h3>
                <p>Set and manage stylist working hours by day.</p>
            </a>
            
            <a href="feedback/admin_feedbackdashboard.php" class="menu-card">
                <div class="icon-box">📊</div>
                <h3>Reports & Feedback</h3>
                <p>Check business performance and customer reviews.</p>
            </a>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <p style="color: #999; font-size: 0.85rem;">Salon & Spa System v1.0</p>
        </div>
    </div>
</body>
</html>