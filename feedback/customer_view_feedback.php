<?php
session_start();
include '../db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_reply_submit'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    $customer_reply = trim($_POST['customer_reply']);

    if ($customer_reply === "") {
        $error = "Reply cannot be empty.";
    } else {
        $update = $conn->prepare("
            UPDATE feedback
            SET customer_reply = ?,
                customer_reply_at = CURRENT_TIMESTAMP
            WHERE feedback_id = ?
              AND user_id = ?
              AND reply IS NOT NULL
              AND reply != ''
        ");
        $update->bind_param("sii", $customer_reply, $feedback_id, $user_id);

        if ($update->execute() && $update->affected_rows > 0) {
            $success = "Your reply has been submitted successfully.";
        } else {
            $error = "Unable to submit reply. Staff must reply first.";
        }
    }
}

$stmt = $conn->prepare("
    SELECT 
        f.feedback_id,
        f.rating,
        f.comment,
        f.reply,
        f.customer_reply,
        f.customer_reply_at,
        f.created_at,
        f.anonymous,
        s.service_name,
        u.username AS stylist_name
    FROM feedback f
    JOIN services s ON f.service_id = s.service_id
    JOIN users u ON f.stylist_id = u.user_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Feedback</title>
<link rel="stylesheet" href="../style.css">

<style>
.page-wrap {
    max-width: 1050px;
    margin: 50px auto;
    padding: 0 20px;
}

.feedback-card {
    background: white;
    padding: 36px;
    border-radius: 24px;
    box-shadow: 0 14px 38px rgba(0,0,0,0.08);
    border: 1px solid #eee;
}

.header-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.header-box h2 {
    margin: 0;
    font-size: 2rem;
    color: #2d3436;
}

.header-box p {
    margin-top: 8px;
    color: #777;
}

.header-icon {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    background: #f0edff;
    color: #6c5ce7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
}

.msg-success,
.msg-error {
    padding: 14px 16px;
    border-radius: 14px;
    font-weight: 700;
    margin-bottom: 20px;
}

.msg-success {
    background: #d4edda;
    color: #155724;
}

.msg-error {
    background: #ffe1e1;
    color: #b00020;
}

.review-list {
    display: grid;
    gap: 22px;
}

.review-card {
    padding: 24px;
    border-radius: 20px;
    background: #fff;
    border: 1px solid #eee;
    box-shadow: 0 8px 24px rgba(0,0,0,0.05);
    transition: 0.2s;
}

.review-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(108,92,231,0.12);
    border-color: #e5ddff;
}

.review-top {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: flex-start;
    flex-wrap: wrap;
}

.service-name {
    font-size: 1.25rem;
    font-weight: 900;
    color: #2d3436;
    margin-bottom: 8px;
}

.meta {
    color: #777;
    font-size: 0.92rem;
}

.rating-badge {
    background: #fff8db;
    color: #b7791f;
    padding: 8px 14px;
    border-radius: 999px;
    font-weight: 900;
    white-space: nowrap;
}

.comment-box {
    margin-top: 18px;
    padding: 18px;
    background: #faf9ff;
    border: 1px solid #e5ddff;
    border-radius: 16px;
    color: #333;
    line-height: 1.6;
}

.status-row {
    margin-top: 16px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 800;
}

.public {
    background: #e6f7ec;
    color: #16803c;
}

.anonymous {
    background: #eef2ff;
    color: #3730a3;
}

.date-badge {
    background: #f5f5f5;
    color: #666;
}

.reply-box {
    margin-top: 18px;
    padding: 18px;
    background: linear-gradient(135deg, #f0edff, #faf9ff);
    border-left: 5px solid #6c5ce7;
    border-radius: 16px;
}

.reply-box strong {
    color: #6c5ce7;
    display: block;
    margin-bottom: 8px;
}

.customer-reply-box {
    margin-top: 16px;
    padding: 18px;
    background: #e6f7ec;
    border-left: 5px solid #16803c;
    border-radius: 16px;
    color: #155724;
}

.customer-reply-box strong {
    display: block;
    margin-bottom: 8px;
}

.customer-reply-time {
    margin-top: 8px;
    font-size: 0.85rem;
    color: #477b55;
}

.reply-form {
    display: none;
    margin-top: 16px;
}

.reply-form textarea {
    width: 100%;
    min-height: 95px;
    padding: 14px;
    border-radius: 14px;
    border: 1px solid #ddd;
    resize: vertical;
    box-sizing: border-box;
    font-family: inherit;
}

.reply-form textarea:focus {
    outline: none;
    border-color: #6c5ce7;
    box-shadow: 0 0 0 4px rgba(108,92,231,0.12);
}

.btn-reply-toggle,
.btn-reply-submit {
    display: inline-block;
    border: none;
    border-radius: 14px;
    padding: 12px 18px;
    font-weight: 800;
    cursor: pointer;
    transition: 0.2s;
}

.btn-reply-toggle {
    margin-top: 16px;
    background: #f0edff;
    color: #6c5ce7;
}

.btn-reply-toggle:hover {
    background: #6c5ce7;
    color: white;
}

.btn-reply-submit {
    margin-top: 10px;
    background: #6c5ce7;
    color: white;
    width: 100%;
}

.btn-reply-submit:hover {
    box-shadow: 0 8px 20px rgba(108,92,231,0.28);
}

.no-reply {
    margin-top: 18px;
    padding: 15px;
    background: #fff4d6;
    color: #9a6a00;
    border-radius: 14px;
    font-weight: 700;
}

.empty-state {
    text-align: center;
    padding: 55px 20px;
    border: 2px dashed #ddd;
    border-radius: 18px;
    color: #888;
}

.empty-state .icon {
    font-size: 45px;
    margin-bottom: 12px;
}

.btn-row {
    margin-top: 28px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-soft,
.btn-main {
    display: inline-block;
    padding: 13px 20px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 800;
}

.btn-main {
    background: #6c5ce7;
    color: white;
}

.btn-soft {
    background: #f0edff;
    color: #6c5ce7;
}

@media (max-width: 700px) {
    .feedback-card {
        padding: 24px;
    }

    .header-icon {
        display: none;
    }
}
</style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="page-wrap">
<div class="feedback-card">

    <div class="header-box">
        <div>
            <h2>My Submitted Feedback</h2>
            <p>View your reviews, staff replies, and reply back when needed.</p>
        </div>
        <div class="header-icon">⭐</div>
    </div>

    <?php if ($success): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows === 0): ?>

        <div class="empty-state">
            <div class="icon">📝</div>
            <h3>No feedback submitted yet</h3>
            <p>You have not submitted any service feedback.</p>
            <br>
            <a href="customer_feedback.php" class="btn-main">Give Feedback</a>
        </div>

    <?php else: ?>

        <div class="review-list">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="review-card">

                    <div class="review-top">
                        <div>
                            <div class="service-name">
                                <?= htmlspecialchars($row['service_name']) ?>
                            </div>
                            <div class="meta">
                                Stylist: <strong><?= htmlspecialchars($row['stylist_name']) ?></strong>
                            </div>
                        </div>

                        <div class="rating-badge">
                            <?= str_repeat("★", (int)$row['rating']) ?>
                            <?= (int)$row['rating'] ?>/5
                        </div>
                    </div>

                    <div class="comment-box">
                        <?= nl2br(htmlspecialchars($row['comment'])) ?>
                    </div>

                    <div class="status-row">
                        <?php if ($row['anonymous']): ?>
                            <span class="badge anonymous">Anonymous</span>
                        <?php else: ?>
                            <span class="badge public">Public</span>
                        <?php endif; ?>

                        <span class="badge date-badge">
                            <?= date('d M Y, g:i A', strtotime($row['created_at'])) ?>
                        </span>
                    </div>

                    <?php if (!empty($row['reply'])): ?>
                        <div class="reply-box">
                            <strong>Staff Reply</strong>
                            <?= nl2br(htmlspecialchars($row['reply'])) ?>
                        </div>

                        <?php if (!empty($row['customer_reply'])): ?>
                            <div class="customer-reply-box">
                                <strong>Your Reply</strong>
                                <?= nl2br(htmlspecialchars($row['customer_reply'])) ?>

                                <?php if (!empty($row['customer_reply_at'])): ?>
                                    <div class="customer-reply-time">
                                        Replied on <?= date('d M Y, g:i A', strtotime($row['customer_reply_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <button 
                            type="button" 
                            class="btn-reply-toggle" 
                            onclick="toggleCustomerReply(<?= (int)$row['feedback_id'] ?>)"
                        >
                            <?= empty($row['customer_reply']) ? 'Reply to Staff' : 'Update Your Reply' ?>
                        </button>

                        <form 
                            method="POST" 
                            class="reply-form" 
                            id="customerReplyForm<?= (int)$row['feedback_id'] ?>"
                        >
                            <input type="hidden" name="feedback_id" value="<?= (int)$row['feedback_id'] ?>">

                            <textarea 
                                name="customer_reply" 
                                required 
                                placeholder="Write your reply to the staff..."
                            ><?= htmlspecialchars($row['customer_reply'] ?? '') ?></textarea>

                            <button type="submit" name="customer_reply_submit" class="btn-reply-submit">
                                Save Reply
                            </button>
                        </form>

                    <?php else: ?>
                        <div class="no-reply">
                            No response from staff yet. You can reply after staff responds.
                        </div>
                    <?php endif; ?>

                </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>

    <div class="btn-row">
        <a href="customer_feedbackdashboard.php" class="btn-soft">⬅ Back</a>
        <a href="customer_feedback.php" class="btn-main">Give Feedback</a>
    </div>

</div>
</div>

<script>
function toggleCustomerReply(id) {
    const form = document.getElementById('customerReplyForm' + id);

    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
    }
}
</script>

</body>
</html>