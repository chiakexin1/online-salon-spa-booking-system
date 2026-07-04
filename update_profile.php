<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = (int) $_SESSION['user_id'];
$role      = $_SESSION['role'];
$is_customer = ($role === 'customer');

$success_msg = "";
$error_msg   = "";

// ── Handle form submission ────────────────────────────────────────────────────
if (isset($_POST['update'])) {
    $new_username = trim($_POST['username']);
    $new_phone    = trim($_POST['phone']);

    if (empty($new_username)) {
        $error_msg = "Username cannot be empty.";
    } else {
        // Update users table
        $stmt = $conn->prepare("UPDATE users SET username = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $new_username, $new_phone, $user_id);

        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username;

            // ── Customer preferences ─────────────────────────────────────────
            if ($is_customer) {
                $fav_service = !empty($_POST['favourite_service']) ? (int)$_POST['favourite_service'] : null;
                $fav_stylist = !empty($_POST['favourite_stylist']) ? (int)$_POST['favourite_stylist'] : null;
                $notes       = trim($_POST['notes']);

                // Upsert into customers table (INSERT or UPDATE)
                $pref = $conn->prepare("
                    INSERT INTO customers (user_id, favourite_service, favourite_stylist, notes)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        favourite_service = VALUES(favourite_service),
                        favourite_stylist = VALUES(favourite_stylist),
                        notes             = VALUES(notes)
                ");
                $pref->bind_param("iiis", $user_id, $fav_service, $fav_stylist, $notes);
                $pref->execute();
            }

            $success_msg = "Profile updated successfully!";
        } else {
            $error_msg = "Failed to update profile. Please try again.";
        }
    }
}

// ── Fetch current user data ───────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ── Fetch customer preferences if customer ────────────────────────────────────
$prefs = null;
if ($is_customer) {
    $p = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
    $p->bind_param("i", $user_id);
    $p->execute();
    $prefs = $p->get_result()->fetch_assoc();

    // Fetch available services for dropdown
    $services = $conn->query("SELECT service_id, service_name FROM services ORDER BY service_name");
    // Fetch available stylists for dropdown
    $stylists = $conn->query("
        SELECT u.user_id, u.username, sp.specialization
        FROM users u
        LEFT JOIN staff_profiles sp ON u.user_id = sp.user_id
        WHERE u.role = 'stylist'
        ORDER BY u.username
    ");
}

$back_link = $is_customer ? 'customer_dashboard.php' : 'staff_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Salon System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .section-divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }
        .section-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            margin-bottom: 14px;
        }
        textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 0.9rem;
            box-sizing: border-box;
            resize: vertical;
            min-height: 80px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="centered-page">
        <div class="container" style="max-width:480px;">
            <h2>Edit Profile</h2>

            <?php if ($success_msg): ?>
                <p style="color:#27ae60; font-weight:bold; text-align:center;"><?= $success_msg ?></p>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="error-msg"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <form method="POST">

                <!-- ── Basic Info ─────────────────────────────── -->
                <p class="section-label">Account Info</p>

                <div class="form-group">
                    <label>Email <span style="color:#aaa; font-size:0.8rem;">(Cannot be changed)</span></label>
                    <input type="text" value="<?= htmlspecialchars($user['email']) ?>" disabled
                           style="background:#f3f4f6; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required
                           value="<?= htmlspecialchars($user['username']) ?>">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="Enter your phone number"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <?php if ($is_customer): ?>
                <!-- ── Customer Preferences ───────────────────── -->
                <hr class="section-divider">
                <p class="section-label">My Preferences</p>

                <div class="form-group">
                    <label>Favourite Service</label>
                    <select name="favourite_service">
                        <option value="">-- No preference --</option>
                        <?php while ($svc = $services->fetch_assoc()): ?>
                            <option value="<?= $svc['service_id'] ?>"
                                <?= ($prefs['favourite_service'] ?? null) == $svc['service_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($svc['service_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Favourite Stylist</label>
                    <select name="favourite_stylist">
                        <option value="">-- No preference --</option>
                        <?php while ($st = $stylists->fetch_assoc()):
                            $spec = $st['specialization'] ? " — " . $st['specialization'] : "";
                        ?>
                            <option value="<?= $st['user_id'] ?>"
                                <?= ($prefs['favourite_stylist'] ?? null) == $st['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st['username'] . $spec) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Personal Notes
                        <span style="color:#aaa; font-size:0.8rem;">(allergies, preferences, etc.)</span>
                    </label>
                    <textarea name="notes" placeholder="e.g. Allergic to certain hair dye, prefer morning slots..."><?= htmlspecialchars($prefs['notes'] ?? '') ?></textarea>
                </div>
                <?php endif; ?>

                <button type="submit" name="update">Save Changes</button>

                <div class="switch-link">
                    <a href="<?= $back_link ?>">← Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>