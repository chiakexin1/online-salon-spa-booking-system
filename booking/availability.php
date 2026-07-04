<?php
include '../db.php';
include '../header.php';
if (isset($_GET['date']) && isset($_GET['stylist_id'])) {

    $date = $_GET['date'];
    $stylist_id = $_GET['stylist_id'];

    $result = $conn->query("
        SELECT appointment_time 
        FROM appointments
        WHERE stylist_id='$stylist_id'
        AND appointment_date='$date'
    ");

    echo "<h3>Unavailable Times:</h3>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo $row['appointment_time'] . "<br>";
        }
    } else {
        echo "No bookings for this stylist on this date.";
    }

} else {
    echo "Provide date & stylist_id in URL";
}
?>