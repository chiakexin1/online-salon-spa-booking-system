<?php
session_start();
include '../db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: book.php");
    exit();
}

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
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Customer profile not found.'
    ];
    header("Location: book.php");
    exit();
}

$customer_id = (int)$customer['customer_id'];
$service_id = (int)($_POST['service_id'] ?? 0);
$staff_id = (int)($_POST['staff_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';
$slot_value = $_POST['slot_value'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!$service_id || !$staff_id || !$booking_date || !$slot_value) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Please complete all required booking fields.'
    ];
    header("Location: book.php");
    exit();
}

if (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'You cannot book a past date.'
    ];
    header("Location: book.php");
    exit();
}

$slot_parts = explode('|', $slot_value);
$timeslot_id = (int)($slot_parts[0] ?? 0);
$start_time = $slot_parts[1] ?? '';

if (!$timeslot_id || !$start_time) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Invalid time slot selected.'
    ];
    header("Location: book.php");
    exit();
}

$serviceStmt = $conn->prepare("
    SELECT duration
    FROM services
    WHERE service_id = ?
    LIMIT 1
");
$serviceStmt->bind_param("i", $service_id);
$serviceStmt->execute();
$service = $serviceStmt->get_result()->fetch_assoc();

if (!$service) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Selected service not found.'
    ];
    header("Location: book.php");
    exit();
}

$durationMinutes = (int)$service['duration'];

if ($durationMinutes <= 0) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Invalid service duration.'
    ];
    header("Location: book.php");
    exit();
}

$end_time = date('H:i:s', strtotime($start_time) + ($durationMinutes * 60));

$slotCheck = $conn->prepare("
    SELECT timeslot_id
    FROM timeslots
    WHERE timeslot_id = ?
      AND service_id = ?
      AND staff_id = ?
      AND (date = ? OR date = '0000-00-00')
      AND start_time <= ?
      AND end_time >= ?
    LIMIT 1
");
$slotCheck->bind_param("iiisss", $timeslot_id, $service_id, $staff_id, $booking_date, $start_time, $end_time);
$slotCheck->execute();

if ($slotCheck->get_result()->num_rows === 0) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Selected slot is not valid for this service, stylist, or date.'
    ];
    header("Location: book.php");
    exit();
}

$conflictStmt = $conn->prepare("
    SELECT booking_id
    FROM bookings
    WHERE staff_id = ?
      AND booking_date = ?
      AND status IN ('pending','confirmed')
      AND (? < end_time AND ? > start_time)
    LIMIT 1
");
$conflictStmt->bind_param("isss", $staff_id, $booking_date, $start_time, $end_time);
$conflictStmt->execute();

if ($conflictStmt->get_result()->num_rows > 0) {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'That slot has already been taken.'
    ];
    header("Location: book.php");
    exit();
}

$insert = $conn->prepare("
    INSERT INTO bookings 
    (customer_id, staff_id, service_id, booking_date, timeslot_id, start_time, end_time, status, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
");

$insert->bind_param(
    "iiisisss",
    $customer_id,
    $staff_id,
    $service_id,
    $booking_date,
    $timeslot_id,
    $start_time,
    $end_time,
    $notes
);

if ($insert->execute()) {
    $_SESSION['booking_flash'] = [
        'type' => 'success-box',
        'msg' => 'Booking confirmed successfully.'
    ];
    header("Location: my_history.php");
    exit();
} else {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Failed to save booking: ' . $conn->error
    ];
    header("Location: book.php");
    exit();
}
?>