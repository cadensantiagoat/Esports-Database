
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