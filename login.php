<?php
// session_start() MUST be the very first thing, before any output or includes
session_start();
include 'db.php';

// If already logged in, redirect to the right dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($_SESSION['role'] == 'customer') {
        header("Location: customer_dashboard.php");
    } else {
        header("Location: staff_dashboard.php");
    }
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        if ($user['role'] == 'customer') {
            header("Location: customer_dashboard.php");
        } elseif ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            // stylist, receptionist, or any other staff role
            header("Location: staff_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Salon & Spa System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="centered-page">
        <div class="container">
            <h2>Welcome Back</h2>

            <?php if (isset($_GET['success'])): ?>
                <div style="color: green; text-align: center; margin-bottom: 10px;">
                    Registration successful! Please login.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" name="login">Login</button>
                <div class="switch-link">
                    New user? <a href="register.php">Create an account</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>