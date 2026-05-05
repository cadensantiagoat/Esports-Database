<?php
require_once 'db_connect.php';
require_once 'auth_helpers.php';

/** Matches Roles.roleID for 'Visitor' in esport_ddl3.sql */
const REGISTER_DEFAULT_ROLE_ID = 1;

$error_message = '';

if (isset($_SESSION['userID'])) {
    header('Location: home_page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($first_name === '' || $last_name === '' || $username === '' || $email === '' || $password === '') {
        $error_message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($username) > 50 || strlen($email) > 100 || strlen($first_name) > 50 || strlen($last_name) > 50) {
        $error_message = 'One or more fields are too long.';
    } else {
        $check = $db->prepare('SELECT userID FROM Users WHERE username = ? OR email = ? LIMIT 1');
        $check->bind_param('ss', $username, $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            $error_message = 'That username or email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role_id = REGISTER_DEFAULT_ROLE_ID;
            $stmt = $db->prepare(
                'INSERT INTO Users (firstName, lastName, username, email, passwordHash, roleID) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssssi', $first_name, $last_name, $username, $email, $hash, $role_id);

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: login.php?registered=1');
                exit();
            }
            $error_message = 'Registration failed. Please try again.';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Create an account</h1>
        <p>New accounts are <strong>Visitor</strong> accounts: you can browse the league, but you cannot edit match or player stats. League staff can change your role later if needed.</p>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">
            <label for="first_name">First name:</label><br>
            <input type="text" id="first_name" name="first_name" maxlength="50" required
                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"><br><br>

            <label for="last_name">Last name:</label><br>
            <input type="text" id="last_name" name="last_name" maxlength="50" required
                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"><br><br>

            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" maxlength="50" required
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"><br><br>

            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" maxlength="100" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"><br><br>

            <label for="password">Password (min. 8 characters):</label><br>
            <input type="password" id="password" name="password" minlength="8" required><br><br>

            <label for="password_confirm">Confirm password:</label><br>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required><br><br>

            <button type="submit">Register</button><br>
        </form>
        <p><a href="login.php">Back to login</a></p>
    </div>
</body>
</html>
