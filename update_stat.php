<?php
require_once('auth_helpers.php');
require_once('db_connect.php');
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home_page.php');
    exit();
}

$stat_id = intval($_POST['stat_id'] ?? 0);
$kills = intval($_POST['kills'] ?? -1);
$deaths = intval($_POST['deaths'] ?? -1);
$assists = intval($_POST['assists'] ?? -1);
$gold = intval($_POST['gold'] ?? -1);

if ($stat_id <= 0 || $kills < 0 || $deaths < 0 || $assists < 0 || $gold < 0) {
    die("<h2>Error: Invalid stat values submitted.</h2><a href='home_page.php'>Return Home</a>");
}

$check_sql = "SELECT StatID, PlayerID FROM Stats WHERE StatID = ?";
$stmt_check = $db->prepare($check_sql);
$stmt_check->bind_param("i", $stat_id);
$stmt_check->execute();
$check_row = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if (!$check_row) {
    die("<h2>Error: Stat row not found.</h2><a href='home_page.php'>Return Home</a>");
}

$player_id = (int)$check_row['PlayerID'];
if (!can_edit_stat_for_player($db, $player_id)) {
    http_response_code(403);
    die("<h2>403 Forbidden</h2><p>You are not allowed to update this stat.</p><a href='home_page.php'>Return Home</a>");
}

$update_sql = "UPDATE Stats
               SET Kills = ?, Deaths = ?, Assists = ?, GoldEarned = ?
               WHERE StatID = ?";
$stmt_update = $db->prepare($update_sql);
$stmt_update->bind_param("iiiii", $kills, $deaths, $assists, $gold, $stat_id);
$stmt_update->execute();
$stmt_update->close();

header('Location: player_stats.php?player_id=' . $player_id);
exit();
?>
