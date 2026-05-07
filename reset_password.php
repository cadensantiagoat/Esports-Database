<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_login('login.php');

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $error_message = 'Please fill out all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'New password must be at least 8 characters.';
    } else {
        $user_id = current_user_id();
        if ($user_id === null) {
            $error_message = 'You must be logged in to reset your password.';
        } else {
            $stmt = $db->prepare("SELECT passwordHash FROM Users WHERE userID = ? LIMIT 1");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !isset($row['passwordHash'])) {
                $error_message = 'Unable to load your account.';
            } elseif (!password_verify($current_password, (string)$row['passwordHash'])) {
                $error_message = 'Current password is incorrect.';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $db->prepare("UPDATE Users SET passwordHash = ? WHERE userID = ?");
                $update->bind_param('si', $new_hash, $user_id);
                $update->execute();
                $changed = $update->affected_rows;
                $update->close();

                // If a user changes password, rotate session id to reduce fixation risk.
                session_regenerate_id(true);

                if ($changed >= 0) {
                    $success_message = 'Password updated successfully.';
                } else {
                    $error_message = 'Password update failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Reset Password</h1>

        <?php if ($success_message): ?>
            <div class="error-message" style="color:green;border-color:green;"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="reset_password.php" method="POST">
            <label for="current_password">Current Password:</label><br>
            <input type="password" id="current_password" name="current_password" required><br><br>

            <label for="new_password">New Password:</label><br>
            <input type="password" id="new_password" name="new_password" required><br><br>

            <label for="confirm_password">Confirm New Password:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>

            <button type="submit">Update Password</button><br><br>
        </form>

        <p><a href="home_page.php">Back to Home</a></p>
    </div>
</body>
</html>
