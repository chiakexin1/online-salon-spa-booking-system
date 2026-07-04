<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$booking_id = (int)($_POST['booking_id'] ?? 0);
$service_id = (int)($_POST['service_id'] ?? 0);
$staff_id = (int)($_POST['staff_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$customer_id = (int)$_SESSION['user_id'];

if (!$booking_id || !$service_id || !$staff_id || !$booking_date || !$start_time) {
    header("Location: my_history.php");
    exit();
}

$serviceStmt = $conn->prepare("SELECT duration FROM services WHERE service_id = ?");
$serviceStmt->bind_param("i", $service_id);
$serviceStmt->execute();
$service = $serviceStmt->get_result()->fetch_assoc();

if (!$service) {
    header("Location: my_history.php");
    exit();
}

$durationMinutes = (int)$service['duration'];
$end_time = date('H:i:s', strtotime($start_time) + ($durationMinutes * 60));

$conflict = $conn->prepare("
    SELECT booking_id
    FROM bookings
    WHERE staff_id = ?
      AND booking_date = ?
      AND booking_id != ?
      AND status IN ('pending','confirmed')
      AND (? < end_time AND ? > start_time)
");
$conflict->bind_param("isiss", $staff_id, $booking_date, $booking_id, $start_time, $end_time);
$conflict->execute();

if ($conflict->get_result()->num_rows > 0) {
    $_SESSION['booking_flash'] = ['type' => 'error-box', 'msg' => 'That new slot is already taken.'];
    header("Location: my_history.php");
    exit();
}

$update = $conn->prepare("
    UPDATE bookings
    SET booking_date = ?, start_time = ?, end_time = ?, updated_at = CURRENT_TIMESTAMP
    WHERE booking_id = ? AND customer_id = ?
");
$update->bind_param("sssii", $booking_date, $start_time, $end_time, $booking_id, $customer_id);
$update->execute();

$_SESSION['booking_flash'] = ['type' => 'success-box', 'msg' => 'Booking rescheduled successfully.'];
header("Location: my_history.php");
exit();