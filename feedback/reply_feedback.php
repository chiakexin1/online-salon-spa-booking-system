<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','stylist'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)$_POST['id'];
    $reply = trim($_POST['reply']);
    $reply_by = $_SESSION['user_id'];

    if (!empty($reply)) {
        $stmt = $conn->prepare("
            UPDATE feedback
            SET reply = ?, reply_by = ?
            WHERE feedback_id = ?
        ");

        $stmt->bind_param("sii", $reply, $reply_by, $id);
        $stmt->execute();
    }
}

header("Location: staff_feedback.php");
exit();
?>