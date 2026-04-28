<?php
require_once 'db_connect.php';
require_once 'auth_helpers.php';

$error_message = '';

// If already logged in, send user to home page.
if (isset($_SESSION['userID'])) {
    header('Location: home_page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_message = 'Please enter both username and password.';
    } else {
        $stmt = $db->prepare("SELECT userID, Username, PasswordHash, Role FROM Users WHERE Username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['PasswordHash'])) {
                session_regenerate_id(true);
                $_SESSION['userID'] = $user['userID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];

                header('Location: home_page.php');
                exit();
            }
        }

        $error_message = 'Invalid username or password.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Login</h1>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required><br><br>

            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <button type="submit">Submit</button><br>
        </form>
        <p><a href="forgot_password.php">Forgot your password?</a></p>
        <p>Don't have an account? <a href="register.php">Create one here</a>.</p><br> <!-- Link to registration -->
    </div>
</body>
</html>
<!-- Make sure that database handles the requirements for user input, use Select Statement instead of "required" here -->