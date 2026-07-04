<?php
include 'db.php';

header('Content-Type: application/json');

$result = $conn->query("
    SELECT s.*, u.username
    FROM schedules s
    JOIN users u ON s.user_id = u.user_id
");

$events = [];

while ($row = $result->fetch_assoc()) {

    $colors = ["#6c5ce7","#00b894","#fdcb6e","#e17055"];
    $color = $colors[$row['user_id'] % count($colors)];

    $events[] = [
        "id" => $row['id'],
        "title" => $row['username'],
        "start" => $row['work_date'],
        "color" => $color,
        "extendedProps" => [
            "staff_id" => $row['user_id'],
            "time" => substr($row['start_time'],0,5) . " - " . substr($row['end_time'],0,5),
            "start_time" => substr($row['start_time'],0,5),
            "end_time" => substr($row['end_time'],0,5)
        ]
    ];
}

echo json_encode($events);