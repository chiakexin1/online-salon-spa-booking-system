<?php
require('../db.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'admin');

/* =========================
   MONTH NAVIGATION
========================= */

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($month < 1) {
    $month = 12;
    $year--;
}

if ($month > 12) {
    $month = 1;
    $year++;
}

$currentMonth = str_pad($month, 2, "0", STR_PAD_LEFT);

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$prevMonth = $month - 1;
$prevYear = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

/* =========================
   STAFF SELECTION
========================= */

if ($is_admin) {

    if (isset($_GET['staff_id'])) {
        $staff_id = (int)$_GET['staff_id'];
    } else {

        $res = $conn->query("
            SELECT staff_id 
            FROM staff_profiles 
            LIMIT 1
        ");

        $staff_id = $res->fetch_assoc()['staff_id'];
    }

} else {

    $stmt = $conn->prepare("
        SELECT sp.staff_id
        FROM users u
        JOIN staff_profiles sp 
        ON u.user_id = sp.user_id
        WHERE u.user_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $staff_id = $row['staff_id'];
    } else {
        die("Staff profile not found.");
    }
}

/* =========================
   FETCH SCHEDULES
========================= */

$schedules = [];

$res = $conn->query("
    SELECT s.* 
    FROM schedules s
    JOIN staff_profiles sp 
    ON s.user_id = sp.user_id
    WHERE sp.staff_id = $staff_id
");

while ($row = $res->fetch_assoc()) {

    $date = $row['work_date'];
    $schedules[$date] = $row;
}

/* =========================
   DELETE TIMESLOT
========================= */

if (isset($_GET['delete'])) {

    $delete_id = (int)$_GET['delete'];

    if ($is_admin) {

        $stmt = $conn->prepare("
            DELETE FROM timeslots
            WHERE timeslot_id = ?
        ");

        $stmt->bind_param("i", $delete_id);

    } else {

        $stmt = $conn->prepare("
            DELETE FROM timeslots
            WHERE timeslot_id = ?
            AND staff_id = ?
        ");

        $stmt->bind_param("ii", $delete_id, $staff_id);
    }

    $stmt->execute();

    header("Location: timeslots.php");
    exit();
}

/* =========================
   FETCH TIMESLOTS
========================= */

$timeslots = [];

$res = $conn->query("
    SELECT t.*, s.service_name
    FROM timeslots t
    JOIN services s 
    ON t.service_id = s.service_id
    WHERE t.staff_id = $staff_id
");

while ($row = $res->fetch_assoc()) {

    $timeslots[$row['date']][] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Timeslots</title>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .day {
            background: white;
            min-height: 100px;
            border-radius: 8px;
            padding: 5px;
            border: 1px solid #ddd;
        }

        .slot {
            padding: 5px;
            border-radius: 6px;
            color: white;
            margin-top: 5px;
            cursor: pointer;
        }

        .available {
            background: #e17055;
        }

        .booked {
            background: #00b894;
        }

        button {
            padding: 10px 18px;
            background: #6c5ce7;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            text-align: center;
        }

        .modal-content {
            background: white;
            width: 400px;
            margin: 100px auto;
            padding: 25px;
            border-radius: 12px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .delete-btn {
            background: #e74c3c;
        }

        .cancel-btn {
            background: #636e72;
        }

        .month-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .month-nav a {
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
            color: #6c5ce7;
        }

    </style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="container">

    <h2>Timeslots Calendar</h2>

    <!-- MONTH NAVIGATION -->

    <div class="month-nav">

        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>&staff_id=<?= $staff_id ?>">
            &lt;
        </a>

        <h3>
            <?= date("F Y", strtotime("$year-$month-01")) ?>
        </h3>

        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>&staff_id=<?= $staff_id ?>">
            &gt;
        </a>

    </div>

    <p>
        To do any actions (Add, Edit, Delete), please click on the coloured block within the calendar to proceed.
    </p>

    <?php if ($is_admin): ?>

    <form method="GET" style="margin-bottom:20px;">

        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">

        <label>Select Staff:</label>

        <select name="staff_id" onchange="this.form.submit()">

            <?php

            $staffs = $conn->query("
                SELECT sp.staff_id, u.username
                FROM staff_profiles sp
                JOIN users u 
                ON sp.user_id = u.user_id
            ");

            while ($s = $staffs->fetch_assoc()) {

                $selected = ($s['staff_id'] == $staff_id)
                    ? "selected"
                    : "";

                echo "
                <option value='{$s['staff_id']}' $selected>

                    {$s['username']}

                </option>";
            }

            ?>

        </select>

    </form>

    <?php endif; ?>

    <div class="calendar">

        <?php

        for ($i = 1; $i <= $daysInMonth; $i++) {

            $date = "$year-$currentMonth-" .
                    str_pad($i, 2, "0", STR_PAD_LEFT);

            echo "<div class='day'>";

            echo "<strong>$i</strong><br>";

            if (isset($schedules[$date])) {

                if (isset($timeslots[$date])) {

                    foreach ($timeslots[$date] as $t) {

                        echo "
                        <div class='slot booked'
                            onclick=\"openModal(
                                '$date',
                                'booked',
                                '{$t['timeslot_id']}'
                            )\">

                            {$t['service_name']}<br>

                            {$t['start_time']} -
                            {$t['end_time']}

                        </div>";
                    }

                } else {

                    echo "
                    <div class='slot available'
                        onclick=\"openModal(
                            '$date',
                            'empty',
                            0
                        )\">

                        Available for timeslot

                    </div>";
                }
            }

            echo "</div>";
        }

        ?>

    </div>

</div>

<!-- MODAL -->

<div class="modal" id="modal">

    <div class="modal-content">

        <h2 id="modalTitle"></h2>

        <div class="modal-buttons">

            <button onclick="goAdd()">Add</button>

            <button onclick="goEdit()">Edit</button>

            <button class="delete-btn" onclick="goDelete()">
                Delete
            </button>

            <button class="cancel-btn" onclick="closeModal()">
                Cancel
            </button>

        </div>

    </div>

</div>

<script>

let selectedDate = "";
let selectedId = 0;

function openModal(date, status, id) {

    document.getElementById("modal").style.display = "block";

    selectedDate = date;
    selectedId = id;

    if (status === "empty") {

        document.getElementById("modalTitle")
            .innerText = "Add Timeslot";

    } else {

        document.getElementById("modalTitle")
            .innerText = "Manage Timeslot";
    }
}

function closeModal() {

    document.getElementById("modal").style.display = "none";
}

function goAdd() {

    window.location.href =
        "add_timeslot.php?date=" +
        selectedDate +
        "&staff_id=<?= $staff_id ?>";
}

function goEdit() {

    if (!selectedId) {

        alert("Please select a timeslot.");
        return;
    }

    window.location.href =
        "edit_timeslot.php?id=" +
        selectedId +
        "&staff_id=<?= $staff_id ?>";
}

function goDelete() {

    if (!selectedId) {

        alert("Please select a timeslot.");
        return;
    }

    if (confirm("Delete this timeslot?")) {

        window.location.href =
            "timeslots.php?delete=" + selectedId;
    }
}

</script>

</body>
</html>