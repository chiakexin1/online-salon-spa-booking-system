<?php
ob_clean();
header('Content-Type: application/json');

include '../db.php';

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

if ($service_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid service selected",
        "stylists" => []
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT DISTINCT sp.staff_id, u.username
    FROM timeslots t
    JOIN staff_profiles sp ON t.staff_id = sp.staff_id
    JOIN users u ON sp.user_id = u.user_id
    WHERE t.service_id = ?
    ORDER BY u.username
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "SQL error: " . $conn->error,
        "stylists" => []
    ]);
    exit();
}

$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

$stylists = [];

while ($row = $result->fetch_assoc()) {
    $stylists[] = [
        "staff_id" => $row["staff_id"],
        "username" => $row["username"]
    ];
}

echo json_encode([
    "success" => true,
    "stylists" => $stylists
]);
exit();
?>