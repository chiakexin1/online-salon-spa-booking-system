<?php
require('../db.php');

$id = intval($_GET['id']);

$query = "
    SELECT s.*, 
        EXISTS (
            SELECT 1 FROM timeslots t 
            WHERE t.service_id = s.service_id
        ) AS available
    FROM services s
    WHERE s.service_id = $id
";

$result = mysqli_query($conn, $query);
$r = mysqli_fetch_assoc($result);

if (!$r) {
    echo "Service not found.";
    exit;
}

echo "
<img src='uploads/{$r['image']}' width='150'>
<h3>Name: {$r['service_name']}</h3>
<p>Description: {$r['description']}</p>
<p>Duration: {$r['duration']} min</p>
<p>Price: RM{$r['price']}</p>
<p>Location: {$r['location']}</p>
<p>Type: {$r['service_type']}</p>
<p>Promotion: {$r['promotion']}%</p>
<p>Status: " . ($r['available'] ? "Available" : "Unavailable") . "</p>
";
?>