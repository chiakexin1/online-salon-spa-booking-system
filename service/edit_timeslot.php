<?php
require('../db.php');
session_start();

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'admin');

/* =========================
   GET TIMESLOT ID
========================= */
$id = $_GET['id'] ?? 0;

if (!$id) {
    die("Invalid timeslot ID.");
}

/* =========================
   GET STAFF ID (only for staff)
========================= */
if (!$is_admin) {

    $stmt = $conn->prepare("
        SELECT staff_id FROM staff_profiles WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        die("Staff profile not found.");
    }

    $staff_id = $row['staff_id'];
}

/* =========================
   FETCH TIMESLOT DATA
========================= */
if ($is_admin) {

    // Admin → no restriction
    $stmt = $conn->prepare("
        SELECT * FROM timeslots WHERE timeslot_id = ?
    ");
    $stmt->bind_param("i", $id);

} else {

    // Staff → restricted to own timeslot
    $stmt = $conn->prepare("
        SELECT * FROM timeslots 
        WHERE timeslot_id = ? AND staff_id = ?
    ");
    $stmt->bind_param("ii", $id, $staff_id);
}

$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if (!$data) {
    die("Timeslot not found or access denied.");
}

/* =========================
   HANDLE UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $service_id = $_POST['service_id'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    if (!$service_id || !$start || !$end) {
        die("All fields required.");
    }

    if (strtotime($end) <= strtotime($start)) {
        die("End time must be after start time.");
    }

    if ($is_admin) {

        // Admin → update any timeslot
        $stmt = $conn->prepare("
            UPDATE timeslots 
            SET service_id = ?, start_time = ?, end_time = ?
            WHERE timeslot_id = ?
        ");
        $stmt->bind_param("issi", $service_id, $start, $end, $id);

    } else {

        // Staff → only own timeslot
        $stmt = $conn->prepare("
            UPDATE timeslots 
            SET service_id = ?, start_time = ?, end_time = ?
            WHERE timeslot_id = ? AND staff_id = ?
        ");
        $stmt->bind_param("issii", $service_id, $start, $end, $id, $staff_id);
    }

    $stmt->execute();

    header("Location: timeslots.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Timeslot</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include '../header.php'; ?>

<div class="container">
    
    <div class="actions" style="margin-bottom:1rem;">
        <a class="btn" href="timeslots.php">&larr; Timeslots</a>
    </div>

    <h2>Edit Timeslot</h2>

    <div class="panel">

        <form method="POST">

            <!-- SERVICE -->
            <label>Service</label>
            <select name="service_id" required>
                <?php
                $services = $conn->query("SELECT * FROM services");

                while ($s = $services->fetch_assoc()) {

                    $selected = ($s['service_id'] == $data['service_id']) ? "selected" : "";

                    echo "<option value='{$s['service_id']}' $selected>
                            {$s['service_name']}
                          </option>";
                }
                ?>
            </select>

            <br><br>

            <!-- TIME -->
            <label>Start Time</label>
            <input type="time" name="start_time" value="<?= $data['start_time'] ?>" required>

            <br><br>

            <label>End Time</label>
            <input type="time" name="end_time" value="<?= $data['end_time'] ?>" required>

            <br><br>

            <button type="submit">Update</button>

        </form>

    </div>

</div>

</body>
</html>