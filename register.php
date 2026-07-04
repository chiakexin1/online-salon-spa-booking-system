<?php
include 'db.php';
$message = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone    = trim($_POST['phone']);
    $role     = $_POST['role'];

    // SECURITY FIX: Only allow customer or stylist registration.
    // Admin accounts must be created directly in the database by a system administrator.
    $allowed_roles = ['customer', 'stylist'];
    if (!in_array($role, $allowed_roles)) {
        $message = "Invalid role selected.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "This email is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $password, $phone, $role);
            if ($stmt->execute()) {
                header("Location: login.php?success=1");
                exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Salon & Spa System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="centered-page">
        <div class="container">
            <h2>Create Account</h2>
            <?php if ($message) echo "<div class='error-msg'>$message</div>"; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter your name"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="example@mail.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="Optional"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>I am a:</label>
                    <select name="role">
                        <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'customer') ? 'selected' : ''; ?>>
                            Customer (Book Services)
                        </option>
                        <option value="stylist" <?php echo (isset($_POST['role']) && $_POST['role'] == 'stylist') ? 'selected' : ''; ?>>
                            Stylist (Service Provider)
                        </option>
                    </select>
                </div>
                <button type="submit" name="register">Sign Up</button>
                <div class="switch-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>