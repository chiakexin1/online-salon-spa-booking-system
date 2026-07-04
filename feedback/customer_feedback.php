<?php
session_start();
include '../db.php';

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

$success = "";
$error = "";

if (isset($_POST['submit'])) {
    $booking_id = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    $bookingCheck = $conn->prepare("
        SELECT b.booking_id, b.service_id, u.user_id AS stylist_user_id
        FROM bookings b
        JOIN staff_profiles sp ON b.staff_id = sp.staff_id
        JOIN users u ON sp.user_id = u.user_id
        WHERE b.booking_id = ?
          AND b.customer_id = ?
          AND b.status = 'completed'
    ");
    $bookingCheck->bind_param("ii", $booking_id, $customer_id);
    $bookingCheck->execute();
    $booking = $bookingCheck->get_result()->fetch_assoc();

    if (!$booking) {
        $error = "You can only give feedback for completed appointments.";
    } else {
        $check = $conn->prepare("
            SELECT feedback_id 
            FROM feedback 
            WHERE booking_id = ?
        ");
        $check->bind_param("i", $booking_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "You already submitted feedback for this booking.";
        } else {
            $service_id = (int)$booking['service_id'];
            $stylist_id = (int)$booking['stylist_user_id'];

            $stmt = $conn->prepare("
                INSERT INTO feedback 
                (booking_id, user_id, stylist_id, service_id, rating, comment, anonymous)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiiiisi",
                $booking_id,
                $user_id,
                $stylist_id,
                $service_id,
                $rating,
                $comment,
                $anonymous
            );

            if ($stmt->execute()) {
                $success = "Feedback submitted successfully!";
            } else {
                $error = "Error submitting feedback.";
            }
        }
    }
}

$bookingsStmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.service_id,
        b.staff_id,
        s.service_name,
        u.username AS stylist_name,
        f.feedback_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN staff_profiles sp ON b.staff_id = sp.staff_id
    JOIN users u ON sp.user_id = u.user_id
    LEFT JOIN feedback f ON b.booking_id = f.booking_id
    WHERE b.customer_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$bookingsStmt->bind_param("i", $customer_id);
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Give Feedback</title>
<link rel="stylesheet" href="../style.css">

<style>
.page-wrap {
    max-width: 850px;
    margin: 55px auto;
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
    text-align: center;
    margin-bottom: 28px;
}

.header-box .icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 14px;
    border-radius: 20px;
    background: #f0edff;
    color: #6c5ce7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
}

.header-box h2 {
    margin: 0;
    color: #2d3436;
    font-size: 2rem;
}

.header-box p {
    color: #777;
    margin-top: 8px;
}

.msg-success,
.msg-error {
    padding: 14px 16px;
    border-radius: 14px;
    text-align: center;
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

label {
    display: block;
    font-weight: 800;
    margin-top: 18px;
    margin-bottom: 9px;
    color: #444;
}

select,
textarea {
    width: 100%;
    padding: 15px;
    border-radius: 14px;
    border: 1px solid #ddd;
    background: #fafafa;
    font-size: 0.95rem;
    box-sizing: border-box;
}

select:focus,
textarea:focus {
    outline: none;
    border-color: #6c5ce7;
    background: white;
    box-shadow: 0 0 0 4px rgba(108,92,231,0.12);
}

textarea {
    min-height: 130px;
    resize: vertical;
}

.booking-preview {
    display: none;
    margin-top: 18px;
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, #faf9ff, #f4f1ff);
    border: 1px solid #e5ddff;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}

.preview-item {
    background: white;
    padding: 14px;
    border-radius: 14px;
    border: 1px solid #eee;
}

.preview-label {
    color: #888;
    font-size: 0.78rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.preview-value {
    color: #2d3436;
    font-weight: 800;
}

.status-note {
    margin-top: 14px;
    padding: 12px;
    border-radius: 12px;
    font-weight: 700;
}

.status-ok {
    background: #e6f7ec;
    color: #16803c;
}

.status-no {
    background: #fff4d6;
    color: #9a6a00;
}

.status-reviewed {
    background: #e1f3ff;
    color: #00649b;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 38px;
    color: #ccc;
    cursor: pointer;
    margin: 0;
    transition: 0.15s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #f5b301;
}

/* ✅ Modern Anonymous Checkbox UI */
.checkbox-group {
    margin-top: 22px;
}

.checkbox-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    border: 1px solid #e5ddff;
    background: linear-gradient(135deg, #faf9ff, #f4f1ff);
    border-radius: 18px;
    cursor: pointer;
    transition: 0.25s ease;
    box-shadow: 0 4px 12px rgba(108,92,231,0.06);
}

.checkbox-card:hover {
    border-color: #6c5ce7;
    box-shadow: 0 10px 24px rgba(108,92,231,0.15);
    transform: translateY(-2px);
}

.checkbox-card input {
    display: none;
}

.custom-check {
    width: 26px;
    height: 26px;
    min-width: 26px;
    border-radius: 8px;
    border: 2px solid #cfc8ff;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    font-weight: 900;
    transition: 0.2s ease;
}

.checkbox-card input:checked + .custom-check {
    background: #6c5ce7;
    border-color: #6c5ce7;
}

.checkbox-card input:checked + .custom-check::after {
    content: "✓";
}

.checkbox-text strong {
    display: block;
    color: #2d3436;
    font-size: 0.98rem;
    margin-bottom: 4px;
}

.checkbox-text span {
    color: #777;
    font-size: 0.85rem;
    line-height: 1.4;
}

.btn-submit {
    width: 100%;
    padding: 16px;
    margin-top: 26px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, #6c5ce7, #7d6df2);
    color: white;
    font-weight: 900;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.2s;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(108,92,231,0.35);
}

.btn-submit:disabled {
    background: #ccc;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #6c5ce7;
    font-weight: 800;
    text-decoration: none;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    border: 2px dashed #ddd;
    border-radius: 18px;
    color: #888;
}

@media (max-width: 700px) {
    .feedback-card {
        padding: 24px;
    }

    .preview-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="page-wrap">
<div class="feedback-card">

    <div class="header-box">
        <div class="icon">⭐</div>
        <h2>Give Feedback</h2>
        <p>Select one of your bookings and review only completed appointments.</p>
    </div>

    <?php if ($success): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($bookings->num_rows === 0): ?>

        <div class="empty-state">
            <h3>No bookings found</h3>
            <p>You need to make a booking before leaving feedback.</p>
        </div>

        <a class="back-link" href="customer_feedbackdashboard.php">⬅ Back</a>

    <?php else: ?>

    <form method="POST" id="feedbackForm">

        <label>Choose Booking</label>
        <select name="booking_id" id="booking_id" required>
            <option value="">-- Select Booking --</option>

            <?php while ($b = $bookings->fetch_assoc()): 
                $canReview = ($b['status'] === 'completed' && empty($b['feedback_id']));
                $alreadyReviewed = !empty($b['feedback_id']);

                if ($alreadyReviewed) {
                    $note = "Already reviewed";
                } elseif ($b['status'] !== 'completed') {
                    $note = "Appointment not completed yet";
                } else {
                    $note = "Ready for review";
                }
            ?>
                <option
                    value="<?= (int)$b['booking_id'] ?>"
                    data-can-review="<?= $canReview ? '1' : '0' ?>"
                    data-service="<?= htmlspecialchars($b['service_name']) ?>"
                    data-stylist="<?= htmlspecialchars($b['stylist_name']) ?>"
                    data-date="<?= date('d M Y', strtotime($b['booking_date'])) ?>"
                    data-time="<?= date('g:i A', strtotime($b['start_time'])) ?> - <?= date('g:i A', strtotime($b['end_time'])) ?>"
                    data-status="<?= htmlspecialchars($b['status']) ?>"
                    data-note="<?= htmlspecialchars($note) ?>"
                >
                    <?= htmlspecialchars($b['service_name']) ?> with <?= htmlspecialchars($b['stylist_name']) ?>
                    - <?= date('d M Y', strtotime($b['booking_date'])) ?>
                    (<?= $note ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <div class="booking-preview" id="bookingPreview"></div>

        <label>Rating</label>
        <div class="star-rating">
            <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
            <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
            <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
            <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
            <input type="radio" name="rating" value="1" id="star1" required><label for="star1">★</label>
        </div>

        <label>Comment</label>
        <textarea name="comment" id="comment" required placeholder="Share your experience..."></textarea>

        <div class="checkbox-group">
            <label class="checkbox-card" for="anon">
                <input type="checkbox" name="anonymous" id="anon">
                <span class="custom-check"></span>
                <div class="checkbox-text">
                    <strong>Submit as Anonymous</strong>
                    <span>Your name will not be shown together with this review.</span>
                </div>
            </label>
        </div>

        <button type="submit" name="submit" id="submitBtn" class="btn-submit" disabled>
            Submit Feedback
        </button>

    </form>

    <a class="back-link" href="customer_feedbackdashboard.php">⬅ Back</a>

    <?php endif; ?>

</div>
</div>

<script>
const bookingSelect = document.getElementById('booking_id');
const bookingPreview = document.getElementById('bookingPreview');
const submitBtn = document.getElementById('submitBtn');
const comment = document.getElementById('comment');

if (bookingSelect) {
    bookingSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];

        if (!this.value) {
            bookingPreview.style.display = 'none';
            bookingPreview.innerHTML = '';
            submitBtn.disabled = true;
            return;
        }

        const canReview = selected.dataset.canReview === '1';

        let statusClass = 'status-no';
        if (canReview) statusClass = 'status-ok';
        if (selected.dataset.note === 'Already reviewed') statusClass = 'status-reviewed';

        bookingPreview.style.display = 'block';
        bookingPreview.innerHTML = `
            <div class="preview-grid">
                <div class="preview-item">
                    <div class="preview-label">Service</div>
                    <div class="preview-value">${selected.dataset.service}</div>
                </div>
                <div class="preview-item">
                    <div class="preview-label">Stylist</div>
                    <div class="preview-value">${selected.dataset.stylist}</div>
                </div>
                <div class="preview-item">
                    <div class="preview-label">Date</div>
                    <div class="preview-value">${selected.dataset.date}</div>
                </div>
                <div class="preview-item">
                    <div class="preview-label">Time</div>
                    <div class="preview-value">${selected.dataset.time}</div>
                </div>
            </div>

            <div class="status-note ${statusClass}">
                ${canReview 
                    ? '✅ This appointment is completed. You can submit feedback.' 
                    : '⏳ ' + selected.dataset.note}
            </div>
        `;

        submitBtn.disabled = !canReview;
        comment.disabled = !canReview;

        document.querySelectorAll('input[name="rating"]').forEach(r => {
            r.disabled = !canReview;
            if (!canReview) r.checked = false;
        });
    });
}
</script>

</body>
</html>