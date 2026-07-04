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
    $_SESSION['booking_flash'] = [
        'type' => 'error-box',
        'msg' => 'Customer profile not found.'
    ];
    header("Location: my_history.php");
    exit();
}

$customer_id = (int)$customer['customer_id'];

if ($booking_id > 0) {
    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = 'cancelled',
            updated_at = CURRENT_TIMESTAMP
        WHERE booking_id = ?
          AND customer_id = ?
          AND status IN ('pending', 'confirmed')
    ");

    $stmt->bind_param("ii", $booking_id, $customer_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['booking_flash'] = [
            'type' => 'success-box',
            'msg' => 'Booking cancelled successfully.'
        ];
    } else {
        $_SESSION['booking_flash'] = [
            'type' => 'error-box',
            'msg' => 'Booking could not be cancelled.'
        ];
    }
}

header("Location: my_history.php");
exit();
?>