<?php

// Login/register/reset use the shared app account; in a VM this should
// point at localhost.
$host = getenv('DB_HOST') ?: 'localhost';

$user = getenv('DB_USER') ?: 'user';

$pass = getenv('DB_PASS') ?: 'password';

$db_name = getenv('DB_NAME') ?: 'EsportLeagueDB';


$app_db = new mysqli($host, $user, $pass, $db_name);


if ($app_db->connect_error) {
    die("App database connection failed: " . $app_db->connect_error);
}

?>