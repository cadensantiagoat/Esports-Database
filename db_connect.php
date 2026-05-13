<?php
// In a VM, Apache reaches MariaDB over localhost.
// Override with DB_HOST if needed.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'EsportLeagueDB';

$role_passwords = [
    'visitor_role' => '!visitor',
    'player_role' => '!player',
    'coach_role' => '!coach',
    'referee_role' => '!referee',
    // Backward compatibility for pre-permission-schema databases.
    'league_owner_role' => '!league_owner',
    'executive_manager_role' => '!executive_manager'
];

$db_account = $_SESSION['dbAccountName'] ?? 'visitor_role';

if (!array_key_exists($db_account, $role_passwords)) {
    $db_account = 'visitor_role';
}

$db = new mysqli($host, $db_account, $role_passwords[$db_account], $db_name);

if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

?>