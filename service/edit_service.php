<?php
require('../db.php');

session_start();

// ✅ INTEGRATED: Only staff and admin can edit services
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'customer') {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load record for editing
$stmt = mysqli_prepare($conn,
    "SELECT * FROM services WHERE service_id = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res      = mysqli_stmt_get_result($stmt);
$editData = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$editData) {
    header("Location: staff_service_catalogue.php");
    exit();
}

// Update the record
if (isset($_POST['update'])) {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $duration = trim($_POST['duration']);
    $price = trim($_POST['price']);
    $location = trim($_POST['location']);
    $service_type = trim($_POST['service_type']);
    $promotion = trim($_POST['promotion']);
    $image = $_FILES['image']['name'];

    if ($_FILES['image']['error'] === 0) {
    $image = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
    } else {
        $image = $editData['image']; // keep old image
    }

    if(empty($service_name)||empty($description)||empty($duration)||empty($price)||empty($location)||empty($service_type)||empty($image)){
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'All fields are required.'];
        header("Location: edit_service.php?id=$id");
        exit();
    }


    $stmt = mysqli_prepare($conn,
        "UPDATE services SET service_name = ?, description = ?, duration = ?, price = ?, location = ?, service_type = ?, promotion = ?, image = ?
         WHERE service_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'ssidssisi', 
        $service_name, 
        $description, 
        $duration, 
        $price, 
        $location, 
        $service_type,
        $promotion, 
        $image,
        $id,
    );

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Service updated successfully.'];
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
    <title>Service Catalogue Management - Edit Service Record</title>
    <link rel="stylesheet" href="style.css">
    <script>
        document.getElementById('imageInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const preview = document.getElementById('preview');
                preview.src = URL.createObjectURL(file);
            }
        });
    </script>
</head>
<body>

    <!-- ✅ INTEGRATED: Salon-wide navigation header -->
    <?php include '../header.php'; ?>
<div class="container">
    <h1>Edit Sevice Record</h1>
    <div class="actions" style="margin-bottom:1rem;">
        <a class="btn" href="staff_service_catalogue.php">&larr; Back</a>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="form <?= $f['type'] === 'success' ? 'success' : 'error' ?>" style="max-width:100%;">
            <?= htmlspecialchars($f['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <form method="POST" enctype="multipart/form-data">
            <label>Service Name
                <input type="text" name="service_name" required
                       value="<?= htmlspecialchars($editData['service_name']) ?>">
            </label>

            <label>Service Description
                <textarea name="description" required><?= htmlspecialchars($editData['description']) ?></textarea>
            </label>


            <label>Duration
                <select name="duration" required>
                    <option value="30"      <?= $editData['duration'] === '30'      ? 'selected' : '' ?>>30</option>
                    <option value="60"       <?= $editData['duration'] === '60'      ? 'selected' : '' ?>>60</option>
                    <option value="90"      <?= $editData['duration'] === '90'      ? 'selected' : '' ?>>90</option>
                    <option value="120"      <?= $editData['duration'] === '120'      ? 'selected' : '' ?>>120</option>
                    <option value="180"      <?= $editData['duration'] === '180'      ? 'selected' : '' ?>>180</option>
                    <option value="240"      <?= $editData['duration'] === '240'      ? 'selected' : '' ?>>240</option>
                </select>
            </label>

            <label>Price
                <input type="number" name="price" required
                       value="<?= htmlspecialchars($editData['price']) ?>">
            </label>

            <label>Location
                <input type="text" name="location" required
                       value="<?= htmlspecialchars($editData['location']) ?>">
            </label>

            <label>Service Type
                <select name="service_type" required>
                    <option value="hair"      <?= $editData['service_type'] === 'hair'      ? 'selected' : '' ?>>hair</option>
                    <option value="skincare"       <?= $editData['service_type'] === 'skincare'      ? 'selected' : '' ?>>skincare</option>
                    <option value="massage"      <?= $editData['service_type'] === 'massage'      ? 'selected' : '' ?>>massage</option>
                    <option value="nails"      <?= $editData['service_type'] === 'nails'      ? 'selected' : '' ?>>nails</option>
                </select>
            </label>

            <label>Promotion for Service
                <select name="promotion" required>
                    <option value="0"      <?= $editData['promotion'] === '0'      ? 'selected' : '' ?>>0%</option>
                    <option value="5"       <?= $editData['promotion'] === '5'      ? 'selected' : '' ?>>5%</option>
                    <option value="10"      <?= $editData['promotion'] === '10'      ? 'selected' : '' ?>>10%</option>
                    <option value="20"      <?= $editData['promotion'] === '20'      ? 'selected' : '' ?>>20%</option>
                </select>
            </label>

            <label>Image of Service
                <img id="preview" 
                    src="uploads/<?= htmlspecialchars($editData['image']) ?>" 
                    alt="Image Preview" 
                    style="max-width:200px; display:block; margin-bottom:10px;">
                <input type="file" name="image" id="imageInput"
		            value="<?= htmlspecialchars($editData['image']) ?>">
            </label>

            <div class="actions">
                <button type="submit" name="update">Update Service Record</button>
                <a href="staff_service_catalogue.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- ✅ FIX: Script moved to end of body so #imageInput exists in DOM when JS runs -->
<script>
document.getElementById('imageInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('preview').src = URL.createObjectURL(file);
    }
});
</script>
</body>
</html>