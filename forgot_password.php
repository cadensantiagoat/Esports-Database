<?php
// Include PEAR Mail package and db_connect to connect to your database
require_once "Mail.php";
require_once 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // 2. Check if the email exists in the Users table using the $db object from db_connect.php
    $stmt = $db->prepare("SELECT userID, Username FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 3. Generate a new random 8-character password
        $new_password = substr(md5(uniqid(rand(), true)), 0, 8); 
        
        // Hash it for the database
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 4. Update the database with the new hashed password
        $update_stmt = $db->prepare("UPDATE Users SET PasswordHash = ? WHERE Email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);
        $update_stmt->execute();

        // 5. Send the email with the UNHASHED temporary password
        $toaddress = $email;
        $subject = "Esports League - Password Reset";
        $mailcontent = "Hello " . $user['Username'] . ",\n\n" .
                       "Your password has been successfully reset.\n" .
                       "Your new temporary password is: " . $new_password . "\n\n" .
                       "Please log in and change this password as soon as possible.\n";
        
        $fromaddress = "From: cadenb.santiago@gmail.com";

        // SMTP configuration (from your old homework)
        $host = "smtp.gmail.com";
        $port = "587";
        $username = "cadenb.santiago@gmail.com";
        $password = "jftn emqt ubto zlpo"; // Your App Password

        $headers = array(
            'From' => $fromaddress,
            'To' => $toaddress,
            'Subject' => $subject
        );

        // Sending email
        $smtp = Mail::factory('smtp', array(
            'host' => $host,
            'port' => $port,
            'auth' => true,
            'username' => $username,
            'password' => $password
        ));

        $mail = $smtp->send($toaddress, $headers, $mailcontent);

        if (PEAR::isError($mail)) {
            $message = "<div style='color:red;'>Error sending email: " . $mail->getMessage() . "</div>";
        } else {
            $message = "<div style='color:green;'>A new password has been sent to your email address!</div>";
        }
    } else {
        $message = "<div style='color:red;'>No account found with that email address.</div>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Reset Password</h1>
        
        <?php echo $message; ?>

        <form action="forgot_password.php" method="POST">
            <p>Enter your email address and we will send you a new temporary password.</p>
            <label for="email">Email Address:</label><br>
            <input type="email" id="email" name="email" required><br><br>

            <button type="submit">Reset Password</button><br><br>
        </form>
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>