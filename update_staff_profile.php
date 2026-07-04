<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'customer') {
    header("Location: login.php");
    exit();
}

$user_id  = (int) $_SESSION['user_id'];
$message  = "";
$msg_type = "";

if (isset($_POST['save_staff'])) {
    $spec       = trim($_POST['specialization']);
    $bio        = trim($_POST['bio']);
    // ✅ FIX: use 'staff_role' as the one consistent name everywhere
    $staff_role = trim($_POST['staff_role']);

    if (empty($spec) || empty($staff_role)) {
        $message  = "Please fill in your specialization and service type.";
        $msg_type = "error";
    } else {
        $check = $conn->prepare("SELECT user_id FROM staff_profiles WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;

        if ($exists) {
            // ✅ FIX: column is staff_role, not service_type
            $stmt = $conn->prepare(
                "UPDATE staff_profiles
                 SET specialization = ?, bio = ?, staff_role = ?
                 WHERE user_id = ?"
            );
            $stmt->bind_param("sssi", $spec, $bio, $staff_role, $user_id);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO staff_profiles (user_id, specialization, bio, staff_role)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("isss", $user_id, $spec, $bio, $staff_role);
        }

        if ($stmt->execute()) {
            $message  = "Profile updated successfully!";
            $msg_type = "success";
        } else {
            $message  = "Error: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// Pre-fill form with existing data
$fetch = $conn->prepare("SELECT * FROM staff_profiles WHERE user_id = ?");
$fetch->bind_param("i", $user_id);
$fetch->execute();
$staff_data = $fetch->get_result()->fetch_assoc();

// ✅ FIX: read from staff_role, not service_type
$current_role = $staff_data['staff_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Staff Info - Salon System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            font-family: inherit;
            box-sizing: border-box;
            resize: vertical;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="centered-page">
        <div class="container" style="max-width:480px;">
            <h2>Professional Profile</h2>

            <?php if ($message): ?>
                <p style="color:<?= $msg_type === 'success' ? '#27ae60' : '#c62828' ?>;
                           font-weight:bold; text-align:center;">
                    <?= htmlspecialchars($message) ?>
                </p>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label>Service Type</label>
                    <select name="staff_role" required>
                        <option value="">-- Select Type of Service --</option>
                        <?php
                        // ✅ FIX: $current_role compared against each option value
                        $options = [
                            'hair'     => 'Hair',
                            'nails'    => 'Nails',
                            'massage'  => 'Massage',
                            'skincare' => 'Skincare',
                        ];
                        foreach ($options as $val => $label):
                        ?>
                            <option value="<?= $val ?>"
                                <?= $current_role === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Specialization</label>
                    <input type="text" name="specialization" required
                           placeholder="e.g. Haircut & Styling"
                           value="<?= htmlspecialchars($staff_data['specialization'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Bio <span style="color:#aaa; font-size:0.82rem;">(Tell your story)</span></label>
                    <textarea name="bio" rows="6"
                              placeholder="Share your experience..."><?= htmlspecialchars($staff_data['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="save_staff">Save Changes</button>

                <div class="switch-link">
                    <a href="staff_dashboard.php">← Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>