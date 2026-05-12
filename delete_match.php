<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_permission($db, 'delete_matches');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home_page.php');
    exit();
}

$match_id = intval($_POST['match_id'] ?? 0);
if ($match_id <= 0) {
    die("<h2>Error: Invalid Match ID.</h2><a href='home_page.php'>Return Home</a>");
}

$match_stmt = $db->prepare("
    SELECT
        m.matchID,
        m.matchDate,
        MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN t.teamName END) AS BlueTeam,
        MAX(CASE WHEN mt.sidePlayed = 'Red' THEN t.teamName END) AS RedTeam
    FROM Matches m
    JOIN MatchTeam mt ON mt.matchID = m.matchID
    JOIN Teams t ON t.teamID = mt.teamID
    WHERE m.matchID = ?
    GROUP BY m.matchID, m.matchDate
    LIMIT 1
");
$match_stmt->bind_param('i', $match_id);
$match_stmt->execute();
$match_row = $match_stmt->get_result()->fetch_assoc();
$match_stmt->close();

if (!$match_row) {
    die("<h2>Error: Match not found.</h2><a href='home_page.php'>Return Home</a>");
}

$match_label = $match_row['BlueTeam'] . ' vs ' . $match_row['RedTeam'] . ' (' . $match_row['MatchDate'] . ')';

$delete_stmt = $db->prepare("DELETE FROM Matches WHERE matchID = ?");
$delete_stmt->bind_param('i', $match_id);
$delete_stmt->execute();
$affected_rows = $delete_stmt->affected_rows;
$delete_stmt->close();

if ($affected_rows < 1) {
    die("<h2>Error: Match was not deleted.</h2><a href='home_page.php'>Return Home</a>");
}

header('Location: home_page.php?match_deleted=' . rawurlencode($match_label));
exit();
?>
