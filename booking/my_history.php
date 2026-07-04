<?php
session_start();
include '../db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$customerStmt = $conn->prepare("
    SELECT customer_id 
    FROM customers 
    WHERE user_id = ?
    LIMIT 1
");
$customerStmt->bind_param("i", $user_id);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();

if (!$customer) {
    die("Customer profile not found.");
}

$customer_id = (int)$customer['customer_id'];

$flash = $_SESSION['booking_flash'] ?? null;
unset($_SESSION['booking_flash']);

$stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.notes,
        s.service_name,
        u.username AS stylist_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN staff_profiles sp ON b.staff_id = sp.staff_id
    JOIN users u ON sp.user_id = u.user_id
    WHERE b.customer_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

function statusBadge($status) {
    $class = "badge";

    if ($status === "confirmed") $class .= " confirmed";
    if ($status === "pending") $class .= " pending";
    if ($status === "cancelled") $class .= " cancelled";
    if ($status === "completed") $class .= " completed";

    return "<span class='$class'>" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        .page-wrap {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .history-card {
            background: #fff;
            border-radius: 24px;
            padding: 34px;
            box-shadow: 0 14px 38px rgba(0,0,0,0.08);
            border: 1px solid #eee;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 26px;
        }

        .history-header h1 {
            margin: 0;
            font-size: 2rem;
            color: #2d3436;
        }

        .history-header p {
            margin: 6px 0 0;
            color: #777;
        }

        .btn-main, .btn-soft, .btn-danger, .btn-review {
            display: inline-block;
            text-decoration: none;
            border-radius: 13px;
            padding: 11px 16px;
            font-weight: 800;
            font-size: 0.9rem;
            transition: 0.2s;
            border: none;
            text-align: center;
        }

        .btn-main {
            background: linear-gradient(135deg, #6c5ce7, #7d6df2);
            color: white;
        }

        .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108,92,231,0.3);
        }

        .btn-soft {
            background: #f0edff;
            color: #6c5ce7;
        }

        .btn-danger {
            background: #ffe1e1;
            color: #b00020;
        }

        .btn-review {
            background: #e6f7ec;
            color: #16803c;
        }

        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .error-box {
            background: #ffe1e1;
            color: #b00020;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 18px;
            border: 1px solid #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 900px;
        }

        th {
            background: #f8f7ff;
            color: #6c5ce7;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 17px;
            text-align: left;
        }

        td {
            padding: 18px 17px;
            border-top: 1px solid #f0f0f0;
            color: #333;
            vertical-align: middle;
        }

        tr:hover td {
            background: #faf9ff;
        }

        .service-name {
            font-weight: 900;
            color: #2d3436;
        }

        .muted {
            color: #888;
            font-size: 0.9rem;
        }

        .badge {
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 900;
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

        .row-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 55px 20px;
            border: 2px dashed #e2e2e2;
            border-radius: 18px;
            color: #888;
            background: #fafafa;
        }

        .empty-state .icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .bottom-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }
    </style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="page-wrap">
    <div class="history-card">

        <div class="history-header">
            <div>
                <h1>My Appointments</h1>
                <p>Track, reschedule, cancel, or mark completed bookings.</p>
            </div>

            <a href="book.php" class="btn-main">+ New Booking</a>
        </div>

        <?php if ($flash): ?>
            <div class="<?= htmlspecialchars($flash['type']) ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows === 0): ?>

            <div class="empty-state">
                <div class="icon">📅</div>
                <h3>No bookings yet</h3>
                <p>You have not made any appointment.</p>
                <br>
                <a href="book.php" class="btn-main">Book your first appointment</a>
            </div>

        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Stylist</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $appointmentEnd = strtotime($row['booking_date'] . ' ' . $row['end_time']);
                            $now = time();
                        ?>

                        <tr>
                            <td>
                                <div class="service-name">
                                    <?= htmlspecialchars($row['service_name']) ?>
                                </div>
                            </td>

                            <td><?= htmlspecialchars($row['stylist_name']) ?></td>

                            <td><?= date('d M Y', strtotime($row['booking_date'])) ?></td>

                            <td>
                                <?= date('g:i A', strtotime($row['start_time'])) ?>
                                -
                                <?= date('g:i A', strtotime($row['end_time'])) ?>
                            </td>

                            <td><?= statusBadge($row['status']) ?></td>

                            <td>
                                <?= $row['notes'] ? htmlspecialchars($row['notes']) : "<span class='muted'>No notes</span>" ?>
                            </td>

                            <td>
                                <div class="row-actions">

                                    <?php if ($row['status'] === 'confirmed' && $appointmentEnd <= $now): ?>

                                        <a class="btn-main"
                                           href="complete_booking.php?id=<?= (int)$row['booking_id'] ?>"
                                           onclick="return confirm('Confirm this service is completed?')">
                                            Done Service
                                        </a>

                                    <?php elseif ($row['status'] === 'confirmed' || $row['status'] === 'pending'): ?>

                                        <a class="btn-soft" href="reschedule_booking.php?id=<?= (int)$row['booking_id'] ?>">
                                            Reschedule
                                        </a>

                                        <a class="btn-danger"
                                           href="cancel_booking.php?id=<?= (int)$row['booking_id'] ?>"
                                           onclick="return confirm('Cancel this booking?')">
                                            Cancel
                                        </a>

                                    <?php elseif ($row['status'] === 'completed'): ?>

                                        <a class="btn-review" href="../feedback/customer_feedback.php">
                                            Give Review
                                        </a>

                                    <?php else: ?>

                                        <span class="muted">No action</span>

                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <div class="bottom-actions">
            <a href="../customer_dashboard.php" class="btn-soft">Back to Dashboard</a>
            <a href="../feedback/customer_feedbackdashboard.php" class="btn-soft">Feedback</a>
        </div>

    </div>
</div>

</body>
</html>