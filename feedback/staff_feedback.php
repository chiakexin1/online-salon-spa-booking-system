<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff', 'stylist'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

$back_link = ($role === 'admin') ? "admin_feedbackdashboard.php" : "../staff_dashboard.php";

$success = "";
$error = "";

/* Filters */
$rating_filter = isset($_GET['rating_filter']) ? (int)$_GET['rating_filter'] : 0;
$date_filter = $_GET['date_filter'] ?? "";
$stylist_filter = isset($_GET['stylist_filter']) ? (int)$_GET['stylist_filter'] : 0;
$service_filter = isset($_GET['service_filter']) ? (int)$_GET['service_filter'] : 0;

/* Reply */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    $reply = trim($_POST['reply']);

    if ($reply === "") {
        $error = "Reply cannot be empty.";
    } else {
        if ($role === 'admin') {
            $update = $conn->prepare("
                UPDATE feedback
                SET reply = ?, reply_by = ?
                WHERE feedback_id = ?
            ");
            $update->bind_param("sii", $reply, $user_id, $feedback_id);
        } else {
            $update = $conn->prepare("
                UPDATE feedback
                SET reply = ?, reply_by = ?
                WHERE feedback_id = ?
                  AND stylist_id = ?
            ");
            $update->bind_param("siii", $reply, $user_id, $feedback_id, $user_id);
        }

        if ($update->execute()) {
            $success = "Reply updated successfully.";
        } else {
            $error = "Unable to update reply.";
        }
    }
}

/* Dropdown data */
$servicesList = $conn->query("
    SELECT service_id, service_name
    FROM services
    ORDER BY service_name
");

$stylistsList = $conn->query("
    SELECT user_id, username
    FROM users
    WHERE role IN ('staff', 'stylist')
    ORDER BY username
");

/* Main query with flexible filters */
$sql = "
    SELECT 
        f.feedback_id,
        f.rating,
        f.comment,
        f.reply,
        f.customer_reply,
        f.customer_reply_at,
        f.created_at,
        f.anonymous,
        c.username AS customer_name,
        st.username AS stylist_name,
        s.service_name
    FROM feedback f
    LEFT JOIN users c ON f.user_id = c.user_id
    LEFT JOIN users st ON f.stylist_id = st.user_id
    LEFT JOIN services s ON f.service_id = s.service_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($role !== 'admin') {
    $sql .= " AND f.stylist_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

if ($rating_filter >= 1 && $rating_filter <= 5) {
    $sql .= " AND f.rating = ?";
    $params[] = $rating_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $sql .= " AND DATE(f.created_at) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($role === 'admin' && $stylist_filter > 0) {
    $sql .= " AND f.stylist_id = ?";
    $params[] = $stylist_filter;
    $types .= "i";
}

if ($service_filter > 0) {
    $sql .= " AND f.service_id = ?";
    $params[] = $service_filter;
    $types .= "i";
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>My Reviews</title>
<link rel="stylesheet" href="../style.css">

<style>
.page-wrap {
    max-width: 1250px;
    margin: 50px auto;
    padding: 0 20px;
}

.feedback-card {
    background: white;
    border-radius: 24px;
    padding: 35px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.08);
}

.page-header h1 {
    margin: 0;
    color: #2d3436;
    font-size: 2rem;
}

.page-header p {
    color: #777;
}

.success-box {
    background: #d4edda;
    color: #155724;
    padding: 14px;
    border-radius: 14px;
    margin-bottom: 18px;
    font-weight: 700;
}

.error-box {
    background: #ffe1e1;
    color: #b00020;
    padding: 14px;
    border-radius: 14px;
    margin-bottom: 18px;
    font-weight: 700;
}

.filter-box {
    margin: 22px 0;
    padding: 18px;
    background: #faf9ff;
    border: 1px solid #e5ddff;
    border-radius: 18px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    align-items: end;
}

.filter-group label {
    display: block;
    font-weight: 800;
    color: #444;
    margin-bottom: 8px;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 11px 13px;
    border-radius: 12px;
    border: 1px solid #ddd;
    background: white;
    font-weight: 700;
    color: #444;
    box-sizing: border-box;
}

.filter-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-actions button,
.filter-actions a {
    padding: 11px 15px;
    border-radius: 12px;
    border: none;
    text-decoration: none;
    font-weight: 800;
    cursor: pointer;
}

.filter-actions button {
    background: #6c5ce7;
    color: white;
}

.filter-actions a {
    background: #f0edff;
    color: #6c5ce7;
}

.table-wrap {
    overflow-x: auto;
    border-radius: 18px;
    border: 1px solid #eee;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1150px;
}

thead {
    background: linear-gradient(135deg, #6c5ce7, #7d6df2);
    color: white;
}

th, td {
    padding: 18px;
    text-align: left;
    vertical-align: top;
}

td {
    border-bottom: 1px solid #eee;
}

.rating-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 85px;
    padding: 10px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, #fff7cc, #ffe89a);
    color: #b7791f;
    font-weight: 900;
    font-size: 0.95rem;
    box-shadow: 0 4px 10px rgba(255, 200, 0, 0.18);
    white-space: nowrap;
    border: 1px solid rgba(255, 200, 0, 0.25);
}

.reply-box {
    background: #eef2ff;
    color: #3730a3;
    padding: 13px;
    border-radius: 14px;
    margin-bottom: 12px;
}

.customer-reply-box {
    background: #e6f7ec;
    color: #16803c;
    padding: 13px;
    border-radius: 14px;
    margin-top: 12px;
}

.reply-form {
    display: none;
    margin-top: 12px;
}

.reply-form textarea {
    width: 100%;
    min-height: 90px;
    padding: 13px;
    border-radius: 14px;
    border: 1px solid #ddd;
    resize: vertical;
    box-sizing: border-box;
}

.reply-form textarea:focus {
    outline: none;
    border-color: #6c5ce7;
    box-shadow: 0 0 0 4px rgba(108,92,231,0.12);
}

.btn-toggle,
.reply-btn,
.btn-back {
    display: inline-block;
    border: none;
    text-decoration: none;
    border-radius: 14px;
    padding: 11px 16px;
    font-weight: 800;
    cursor: pointer;
}

.btn-toggle {
    background: #f0edff;
    color: #6c5ce7;
}

.reply-btn {
    margin-top: 10px;
    background: #6c5ce7;
    color: white;
    width: 100%;
}

.btn-back {
    margin-top: 25px;
    background: #f0edff;
    color: #6c5ce7;
}

.anonymous-badge {
    background: #eef2ff;
    color: #3730a3;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}

.date-text {
    color: #666;
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 45px;
    border: 2px dashed #ddd;
    border-radius: 18px;
    color: #888;
}
</style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="page-wrap">
<div class="feedback-card">

    <div class="page-header">
        <h1>My Reviews</h1>
        <p>View customer feedback and reply only when needed.</p>
    </div>

    <?php if ($success): ?>
        <div class="success-box"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="GET" class="filter-box">

        <div class="filter-group">
            <label>Rating</label>
            <select name="rating_filter">
                <option value="0" <?= $rating_filter === 0 ? 'selected' : '' ?>>All Ratings</option>
                <option value="5" <?= $rating_filter === 5 ? 'selected' : '' ?>>⭐ 5 Stars</option>
                <option value="4" <?= $rating_filter === 4 ? 'selected' : '' ?>>⭐ 4 Stars</option>
                <option value="3" <?= $rating_filter === 3 ? 'selected' : '' ?>>⭐ 3 Stars</option>
                <option value="2" <?= $rating_filter === 2 ? 'selected' : '' ?>>⭐ 2 Stars</option>
                <option value="1" <?= $rating_filter === 1 ? 'selected' : '' ?>>⭐ 1 Star</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Date</label>
            <input type="date" name="date_filter" value="<?= htmlspecialchars($date_filter) ?>">
        </div>

        <?php if ($role === 'admin'): ?>
        <div class="filter-group">
            <label>Stylist</label>
            <select name="stylist_filter">
                <option value="0">All Stylists</option>
                <?php while ($stylist = $stylistsList->fetch_assoc()): ?>
                    <option value="<?= (int)$stylist['user_id'] ?>" <?= $stylist_filter === (int)$stylist['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($stylist['username']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="filter-group">
            <label>Service</label>
            <select name="service_filter">
                <option value="0">All Services</option>
                <?php while ($service = $servicesList->fetch_assoc()): ?>
                    <option value="<?= (int)$service['service_id'] ?>" <?= $service_filter === (int)$service['service_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($service['service_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit">Apply Filter</button>
            <a href="staff_feedback.php">Reset</a>
        </div>

    </form>

    <?php if ($result->num_rows > 0): ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Stylist</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Reply</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($row['anonymous']): ?>
                            <span class="anonymous-badge">Anonymous</span>
                        <?php else: ?>
                            <?= htmlspecialchars($row['customer_name'] ?? '-') ?>
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($row['service_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['stylist_name'] ?? '-') ?></td>

                    <td>
                        <span class="rating-badge">⭐ <?= (int)$row['rating'] ?>/5</span>
                    </td>

                    <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>

                    <td>
                        <?php if (!empty($row['reply'])): ?>
                            <div class="reply-box">
                                <strong>Staff Reply:</strong><br>
                                <?= nl2br(htmlspecialchars($row['reply'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="reply-box">
                                No reply yet.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($row['customer_reply'])): ?>
                            <div class="customer-reply-box">
                                <strong>Customer Reply:</strong><br>
                                <?= nl2br(htmlspecialchars($row['customer_reply'])) ?>

                                <?php if (!empty($row['customer_reply_at'])): ?>
                                    <br><small><?= date('d M Y, g:i A', strtotime($row['customer_reply_at'])) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <button type="button" class="btn-toggle" onclick="toggleReplyForm(<?= (int)$row['feedback_id'] ?>)">
                            <?= empty($row['reply']) ? 'Reply' : 'Update Reply' ?>
                        </button>

                        <form method="POST" class="reply-form" id="replyForm<?= (int)$row['feedback_id'] ?>">
                            <input type="hidden" name="feedback_id" value="<?= (int)$row['feedback_id'] ?>">

                            <textarea name="reply" required placeholder="Write your reply here..."><?= htmlspecialchars($row['reply'] ?? '') ?></textarea>

                            <button type="submit" name="reply_submit" class="reply-btn">
                                Save Reply
                            </button>
                        </form>
                    </td>

                    <td class="date-text">
                        <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                        <?= date('g:i A', strtotime($row['created_at'])) ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>

        <div class="empty-state">
            <h3>No reviews found</h3>
            <p>No customer feedback matched your selected filters.</p>
        </div>

    <?php endif; ?>

    <a href="<?= $back_link ?>" class="btn-back">⬅ Back</a>

</div>
</div>

<script>
function toggleReplyForm(id) {
    const form = document.getElementById('replyForm' + id);

    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
    }
}
</script>

</body>
</html>