<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for an account</title>
</head>
<body>
    <div align="left">
        <h1>Create a Coach Account</h1>

        <?php if ($error_message): ?>
            <div><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="createCoach.php" method="POST">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required><br><br>

            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <label for="firstName">First Name:</label><br>
            <input type="firstName" id="firstName" name="firstName" required><br><br>

            <label for="lastName">Last Name:</label><br>
            <input type="lastName" id="lastName" name="lastName" required><br><br>

            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required><br><br>

            <label for="ign">Team You Belong To:</label><br>
            <input type="team" id="team" name="team" required><br><br>

            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>

<!-- Make sure that database handles the requirements for user input, use Select Statement instead of "required" here -->