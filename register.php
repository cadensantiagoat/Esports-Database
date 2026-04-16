<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for an account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <label for="roleselect">Select a Role:</label>
            <select>
                <option value="player">Player</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
            </select>      
    
        </form>
    </div>
</body>
</html>

<!-- Make sure that database handles the requirements for user input, use Select Statement instead of "required" here -->