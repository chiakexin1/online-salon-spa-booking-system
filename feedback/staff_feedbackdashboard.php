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
<title>Staff Feedback Dashboard</title>
<link rel="stylesheet" href="../style.css">
</head>

<body>

<?php include '../header.php'; ?>

<div class="container">

<h2>Staff Feedback Dashboard</h2>
<p>Welcome <strong><?php echo $_SESSION['username']; ?></strong></p>

<hr><br>

<a class="btn" href="staff_feedback.php">📋 View Feedback</a>
<br><br>

<a class="btn" href="../staff_dashboard.php">⬅ Back to Main</a>

</div>

</body>
</html>