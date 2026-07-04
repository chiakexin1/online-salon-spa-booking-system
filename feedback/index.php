<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Redirect based on role
if ($_SESSION['role'] === 'customer') {
    header("Location: customer_feedbackdashboard.php");
} elseif ($_SESSION['role'] === 'admin') {
    header("Location: admin_feedbackdashboard.php");
} else {
    header("Location: staff_feedbackdashboard.php");
}
exit();
?>