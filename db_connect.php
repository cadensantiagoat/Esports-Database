<?php
// Add database connection here. You can comment or uncomment the block that matches your environment


// DOCKER SETUP (Caden)
$host = 'localhost';
$user = 'user';
$pass = 'password';

$db_name = 'EsportLeagueDB';

$db = new mysqli($host, $user, $pass, $db_name);

if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}
?>