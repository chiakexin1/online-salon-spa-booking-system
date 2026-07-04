<?php
require('../db.php');

session_start();

// ✅ INTEGRATED: Only staff and admin can add services
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'customer') {
    header("Location: ../login.php");
    exit();
}
//$user_id = $_SESSION[];

if (isset($_POST['new'])) {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $duration = trim($_POST['duration']);
    $price = trim($_POST['price']);
    $location = trim($_POST['location']);
    $service_type = trim($_POST['service_type']);
    $promotion = trim($_POST['promotion']);
    $image = $_FILES['image']['name'];

    if ($_FILES['image']['error'] === 0) {
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Image upload failed.'];
        header("Location: add_service.php");
        exit();
    }

    if(empty($service_name)||empty($description)||empty($duration)||empty($price)||empty($location)||empty($service_type)||empty($image)){
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'All fields are required.'];
        header("Location: add_service.php");
        exit();
    }

    $stmt = mysqli_prepare($conn, 
    "INSERT INTO services 
    (service_name, description, duration, price, location, service_type, promotion, image) 
    VALUES (?,?,?,?,?,?,?,?)"
    );

    mysqli_stmt_bind_param($stmt, 'ssidssis', 
        $service_name, 
        $description, 
        $duration, 
        $price, 
        $location, 
        $service_type, 
        $promotion, 
        $image
    );

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Service added successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => mysqli_error($conn)];
    }

mysqli_stmt_close($stmt);

    header("Location: staff_service_catalogue.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Service Catalogue Management - Add Service</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- ✅ INTEGRATED: Salon-wide navigation header -->
    <?php include '../header.php'; ?>
<div class="container">
    <h1>Service Catalogue Management</h1>
    <div class="actions" style="margin-bottom:1rem;">
        <a class="btn" href="staff_service_catalogue.php">&larr; Management</a>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="form <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="max-width:100%;">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Add New Service</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Service Name
                <input type="text" name="service_name" placeholder="Enter Service Name" required>
            </label>

            <label>Service Description
                <textarea name="description" placeholder="Enter Service Description"></textarea>
            </label>

            <label>Duration (minutes)
                <select name="duration" required>
                    <option value="">-- Select Duration of the Service --</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="180">180</option>
                    <option value="240">240</option>
                </select>
            </label>

            <label>Price
                <input type="number" name="price" placeholder="Enter Service Pricing" required>
            </label>

            <label>Location
                <input type="text" name="location" placeholder="Enter Service Location" required>
            </label>

            <label>Service Type
                <select name="service_type" required>
                    <option value="">-- Select Type of the Service --</option>
                    <option value="hair">hair</option>
                    <option value="skincare">skincare</option>
                    <option value="massage">massage</option>
                    <option value="nails">nails</option>
                </select>
            </label>

            <label>Promotion for Service
                <select name="promotion" required>
                    <option value="">-- Select Promotion for the Service --</option>
                    <option value="0">0%</option>
                    <option value="5">5%</option>
                    <option value="10">10%</option>
                    <option value="20">20%</option>
                </select>
            </label>

            <label>Image of Service
                <input type="file" name="image" required>
            </label>

            <div class="actions">
                <button type="submit" name="new">Add Service</button>
                <a href="staff_service_catalogue.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>