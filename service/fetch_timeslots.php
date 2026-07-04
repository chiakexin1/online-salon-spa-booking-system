<?php
require('../db.php');

$staff_id = $_GET['staff_id'];
$date = $_GET['date'];

$stmt = $conn->prepare("
    SELECT timeslot_id, start_time, end_time
    FROM timeslots
    WHERE staff_id = ?
    AND work_date = ?
    ORDER BY start_time
");

$stmt->bind_param("is", $staff_id, $date);
$stmt->execute();

$result = $stmt->get_result();

$slots = [];

while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

echo json_encode($slots);
?>