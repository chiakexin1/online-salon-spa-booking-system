<?php
session_start();
include 'db.php';

// 1. 安全检查：只有角色是 'admin' 的人可以进入
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // 如果你数据库里还没设 admin 角色，可以暂时注释掉这一行来测试，但最终一定要加上
    header("Location: login.php");
    exit();
}

$message = "";

// 2. 处理删除请求
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // 防止管理员删掉自己
    if ($delete_id == $_SESSION['user_id']) {
        $message = "Error: You cannot delete your own admin account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "User ID $delete_id has been deleted successfully.";
        } else {
            $message = "Error deleting user.";
        }
    }
}

// 3. 获取所有用户列表
$result = $conn->query("SELECT user_id, username, email, phone, role FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - User Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 针对表格的专用样式 */
        .admin-container { max-width: 900px; width: 95%; margin: 20px auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: var(--primary-color); color: white; }
        tr:hover { background-color: #f9f9f9; }
        .btn-delete { color: #ff4d4d; text-decoration: none; font-weight: bold; padding: 5px 10px; border: 1px solid #ff4d4d; border-radius: 4px; font-size: 0.8rem; }
        .btn-delete:hover { background: #ff4d4d; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2 style="text-align:left;">System User Management</h2>
        <p>Manage all registered customers and staff members.</p>

        <?php if($message) echo "<p style='color:orange; font-weight:bold;'>$message</p>"; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; background: <?php echo $row['role'] == 'customer' ? '#e1f5fe' : '#fff3e0'; ?>;">
                            <?php echo ucfirst($row['role']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" 
                            style="color: #6c5ce7; text-decoration: none; font-weight: bold; margin-right: 10px;">Edit</a>

                        <a href="user_management.php?delete_id=<?php echo $row['user_id']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="margin-top: 20px; text-align: center;">
            <a href="admin_dashboard.php" style="color: #666;">Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>