<?php
require('../db.php');

$staff_id = $_GET['staff_id'];
$date = $_GET['date'];

$stmt = $conn->prepare("
    SELECT * FROM schedules
    WHERE user_id = (
        SELECT user_id FROM staff_profiles WHERE staff_id = ?
    )
    AND work_date = ?
");

$stmt->bind_param("is", $staff_id, $date);
$stmt->execute();

$result = $stmt->get_result();

echo json_encode([
    "isWorkingDay" => $result->num_rows > 0
]);
?>