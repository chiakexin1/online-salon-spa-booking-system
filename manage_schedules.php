<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_uid = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'admin');

$message = "";
$msg_type = "";

// Delete schedule
if (isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];

    if ($is_admin) {
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id=?");
        $stmt->bind_param("i", $schedule_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $schedule_id, $current_uid);
    }

    $stmt->execute();
    $message = "Schedule deleted!";
    $msg_type = "success";
}

// Update schedule
if (isset($_POST['update_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $work_date = $_POST['edit_date'];
    $start = $_POST['edit_start_time'];
    $end = $_POST['edit_end_time'];

    if ($is_admin) {
        $staff_id = $_POST['edit_staff_id'];
    } else {
        $staff_id = $current_uid;
    }

    if ($end <= $start) {
        $message = "End time must be later than start time.";
        $msg_type = "error";
    } else {
        if ($is_admin) {
            $stmt = $conn->prepare("
                UPDATE schedules 
                SET user_id=?, work_date=?, start_time=?, end_time=?
                WHERE id=?
            ");
            $stmt->bind_param("isssi", $staff_id, $work_date, $start, $end, $schedule_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE schedules 
                SET work_date=?, start_time=?, end_time=?
                WHERE id=? AND user_id=?
            ");
            $stmt->bind_param("sssii", $work_date, $start, $end, $schedule_id, $current_uid);
        }

        $stmt->execute();
        $message = "Schedule updated!";
        $msg_type = "success";
    }
}

// Save new schedule
if (isset($_POST['save_schedule'])) {

    $staff_id = $is_admin ? $_POST['staff_id'] : $current_uid;

    $dates = explode(",", $_POST['dates']);
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];

    if ($end <= $start) {
        $message = "End time must be later than start time.";
        $msg_type = "error";
    } else {

        foreach ($dates as $date) {

            $date = trim($date);

            $check = $conn->prepare("
                SELECT id FROM schedules 
                WHERE user_id = ? AND work_date = ?
            ");
            $check->bind_param("is", $staff_id, $date);
            $check->execute();

            if ($check->get_result()->num_rows == 0) {

                $stmt = $conn->prepare("
                    INSERT INTO schedules (user_id, work_date, start_time, end_time)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $staff_id, $date, $start, $end);
                $stmt->execute();
            }
        }

        $message = "Schedule saved!";
        $msg_type = "success";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Schedule</title>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        #calendar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 40px;
        }

        .schedule-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 450px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        input, select {
            padding: 10px;
            width: 100%;
            margin-top: 8px;
            box-sizing: border-box;
        }

        input[type="time"] {
            width: 48%;
        }

        button {
            padding: 10px 18px;
            background: #6c5ce7;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .success {
            color: green;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
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
            margin-top: 15px;
        }

        .delete-btn {
            background: #e74c3c;
        }

        .cancel-btn {
            background: #636e72;
        }
    </style>
</head>

<body>

<?php include 'header.php'; ?>

<div id="calendar"></div>

<div class="schedule-form">

    <h3><?= $is_admin ? "Add Schedule (Multi-Date)" : "My Schedule" ?></h3>

    <?php if (!empty($message)): ?>
        <p class="<?= $msg_type ?>"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">

        <?php if ($is_admin): ?>
            <label>Select Stylist</label>
            <select name="staff_id" required>
                <option value="">Select Stylist</option>
                <?php
                $res = $conn->query("SELECT user_id, username FROM users WHERE role='stylist'");
                while ($row = $res->fetch_assoc()):
                ?>
                    <option value="<?= $row['user_id'] ?>">
                        <?= $row['username'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        <?php else: ?>
            <input type="hidden" name="staff_id" value="<?= $current_uid ?>">
        <?php endif; ?>

        <br><br>

        <label>Select Dates</label>
        <input type="text" id="datePicker" name="dates" placeholder="Select multiple dates" required>

        <br><br>

        <label>Working Time</label><br>
        <input type="time" name="start_time" value="09:00" required>
        <input type="time" name="end_time" value="17:00" required>

        <br><br>

        <button type="submit" name="save_schedule">Save Schedule</button>

    </form>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Schedule</h3>

        <form method="POST">
            <input type="hidden" id="schedule_id" name="schedule_id">
            <?php if ($is_admin): ?>
                <label>Staff</label>
                <select id="edit_staff_id" name="edit_staff_id" required>
                    <option value="">Select Stylist</option>
                    <?php
                    $res2 = $conn->query("SELECT user_id, username FROM users WHERE role='stylist'");
                    while ($row2 = $res2->fetch_assoc()):
                    ?>
                        <option value="<?= $row2['user_id'] ?>">
                            <?= $row2['username'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <br><br>
            <?php endif; ?>
            
            <label>Date</label>
            <input type="date" id="edit_date" name="edit_date" required>

            <br><br>

            <label>Start Time</label>
            <input type="time" id="edit_start_time" name="edit_start_time" required>

            <br><br>

            <label>End Time</label>
            <input type="time" id="edit_end_time" name="edit_end_time" required>

            <div class="modal-buttons">
                <button type="submit" name="update_schedule">Update</button>

                <button type="submit" name="delete_schedule" class="delete-btn"
                        onclick="return confirm('Are you sure you want to delete this schedule?')">
                    Delete
                </button>

                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 650,
        events: 'fetch_schedule.php',

        eventContent: function(arg) {
            return {
                html: `<b>${arg.event.title}</b><br>${arg.event.extendedProps.time}`
            };
        },

        eventClick: function(info) {
            document.getElementById('schedule_id').value = info.event.id;
            document.getElementById('edit_date').value = info.event.startStr;
            document.getElementById('edit_start_time').value = info.event.extendedProps.start_time;
            document.getElementById('edit_end_time').value = info.event.extendedProps.end_time;

            <?php if ($is_admin): ?>
            document.getElementById('edit_staff_id').value = info.event.extendedProps.staff_id;
            <?php endif; ?>

            document.getElementById('editModal').style.display = 'block';
        }
    });

    calendar.render();

    flatpickr("#datePicker", {
        mode: "multiple",
        dateFormat: "Y-m-d"
    });

});

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>