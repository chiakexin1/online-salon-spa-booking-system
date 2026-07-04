<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','stylist'])) {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: staff_service_catalogue.php");
exit();
?>