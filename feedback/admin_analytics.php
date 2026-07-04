<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* Customer Retention Filter */
$retention_filter = $_GET['retention_filter'] ?? 'most';

/* Average Rating per Stylist */
$stylistLabels = [];
$stylistRatings = [];

$result = $conn->query("
    SELECT u.username, AVG(f.rating) AS avg_rating
    FROM feedback f
    JOIN users u ON f.stylist_id = u.user_id
    GROUP BY f.stylist_id
    ORDER BY avg_rating DESC
");

while ($row = $result->fetch_assoc()) {
    $stylistLabels[] = $row['username'];
    $stylistRatings[] = round($row['avg_rating'], 2);
}

/* Popular Services */
$serviceLabels = [];
$serviceCounts = [];

$result2 = $conn->query("
    SELECT s.service_name, COUNT(*) AS total
    FROM feedback f
    JOIN services s ON f.service_id = s.service_id
    GROUP BY f.service_id
    ORDER BY total DESC
");

while ($row = $result2->fetch_assoc()) {
    $serviceLabels[] = $row['service_name'];
    $serviceCounts[] = $row['total'];
}

/* Feedback Time */
$timeLabels = [];
$timeCounts = [];

$result3 = $conn->query("
    SELECT HOUR(created_at) AS feedback_hour, COUNT(*) AS total
    FROM feedback
    GROUP BY feedback_hour
    ORDER BY feedback_hour
");

while ($row = $result3->fetch_assoc()) {
    $timeLabels[] = $row['feedback_hour'] . ':00';
    $timeCounts[] = $row['total'];
}

/* Summary Cards */
$totalFeedback = $conn->query("SELECT COUNT(*) AS total FROM feedback")->fetch_assoc()['total'];
$avgRating = $conn->query("SELECT AVG(rating) AS avg_rating FROM feedback")->fetch_assoc()['avg_rating'];
$totalServicesReviewed = $conn->query("SELECT COUNT(DISTINCT service_id) AS total FROM feedback")->fetch_assoc()['total'];
$totalStylistsReviewed = $conn->query("SELECT COUNT(DISTINCT stylist_id) AS total FROM feedback")->fetch_assoc()['total'];

/* Best Staff */
$bestStaff = $conn->query("
    SELECT u.username, AVG(f.rating) AS avg_rating
    FROM feedback f
    JOIN users u ON f.stylist_id = u.user_id
    GROUP BY f.stylist_id
    ORDER BY avg_rating DESC
    LIMIT 1
")->fetch_assoc();

/* Popular Service */
$popularService = $conn->query("
    SELECT s.service_name, COUNT(*) AS total
    FROM feedback f
    JOIN services s ON f.service_id = s.service_id
    GROUP BY f.service_id
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

/* Staff Performance */
$staffPerformance = $conn->query("
    SELECT 
        u.username,
        COUNT(f.feedback_id) AS total_reviews,
        AVG(f.rating) AS avg_rating
    FROM feedback f
    JOIN users u ON f.stylist_id = u.user_id
    GROUP BY f.stylist_id
    ORDER BY avg_rating DESC, total_reviews DESC
");

/* Retention Filter SQL */
$retentionHaving = "";
$retentionOrder = "ORDER BY total_bookings DESC";

if ($retention_filter === 'loyal') {
    $retentionHaving = "HAVING total_bookings >= 3";
    $retentionOrder = "ORDER BY total_bookings DESC";
} elseif ($retention_filter === 'returning') {
    $retentionHaving = "HAVING total_bookings = 2";
    $retentionOrder = "ORDER BY total_bookings DESC";
} elseif ($retention_filter === 'new') {
    $retentionHaving = "HAVING total_bookings = 1";
    $retentionOrder = "ORDER BY total_bookings DESC";
} elseif ($retention_filter === 'least') {
    $retentionOrder = "ORDER BY total_bookings ASC";
} else {
    $retentionOrder = "ORDER BY total_bookings DESC";
}

/* Customer Retention */
$retention = $conn->query("
    SELECT 
        u.username,
        COUNT(DISTINCT b.booking_id) AS total_bookings,
        COUNT(DISTINCT f.feedback_id) AS total_feedback,
        MIN(b.booking_date) AS first_visit,
        MAX(b.booking_date) AS last_visit,
        CASE 
            WHEN COUNT(DISTINCT b.booking_id) > 1 
            THEN ROUND(DATEDIFF(MAX(b.booking_date), MIN(b.booking_date)) / (COUNT(DISTINCT b.booking_id) - 1), 1)
            ELSE NULL
        END AS avg_return_interval
    FROM bookings b
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN feedback f ON f.user_id = u.user_id
    GROUP BY u.user_id
    $retentionHaving
    $retentionOrder
");

/* Retention Chart */
$retentionLabels = [];
$retentionData = [];

$retentionChart = $conn->query("
    SELECT 
        u.username,
        COUNT(b.booking_id) AS total_bookings
    FROM bookings b
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN users u ON c.user_id = u.user_id
    GROUP BY u.user_id
    ORDER BY total_bookings DESC
");

while ($row = $retentionChart->fetch_assoc()) {
    $retentionLabels[] = $row['username'];
    $retentionData[] = $row['total_bookings'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Feedback Analytics</title>
<link rel="stylesheet" href="../style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.page-wrap {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 20px;
}

.hero-card {
    background: linear-gradient(135deg, #6c5ce7, #8e7cff);
    color: white;
    padding: 34px;
    border-radius: 26px;
    box-shadow: 0 14px 38px rgba(108,92,231,0.28);
    margin-bottom: 28px;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
}

.hero-card h1 {
    margin: 0;
    font-size: 2.2rem;
}

.hero-card p {
    margin: 8px 0 0;
    opacity: 0.9;
}

.hero-icon {
    width: 78px;
    height: 78px;
    background: rgba(255,255,255,0.18);
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.summary-card {
    background: white;
    padding: 22px;
    border-radius: 20px;
    border: 1px solid #eee;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}

.summary-card .label {
    color: #777;
    font-size: 0.9rem;
    font-weight: 700;
}

.summary-card .value {
    margin-top: 8px;
    font-size: 1.8rem;
    font-weight: 900;
    color: #2d3436;
}

.summary-card .sub {
    margin-top: 6px;
    color: #999;
    font-size: 0.85rem;
}

.chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.chart-card {
    background: white;
    padding: 26px;
    border-radius: 22px;
    border: 1px solid #eee;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}

.chart-card h3 {
    margin: 0 0 18px;
    color: #2d3436;
}

.chart-box {
    height: 280px;
    position: relative;
}

.insight-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-top: 28px;
}

.insight-card {
    background: #faf9ff;
    border: 1px solid #e5ddff;
    border-radius: 20px;
    padding: 22px;
}

.insight-card h3 {
    margin: 0 0 10px;
    color: #6c5ce7;
}

.insight-card p {
    margin: 0;
    color: #333;
    font-weight: 800;
    font-size: 1.05rem;
    line-height: 1.5;
}

.table-card {
    background: white;
    padding: 26px;
    border-radius: 22px;
    border: 1px solid #eee;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    margin-top: 28px;
}

.table-card h3 {
    margin: 0 0 18px;
    color: #2d3436;
}

.table-wrap {
    overflow-x: auto;
    border-radius: 16px;
    border: 1px solid #eee;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

th {
    background: #f8f7ff;
    color: #6c5ce7;
    padding: 15px;
    text-align: left;
    font-size: 0.85rem;
    text-transform: uppercase;
}

td {
    padding: 15px;
    border-top: 1px solid #eee;
    color: #333;
}

tr:hover td {
    background: #faf9ff;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 0.8rem;
}

.badge-good {
    background: #e6f7ec;
    color: #16803c;
}

.badge-mid {
    background: #fff4d6;
    color: #9a6a00;
}

.badge-bad {
    background: #ffe1e1;
    color: #b00020;
}

.filter-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 18px;
    background: #faf9ff;
    border: 1px solid #e5ddff;
    padding: 16px;
    border-radius: 16px;
}

.filter-row label {
    font-weight: 800;
    color: #444;
}

.filter-row select {
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-weight: 700;
    background: white;
}

.filter-row button,
.filter-row a {
    padding: 10px 15px;
    border-radius: 12px;
    border: none;
    text-decoration: none;
    font-weight: 800;
    cursor: pointer;
}

.filter-row button {
    background: #6c5ce7;
    color: white;
}

.filter-row a {
    background: #f0edff;
    color: #6c5ce7;
}

.btn-back {
    display: inline-block;
    margin-top: 28px;
    padding: 13px 22px;
    border-radius: 14px;
    background: #f0edff;
    color: #6c5ce7;
    font-weight: 800;
    text-decoration: none;
}

.btn-back:hover {
    background: #6c5ce7;
    color: white;
}

.empty-note {
    background: #fff4d6;
    color: #9a6a00;
    padding: 16px;
    border-radius: 16px;
    font-weight: 700;
    text-align: center;
}

@media (max-width: 850px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }

    .hero-icon {
        display: none;
    }

    .hero-card h1 {
        font-size: 1.7rem;
    }
}
</style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="page-wrap">

    <div class="hero-card">
        <div>
            <h1>📊 Feedback Analytics</h1>
            <p>Monitor customer satisfaction, staff performance, customer retention, and operational insights.</p>
        </div>
        <div class="hero-icon">⭐</div>
    </div>

    <?php if ($totalFeedback == 0): ?>

        <div class="empty-note">
            No feedback data available yet. Analytics will appear after customers submit reviews.
        </div>

    <?php else: ?>

        <div class="summary-grid">

            <div class="summary-card">
                <div class="label">Total Feedback</div>
                <div class="value"><?= $totalFeedback ?></div>
                <div class="sub">Reviews submitted</div>
            </div>

            <div class="summary-card">
                <div class="label">Average Rating</div>
                <div class="value"><?= round($avgRating, 2) ?> ⭐</div>
                <div class="sub">Overall satisfaction</div>
            </div>

            <div class="summary-card">
                <div class="label">Services Reviewed</div>
                <div class="value"><?= $totalServicesReviewed ?></div>
                <div class="sub">Different services rated</div>
            </div>

            <div class="summary-card">
                <div class="label">Stylists Reviewed</div>
                <div class="value"><?= $totalStylistsReviewed ?></div>
                <div class="sub">Staff performance tracked</div>
            </div>

        </div>

        <div class="chart-grid">

            <div class="chart-card">
                <h3>⭐ Average Rating per Stylist</h3>
                <div class="chart-box">
                    <canvas id="ratingChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>🔥 Popular Services</h3>
                <div class="chart-box">
                    <canvas id="serviceChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>⏰ Peak Feedback Time</h3>
                <div class="chart-box">
                    <canvas id="timeChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>🔁 Customer Retention Trend</h3>
                <div class="chart-box">
                    <canvas id="retentionChart"></canvas>
                </div>
            </div>

        </div>

        <div class="table-card">
            <h3>👥 Staff Performance Tracking</h3>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Staff</th>
                        <th>Total Reviews</th>
                        <th>Average Rating</th>
                        <th>Performance Status</th>
                    </tr>

                    <?php while($sp = $staffPerformance->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($sp['username']) ?></td>
                        <td><?= $sp['total_reviews'] ?></td>
                        <td><?= round($sp['avg_rating'], 2) ?> ⭐</td>
                        <td>
                            <?php if ($sp['avg_rating'] >= 4.5): ?>
                                <span class="badge badge-good">Excellent</span>
                            <?php elseif ($sp['avg_rating'] >= 3.5): ?>
                                <span class="badge badge-mid">Good</span>
                            <?php else: ?>
                                <span class="badge badge-bad">Needs Improvement</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <div class="insight-grid">

            <div class="insight-card">
                <h3>🏆 Best Staff</h3>
                <p>
                    <?= $bestStaff ? htmlspecialchars($bestStaff['username']) . " (" . round($bestStaff['avg_rating'], 2) . "⭐)" : "No data" ?>
                </p>
            </div>

            <div class="insight-card">
                <h3>🔥 Most Popular Service</h3>
                <p>
                    <?= $popularService ? htmlspecialchars($popularService['service_name']) . " (" . $popularService['total'] . " reviews)" : "No data" ?>
                </p>
            </div>

            <div class="insight-card">
                <h3>💡 Operational Insight</h3>
                <p>
                    Promote high-demand services, reward top-rated staff, and follow up with new customers to improve retention.
                </p>
            </div>

            <div class="insight-card">
                <h3>📌 Improvement Suggestion</h3>
                <p>
                    Staff with lower ratings should receive extra training or customer service support to improve service quality.
                </p>
            </div>

        </div>

        <div class="table-card">
            <h3>🔁 Customer Retention Trends</h3>

            <form method="GET" class="filter-row">
                <label>Filter Customer Visits:</label>

                <select name="retention_filter">
                    <option value="most" <?= $retention_filter === 'most' ? 'selected' : '' ?>>Most Visits</option>
                    <option value="least" <?= $retention_filter === 'least' ? 'selected' : '' ?>>Least Visits</option>
                    <option value="loyal" <?= $retention_filter === 'loyal' ? 'selected' : '' ?>>Loyal Customers</option>
                    <option value="returning" <?= $retention_filter === 'returning' ? 'selected' : '' ?>>Returning Customers</option>
                    <option value="new" <?= $retention_filter === 'new' ? 'selected' : '' ?>>New Customers</option>
                </select>

                <button type="submit">Apply Filter</button>
                <a href="admin_analytics.php">Reset</a>
            </form>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Customer</th>
                        <th>Total Bookings</th>
                        <th>Total Feedback</th>
                        <th>First Visit</th>
                        <th>Last Visit</th>
                        <th>Return Time Interval</th>
                        <th>Retention Status</th>
                    </tr>

                    <?php while($r = $retention->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['username']) ?></td>
                        <td><?= $r['total_bookings'] ?></td>
                        <td><?= $r['total_feedback'] ?></td>
                        <td><?= $r['first_visit'] ? date('d M Y', strtotime($r['first_visit'])) : '-' ?></td>
                        <td><?= $r['last_visit'] ? date('d M Y', strtotime($r['last_visit'])) : '-' ?></td>
                        <td>
                            <?php if ($r['avg_return_interval'] !== null): ?>
                                Every <?= $r['avg_return_interval'] ?> days
                            <?php else: ?>
                                No return yet
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['total_bookings'] >= 3): ?>
                                <span class="badge badge-good">Loyal Customer</span>
                            <?php elseif ($r['total_bookings'] == 2): ?>
                                <span class="badge badge-mid">Returning Customer</span>
                            <?php else: ?>
                                <span class="badge badge-bad">New Customer</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

    <?php endif; ?>

    <a href="admin_feedbackdashboard.php" class="btn-back">⬅ Back</a>

</div>

<script>
const stylistLabels = <?= json_encode($stylistLabels) ?>;
const stylistRatings = <?= json_encode($stylistRatings) ?>;

const serviceLabels = <?= json_encode($serviceLabels) ?>;
const serviceCounts = <?= json_encode($serviceCounts) ?>;

const timeLabels = <?= json_encode($timeLabels) ?>;
const timeCounts = <?= json_encode($timeCounts) ?>;

const retentionLabels = <?= json_encode($retentionLabels) ?>;
const retentionData = <?= json_encode($retentionData) ?>;

new Chart(document.getElementById('ratingChart'), {
    type: 'bar',
    data: {
        labels: stylistLabels,
        datasets: [{
            label: 'Average Rating',
            data: stylistRatings,
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 5
            }
        }
    }
});

new Chart(document.getElementById('serviceChart'), {
    type: 'doughnut',
    data: {
        labels: serviceLabels,
        datasets: [{
            data: serviceCounts
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('timeChart'), {
    type: 'line',
    data: {
        labels: timeLabels,
        datasets: [{
            label: 'Feedback Count',
            data: timeCounts,
            tension: 0.35,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('retentionChart'), {
    type: 'bar',
    data: {
        labels: retentionLabels,
        datasets: [{
            label: 'Total Bookings',
            data: retentionData,
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                precision: 0
            }
        }
    }
});
</script>

</body>
</html>