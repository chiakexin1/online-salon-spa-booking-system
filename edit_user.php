<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// FIX: Cast to integer immediately to prevent SQL injection via GET parameter
$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    die("Invalid user ID.");
}

$message  = "";
$msg_type = "";

// Handle update
if (isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $role     = $_POST['role'];

    // Validate role to only allow known values
    $allowed_roles = ['customer', 'stylist', 'receptionist', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        $message  = "Invalid role selected.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, role = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $username, $email, $phone, $role, $user_id);

        if ($stmt->execute()) {
            $message  = "User updated successfully!";
            $msg_type = "success";
        } else {
            $message  = "Error updating user: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// Fetch current data for the form
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="centered-page">
        <div class="container" style="max-width: 500px;">
            <h2>Edit User</h2>
            <p style="color:#999; font-size:0.85rem;">Editing User ID: <?php echo $user_id; ?></p>

            <?php if ($message): ?>
                <p style="color: <?php echo ($msg_type == 'success') ? '#27ae60' : '#c62828'; ?>; font-weight: bold; text-align: center;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required
                           value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="customer"     <?php echo $user['role'] == 'customer'     ? 'selected' : ''; ?>>Customer</option>
                        <option value="stylist"      <?php echo $user['role'] == 'stylist'      ? 'selected' : ''; ?>>Stylist</option>
                        <option value="receptionist" <?php echo $user['role'] == 'receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                        <option value="admin"        <?php echo $user['role'] == 'admin'        ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" name="update_user" style="margin-top: 10px;">Save Changes</button>
                <div class="switch-link">
                    <a href="user_management.php">← Back to User List</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>