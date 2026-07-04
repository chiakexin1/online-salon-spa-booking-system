<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$base = '/salon/'; // 改成你的项目文件夹名
?>

<nav style="background: #4e3ec4; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; color: white; margin-bottom: 20px; box-sizing: border-box; width: 100%;">
    <div class="logo">
        <a href="<?= $base ?>index.php" style="color: white; text-decoration: none; font-weight: bold; font-size: 1.2rem;">✨ Salon & Spa</a>
    </div>

    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="<?= $base ?>index.php"    style="color: white; text-decoration: none;">Home</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>

            <?php if ($_SESSION['role'] === 'customer'): ?>
                <a href="<?= $base ?>customer_dashboard.php" style="color: white; text-decoration: none;">My Dashboard</a>
                <a href="<?= $base ?>service/customer_catalogue.php" style="color: white; text-decoration: none;">Services Menu</a>

            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="<?= $base ?>admin_dashboard.php" style="color: white; text-decoration: none;">Admin Panel</a>
                <a href="<?= $base ?>user_management.php" style="color: white; text-decoration: none;">Users</a>
                <a href="<?= $base ?>service/staff_service_catalogue.php" style="color: white; text-decoration: none;">Services Menu</a>

            <?php else: ?>
                <a href="<?= $base ?>staff_dashboard.php" style="color: white; text-decoration: none;">Staff Panel</a>
                <a href="<?= $base ?>service/staff_service_catalogue.php"   style="color: white; text-decoration: none;">Services Menu</a>
                <a href="<?= $base ?>service/timeslots.php"  style="color: white; text-decoration: none;">Timeslots</a>
            <?php endif; ?>

            <a href="<?= $base ?>logout.php" style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 6px; color: white; text-decoration: none;">
                Logout
            </a>

        <?php else: ?>
            <a href="<?= $base ?>login.php" style="color: white; text-decoration: none;">Login</a>
            <a href="<?= $base ?>register.php" style="background: white; color: #6c5ce7; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                Register
            </a>
        <?php endif; ?>
    </div>
</nav>