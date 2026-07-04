<?php
session_start();
include '../db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

if (ob_get_length()) {
    ob_clean();
}

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$booking_date = $_GET['booking_date'] ?? '';

if (!$service_id || !$staff_id || !$booking_date) {
    echo json_encode([
        "success" => false,
        "message" => "Missing service, stylist, or date",
        "slots" => []
    ]);
    exit();
}

if (!strtotime($booking_date)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid booking date",
        "slots" => []
    ]);
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
    echo json_encode([
        "success" => false,
        "message" => "Service not found",
        "slots" => []
    ]);
    exit();
}

$durationMinutes = (int)$service['duration'];

if ($durationMinutes <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid service duration",
        "slots" => []
    ]);
    exit();
}

/*
    Your timeslots table uses `date`.
    This version:
    - reads timeslots by selected service, staff, and date
    - also supports old/default date = '0000-00-00'
    - splits slots based on service duration, not every 30 minutes
    Example:
    11:30 - 17:30, duration 120 min
    output:
    11:30 - 13:30
    13:30 - 15:30
    15:30 - 17:30
*/
$slotStmt = $conn->prepare("
    SELECT timeslot_id, start_time, end_time
    FROM timeslots
    WHERE service_id = ?
      AND staff_id = ?
      AND (date = ? OR date = '0000-00-00')
    ORDER BY start_time ASC
");
$slotStmt->bind_param("iis", $service_id, $staff_id, $booking_date);
$slotStmt->execute();
$slotResult = $slotStmt->get_result();

$available = [];
$durationSeconds = $durationMinutes * 60;

while ($slot = $slotResult->fetch_assoc()) {
    $timeslot_id = (int)$slot['timeslot_id'];

    $shiftStart = strtotime($booking_date . ' ' . $slot['start_time']);
    $shiftEnd = strtotime($booking_date . ' ' . $slot['end_time']);

    if (!$shiftStart || !$shiftEnd || $shiftEnd <= $shiftStart) {
        continue;
    }

    for ($t = $shiftStart; ($t + $durationSeconds) <= $shiftEnd; $t += $durationSeconds) {
        $start = date('H:i:s', $t);
        $end = date('H:i:s', $t + $durationSeconds);

        if ($booking_date === date('Y-m-d') && $t <= time()) {
            continue;
        }

        $check = $conn->prepare("
            SELECT booking_id
            FROM bookings
            WHERE staff_id = ?
              AND booking_date = ?
              AND status IN ('pending','confirmed')
              AND (? < end_time AND ? > start_time)
            LIMIT 1
        ");
        $check->bind_param("isss", $staff_id, $booking_date, $start, $end);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $available[] = [
                "timeslot_id" => $timeslot_id,
                "start" => $start,
                "end" => $end,
                "label" => date('g:i A', strtotime($start)) . " - " . date('g:i A', strtotime($end))
            ];
        }
    }
}

echo json_encode([
    "success" => true,
    "message" => count($available) > 0 ? "Slots loaded" : "No available slots",
    "slots" => $available
]);
exit();
?>