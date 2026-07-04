<?php
include '../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$search       = $data['search'] ?? '';
$location     = $data['location'] ?? '';
$service_type = $data['service_type'] ?? '';
$availability = $data['availability'] ?? '';

$query = "SELECT * FROM services WHERE 1=1";

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (
        service_name LIKE '%$search%' OR
        description LIKE '%$search%' OR
        location LIKE '%$search%'
    )";
}

if (!empty($location)) {
    $location = mysqli_real_escape_string($conn, $location);
    $query .= " AND location = '$location'";
}

if (!empty($service_type)) {
    $service_type = mysqli_real_escape_string($conn, $service_type);
    $query .= " AND service_type = '$service_type'";
}

if (!empty($availability)) {
    $availability = mysqli_real_escape_string($conn, $availability);
    $query .= " AND availability = '$availability'";
}

$query .= " ORDER BY service_name ASC";

$result = mysqli_query($conn, $query);

$services = [];

while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

echo json_encode($services);
exit;