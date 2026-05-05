<?php
require_once('auth_helpers.php');
require_once('db_connect.php');

if (!isset($_GET['player_id'])) {
    die("<h2>Error: No Player ID provided.</h2><a href='home_page.php'>Return Home</a>");
}

$player_id = intval($_GET['player_id']);

$player_sql = "SELECT p.userID, p.gameTag AS GameTag, tm.teamID AS TeamID, t.teamName AS TeamName
               FROM Players p
               LEFT JOIN TeamMembers tm ON tm.userID = p.userID AND tm.roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub') AND tm.status = 'Active'
               LEFT JOIN Teams t ON tm.teamID = t.teamID
               WHERE p.userID = ?";
$stmt_player = $db->prepare($player_sql);
$stmt_player->bind_param("i", $player_id);
$stmt_player->execute();
$player_info = $stmt_player->get_result()->fetch_assoc();
$stmt_player->close();

if (!$player_info) {
    die("<h2>Error: Player not found.</h2><a href='home_page.php'>Return Home</a>");
}

$stats_sql = "SELECT
                ps.playerStatsID AS StatID,
                m.matchID AS MatchID,
                ps.kills AS Kills,
                ps.deaths AS Deaths,
                ps.assists AS Assists,
                ps.goldEarned AS GoldEarned,
                m.matchDate AS MatchDate,
                MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN t.teamName END) AS Team1_Name,
                MAX(CASE WHEN mt.sidePlayed = 'Red' THEN t.teamName END) AS Team2_Name
              FROM PlayerStats ps
              JOIN Rounds r ON ps.roundID = r.roundID
              JOIN Matches m ON r.matchID = m.matchID
              JOIN MatchTeam mt ON mt.matchID = m.matchID
              JOIN Teams t ON t.teamID = mt.teamID
              WHERE ps.userID = ?
              GROUP BY ps.playerStatsID, m.matchID, ps.kills, ps.deaths, ps.assists, ps.goldEarned, m.matchDate
              ORDER BY m.matchDate DESC";
$stmt_stats = $db->prepare($stats_sql);
$stmt_stats->bind_param("i", $player_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Player Match Stats</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Player Match Stats</h1>
        <p><a href="team_stats.php?team_id=<?php echo (int)$player_info['TeamID']; ?>">← Back to Team</a></p>

        <h2>
            <?php echo htmlspecialchars($player_info['GameTag']); ?>
            (<?php echo htmlspecialchars($player_info['TeamName'] ?? 'No Team'); ?>)
        </h2>

        <table style="border-collapse: collapse; width: 90%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Match Date</th>
                <th style="border: 1px solid black; padding: 5px;">Matchup</th>
                <th style="border: 1px solid black; padding: 5px;">Kills</th>
                <th style="border: 1px solid black; padding: 5px;">Deaths</th>
                <th style="border: 1px solid black; padding: 5px;">Assists</th>
                <th style="border: 1px solid black; padding: 5px;">Gold</th>
                <th style="border: 1px solid black; padding: 5px;">Action</th>
            </tr>
            <?php if ($stats_result->num_rows > 0): ?>
                <?php while ($row = $stats_result->fetch_assoc()): ?>
                <tr>
                    <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['MatchDate']); ?></td>
                    <td style="border: 1px solid black; padding: 5px;">
                        <?php echo htmlspecialchars($row['Team1_Name']); ?> vs <?php echo htmlspecialchars($row['Team2_Name']); ?>
                    </td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo (int)$row['Kills']; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo (int)$row['Deaths']; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo (int)$row['Assists']; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo number_format((int)$row['GoldEarned']); ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;">
                        <?php if (can_edit_stat_for_player($db, (int)$player_info['userID'])): ?>
                            <a href="edit_stat.php?stat_id=<?php echo (int)$row['StatID']; ?>"><strong>Edit</strong></a>
                        <?php else: ?>
                            Read Only
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="border: 1px solid black; padding: 10px; text-align: center;">
                        No match stats found for this player.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
