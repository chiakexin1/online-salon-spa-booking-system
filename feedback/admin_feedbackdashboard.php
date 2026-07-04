<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Feedback Dashboard</title>
<link rel="stylesheet" href="../style.css">

<style>
.dashboard-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px;
}

.dashboard-title {
    text-align: center;
    margin-bottom: 30px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.card {
    background: white;
    padding: 30px 20px;
    border-radius: 15px;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 1px solid #eee;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(108,92,231,0.2);
    border-color: #6c5ce7;
}

.icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 15px;
    background: #f0edff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.card h3 {
    margin: 10px 0 5px;
    color: #333;
}

.card p {
    color: #777;
    font-size: 0.9rem;
}

.back-btn {
    display: block;
    margin: 40px auto 0;
    text-align: center;
    color: #6c5ce7;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="dashboard-container">

<div class="dashboard-title">
    <h1>Admin Feedback Dashboard</h1>
    <p>Welcome <strong><?php echo $_SESSION['username']; ?></strong></p>
</div>

<div class="dashboard-grid">

    <!-- Analytics -->
    <a href="admin_analytics.php" class="card">
        <div class="icon">📊</div>
        <h3>View Analytics</h3>
        <p>See ratings, trends, and performance</p>
    </a>

    <!-- Feedback -->
    <a href="staff_feedback.php" class="card">
        <div class="icon">💬</div>
        <h3>View All Feedback</h3>
        <p>Read and manage customer feedback</p>
    </a>

</div>

<a href="../admin_dashboard.php" class="back-btn">⬅ Back to Main</a>

</div>

</body>
</html>