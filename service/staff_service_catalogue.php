<?php
// ✅ INTEGRATED: Session check — only staff and admin can access
require('../db.php');
session_start();
 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'customer') {
    header("Location: ../login.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Service Catalogue Management</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <!-- ✅ INTEGRATED: Salon-wide navigation header -->
        <?php include '../header.php'; ?>
        <div class="container">

    <h1>Service Catalogue Management</h1>

    <div style="display: flex; gap: 1rem;">
    <div class="actions" style="margin-bottom:1rem;">
        <a class="btn" href="add_service.php"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ➕<br><br>Add New Service</a>
    </div>

    <div class="actions" style="margin-bottom:1rem;">
        <a class="btn" href="timeslots.php">&nbsp;&nbsp;&nbsp;&nbsp; 🕒 <br><br> Timeslots</a>
    </div>
</div>

    <form method="GET">
        <input type="text" name="search" placeholder="Search" style="font-size: 20px">
        <select name="duration" style="font-size: 15px">
            <option value="">-- Duration --</option>
            <option>30</option>
            <option>60</option>
            <option>90</option>
            <option>120</option>
            <option>180</option>
            <option>240</option>
        </select>
        <select name="service_type" style="font-size: 15px">
            <option value="">-- Service Type --</option>
            <option>hair</option>
            <option>skincare</option>
            <option>massage</option>
            <option>nails</option>
        </select>
        <select name="promotion" style="font-size: 15px">
            <option value="">-- Promotion --</option>
            <option>0</option>
            <option>5</option>
            <option>10</option>
            <option>20</option>
        </select>
        <button>Search</button>
    </form>

    <hr>

<?php
$query = "SELECT * FROM services WHERE 1=1";

if (!empty($_GET['search'])) {
    $s = $_GET['search'];
    $query .= " AND service_name LIKE '%$s%'";
}

if (!empty($_GET['duration'])) {
    $query .= " AND duration = '{$_GET['duration']}'";
}

if (!empty($_GET['service_type'])) {
    $query .= " AND service_type = '{$_GET['service_type']}'";
}

if (!empty($_GET['promotion'])) {
    $query .= " AND promotion = '{$_GET['promotion']}'";
}

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo "<div>
    <b>{$row['service_name']}</b> ({$row['duration']} min)
    <button onclick='viewService({$row['service_id']})'>View</button>
    <a class='btn' href='edit_service.php?id={$row['service_id']}'>Edit</a>
    <a class='btn danger' href='?delete={$row['service_id']}' onclick=\"return confirm('Delete this record?')\">Delete</a>
</div><hr>";
}


?>
<div class="modal" id="modal">
    <div class="modal-inner">
                <div id="modalContent"></div>
                <button id="closeModal">Close</button>
    </div>
</div>

<script>
const modal = document.getElementById("modal");
const closeBtn = document.getElementById("closeModal");

function viewService(id){
    fetch("view_service.php?id=" + id)
    .then(res => res.text())
    .then(data => {
        console.log(data); 
        document.getElementById("modalContent").innerHTML = data;
        modal.classList.add("open");
    })
    .catch(err => console.error(err));
}

// Close modal
closeBtn.addEventListener("click", () => {
    modal.classList.remove("open");
});
</script>
</div>
</body>
</html>