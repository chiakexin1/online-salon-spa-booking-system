<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT a.*, s.service_name
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    WHERE a.user_id = '$user_id'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments</title>
    <link rel="stylesheet" href="../appoinment.css">
</head>
<body>
<?php include '../header.php'; ?>
<div class="container">
    <h2>My Appointments</h2>

    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "
            <div class='appointment-item'>
                Service: {$row['service_name']} <br>
                Date: {$row['appointment_date']} <br>
                Time: {$row['appointment_time']} <br>
                Status: {$row['status']} <br>

                <a href='cancel_booking.php?id={$row['appointment_id']}' class='cancel-btn'>
                    Cancel
                </a>
            </div>
            ";
        }
    } else {
        echo "No appointments found.";
    }
    ?>

</div>

</body>
</html>