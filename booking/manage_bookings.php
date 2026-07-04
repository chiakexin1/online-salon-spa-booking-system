<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

/* DELETE BOOKING */
if (isset($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];

    $delete = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $delete->bind_param("i", $booking_id);

    if ($delete->execute()) {
        $success = "Booking deleted successfully.";
    } else {
        $error = "Failed to delete booking.";
    }
}

/* UPDATE BOOKING */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);

    $allowed_status = ['pending', 'confirmed', 'cancelled', 'completed'];

    if (!in_array($status, $allowed_status)) {
        $error = "Invalid booking status.";
    } else {
        $update = $conn->prepare("
            UPDATE bookings
            SET status = ?,
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE booking_id = ?
        ");
        $update->bind_param("ssi", $status, $notes, $booking_id);

        if ($update->execute()) {
            $success = "Booking updated successfully.";
        } else {
            $error = "Failed to update booking.";
        }
    }
}

/* FETCH BOOKINGS */
$sql = "
    SELECT 
        b.booking_id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.notes,
        cu.username AS customer_name,
        su.username AS stylist_name,
        s.service_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN users cu ON c.user_id = cu.user_id
    JOIN staff_profiles sp ON b.staff_id = sp.staff_id
    JOIN users su ON sp.user_id = su.user_id
    JOIN services s ON b.service_id = s.service_id
    ORDER BY b.booking_date DESC, b.start_time DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        .container {
            max-width: 1250px;
            margin: 45px auto;
            background: white;
            padding: 32px;
            border-radius: 22px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #2d3436;
        }

        .success-box,
        .error-box {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .success-box {
            background: #d4edda;
            color: #155724;
        }

        .error-box {
            background: #ffe1e1;
            color: #b00020;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
            background: white;
        }

        th {
            background: linear-gradient(135deg, #6c5ce7, #7d6df2);
            color: white;
            padding: 16px;
            text-align: left;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #eee;
            color: #333;
            vertical-align: middle;
        }

        tr:hover td {
            background: #faf9ff;
        }

        select,
        input[type="text"] {
            padding: 9px 10px;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: #fafafa;
            width: 100%;
            box-sizing: border-box;
        }

        select:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 3px rgba(108,92,231,0.12);
        }

        .badge {
            padding: 7px 13px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 800;
            display: inline-block;
        }

        .confirmed {
            background: #e6f7ec;
            color: #16803c;
        }

        .pending {
            background: #fff4d6;
            color: #9a6a00;
        }

        .cancelled {
            background: #ffe1e1;
            color: #b00020;
        }

        .completed {
            background: #e1f3ff;
            color: #00649b;
        }

        .action-form {
            display: flex;
            gap: 8px;
            align-items: center;
            min-width: 360px;
        }

        .btn-save,
        .btn-delete,
        .back {
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .btn-save {
            background: #6c5ce7;
            color: white;
        }

        .btn-delete {
            background: #ffe1e1;
            color: #b00020;
        }

        .btn-save:hover {
            background: #5848d8;
        }

        .btn-delete:hover {
            background: #ffcccc;
        }

        .back {
            margin-top: 22px;
            background: #f0edff;
            color: #6c5ce7;
        }

        .back:hover {
            background: #6c5ce7;
            color: white;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 40px;
            border: 2px dashed #ddd;
            border-radius: 18px;
        }
    </style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="container">
    <h1>Manage Bookings</h1>

    <?php if ($success): ?>
        <div class="success-box"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Stylist</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>

                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($row['booking_id']) ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                        <td><?= htmlspecialchars($row['stylist_name']) ?></td>
                        <td><?= date('d M Y', strtotime($row['booking_date'])) ?></td>
                        <td>
                            <?= date('g:i A', strtotime($row['start_time'])) ?>
                            -
                            <?= date('g:i A', strtotime($row['end_time'])) ?>
                        </td>

                        <form method="POST">
                            <td>
                                <select name="status">
                                    <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $row['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </td>

                            <td>
                                <input 
                                    type="text" 
                                    name="notes" 
                                    value="<?= htmlspecialchars($row['notes'] ?? '') ?>" 
                                    placeholder="No notes"
                                >
                            </td>

                            <td>
                                <input type="hidden" name="booking_id" value="<?= (int)$row['booking_id'] ?>">

                                <div class="action-form">
                                    <button type="submit" name="update_booking" class="btn-save">
                                        Save
                                    </button>

                                    <a 
                                        href="manage_booking.php?delete=<?= (int)$row['booking_id'] ?>" 
                                        class="btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this booking?');"
                                    >
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

    <?php else: ?>

        <div class="empty">
            No bookings found.
        </div>

    <?php endif; ?>

    <a href="../admin_dashboard.php" class="back">Back to Dashboard</a>
</div>

</body>
</html>