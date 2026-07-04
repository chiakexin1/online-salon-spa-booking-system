<?php
session_start();
include '../db.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

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
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Customer profile not found.'
    ];
    header("Location: my_history.php");
    exit();
}

$customer_id = (int)$customer['customer_id'];

$stmt = $conn->prepare("
    UPDATE bookings
    SET status = 'completed',
        updated_at = CURRENT_TIMESTAMP
    WHERE booking_id = ?
      AND customer_id = ?
      AND status = 'confirmed'
");
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['booking_flash'] = [
        'type' => 'success-box',
        'msg' => 'Service completed. You can now give feedback.'
    ];
} else {
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Unable to complete this booking.'
    ];
}

header("Location: ../feedback/customer_feedback.php");
exit();
?>