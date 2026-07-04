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
   GET DATE (from calendar)
========================= */
$date = $_GET['date'] ?? '';

/* =========================
   GET STAFF ID
========================= */
if ($is_admin) {

    // Admin must select staff
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $staff_id = $_POST['staff_id'];
    } else {
        $staff_id = $_GET['staff_id'] ?? '';
    }

    if (!$staff_id) {
        die("No staff selected.");
    }

} else {

    // Staff → own staff_id
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
   HANDLE FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date = $_POST['work_date'];
    $service_id = $_POST['service_id'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    if (!$date || !$service_id || !$start || !$end) {
        die("All fields required.");
    }

    if (strtotime($end) <= strtotime($start)) {
        die("End time must be after start time.");
    }

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO timeslots (staff_id, service_id, date, start_time, end_time)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $staff_id, $service_id, $date, $start, $end);
    $stmt->execute();

    header("Location: timeslots.php?staff_id=" . $staff_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Timeslot</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include '../header.php'; ?>

<div class="container">

<div class="actions" style="margin-bottom:1rem;">
        <a class="btn" href="timeslots.php">&larr; Timeslots</a>
    </div>

    <h2>Add Timeslot</h2>

    <div class="panel">

        <form method="POST">

            <!-- ADMIN STAFF SELECT -->
            <?php if ($is_admin): ?>
                <label>Staff</label>
                <select name="staff_id" required>
                    <?php
                    $staffs = $conn->query("
                        SELECT sp.staff_id, u.username
                        FROM staff_profiles sp
                        JOIN users u ON sp.user_id = u.user_id
                    ");

                    while ($s = $staffs->fetch_assoc()) {

                        $selected = ($s['staff_id'] == $staff_id) ? "selected" : "";

                        echo "<option value='{$s['staff_id']}' $selected>
                                {$s['username']}
                              </option>";
                    }
                    ?>
                </select>
                <br><br>
            <?php endif; ?>

            <!-- DATE -->
            <label>Date</label>
            <input type="date" name="work_date" value="<?= htmlspecialchars($date) ?>" required>
            <br><br>

            <!-- SERVICE -->
            <label>Service</label>
            <select name="service_id" required>
                <?php
                $services = $conn->query("SELECT * FROM services");

                while ($s = $services->fetch_assoc()) {
                    echo "<option value='{$s['service_id']}'>
                            {$s['service_name']}
                          </option>";
                }
                ?>
            </select>
            <br><br>

            <!-- TIME -->
            <label>Start Time</label>
            <input type="time" name="start_time" required>
            <br><br>

            <label>End Time</label>
            <input type="time" name="end_time" required>
            <br><br>

            <button type="submit">Add</button>

        </form>

    </div>

</div>

</body>
</html>