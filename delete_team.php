<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home_page.php');
    exit();
}

$team_id = intval($_POST['team_id'] ?? 0);
if ($team_id <= 0) {
    die("<h2>Error: Invalid Team ID.</h2><a href='home_page.php'>Return Home</a>");
}

if (!can_manage_team($db, $team_id)) {
    http_response_code(403);
    die("<h2>403 Forbidden</h2><p>You are not allowed to delete this team.</p><a href='home_page.php'>Return Home</a>");
}

$team_stmt = $db->prepare('SELECT teamName FROM Teams WHERE teamID = ?');
$team_stmt->bind_param('i', $team_id);
$team_stmt->execute();
$team_row = $team_stmt->get_result()->fetch_assoc();
$team_stmt->close();

if (!$team_row) {
    die("<h2>Error: Team not found.</h2><a href='home_page.php'>Return Home</a>");
}

$team_name = $team_row['teamName'];

$delete_stmt = $db->prepare('DELETE FROM Teams WHERE teamID = ?');
$delete_stmt->bind_param('i', $team_id);
$delete_stmt->execute();
$affected = $delete_stmt->affected_rows;
$delete_stmt->close();

if ($affected < 1) {
    die("<h2>Error: Team was not deleted.</h2><a href='home_page.php'>Return Home</a>");
}

header('Location: home_page.php?team_deleted=' . rawurlencode($team_name));
exit();
?>
