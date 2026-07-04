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
<title>Feedback Dashboard</title>
<link rel="stylesheet" href="../style.css">

<style>
.container {
    max-width: 900px;
    margin: 50px auto;
}

/* TITLE */
.title {
    text-align: center;
    margin-bottom: 30px;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
}

/* CARD */
.card {
    background: white;
    padding: 30px 20px;
    border-radius: 16px;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 5px 18px rgba(0,0,0,0.06);
    transition: 0.3s;
    border: 1px solid #eee;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(108,92,231,0.2);
    border-color: #6c5ce7;
}

/* ICON */
.icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 10px;
    background: #f0edff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

/* TEXT */
.card h3 {
    margin: 10px 0 5px;
    color: #333;
}

.card p {
    color: #777;
    font-size: 14px;
}

/* BACK BUTTON */
.back {
    display: block;
    text-align: center;
    margin-top: 30px;
    color: #6c5ce7;
    font-weight: bold;
    text-decoration: none;
}
</style>
</head>

<body>

<?php include '../header.php'; ?>

<div class="container">

<div class="title">
    <h1>Feedback Dashboard</h1>
    <p>Welcome <strong><?php echo $_SESSION['username']; ?></strong></p>
</div>

<div class="grid">

<!-- Give Feedback -->
<a href="customer_feedback.php" class="card">
    <div class="icon">⭐</div>
    <h3>Give Feedback</h3>
    <p>Share your experience with our services</p>
</a>

<!-- View Feedback -->
<a href="customer_view_feedback.php" class="card">
    <div class="icon">📋</div>
    <h3>View My Feedback</h3>
    <p>Check your submitted reviews</p>
</a>

</div>

<a class="back" href="../customer_dashboard.php">⬅ Back to Main</a>

</div>

</body>
</html>