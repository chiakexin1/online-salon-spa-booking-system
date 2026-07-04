<?php
require('../db.php');

$staff_id = $_GET['staff_id'];

$stmt = $conn->prepare("
    SELECT work_date FROM schedules
    WHERE user_id = (
        SELECT user_id FROM staff_profiles WHERE staff_id = ?
    )
");

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

$dates = [];

while ($row = $result->fetch_assoc()) {
    $dates[] = $row['work_date'];
}

echo json_encode($dates);
?>