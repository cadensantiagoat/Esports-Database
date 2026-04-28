<?php
session_start();
require_once('db_connect.php');

if (!isset($_GET['team_id'])) {
    die("<h2>Error: No Team ID provided.</h2><a href='home_page.php'>Return Home</a>");
}

$team_id = intval($_GET['team_id']);

$team_sql = "SELECT TeamID, TeamName FROM Teams WHERE TeamID = ?";
$stmt_team = $db->prepare($team_sql);
$stmt_team->bind_param("i", $team_id);
$stmt_team->execute();
$team_info = $stmt_team->get_result()->fetch_assoc();

if (!$team_info) {
    die("<h2>Error: Team not found.</h2><a href='home_page.php'>Return Home</a>");
}

// Average stats are computed across all matches where this player has a stats row.
$players_sql = "SELECT
                    p.userID,
                    p.GameTag,
                    p.Rank,
                    COUNT(s.StatID) AS GamesPlayed,
                    ROUND(AVG(s.Kills), 2) AS AvgKills,
                    ROUND(AVG(s.Deaths), 2) AS AvgDeaths,
                    ROUND(AVG(s.Assists), 2) AS AvgAssists,
                    ROUND(AVG(s.GoldEarned), 2) AS AvgGold
                FROM Players p
                LEFT JOIN Stats s ON s.PlayerID = p.userID
                WHERE p.TeamID = ?
                GROUP BY p.userID, p.GameTag, p.Rank
                ORDER BY p.GameTag ASC";
$stmt_players = $db->prepare($players_sql);
$stmt_players->bind_param("i", $team_id);
$stmt_players->execute();
$players_result = $stmt_players->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Team Stats</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Team Details</h1>
        <p><a href="home_page.php">← Back to Homepage</a></p>

        <h2><?php echo htmlspecialchars($team_info['TeamName']); ?></h2>

        <table style="border-collapse: collapse; width: 80%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Player IGN</th>
                <th style="border: 1px solid black; padding: 5px;">Rank</th>
                <th style="border: 1px solid black; padding: 5px;">Games Played</th>
                <th style="border: 1px solid black; padding: 5px;">Avg Kills</th>
                <th style="border: 1px solid black; padding: 5px;">Avg Deaths</th>
                <th style="border: 1px solid black; padding: 5px;">Avg Assists</th>
                <th style="border: 1px solid black; padding: 5px;">Avg Gold</th>
            </tr>
            <?php if ($players_result->num_rows > 0): ?>
                <?php while($row = $players_result->fetch_assoc()): ?>
                <tr>
                    <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['GameTag']); ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo htmlspecialchars($row['Rank'] ?? 'N/A'); ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo (int)$row['GamesPlayed']; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['AvgKills'] !== null ? $row['AvgKills'] : '0.00'; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['AvgDeaths'] !== null ? $row['AvgDeaths'] : '0.00'; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['AvgAssists'] !== null ? $row['AvgAssists'] : '0.00'; ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['AvgGold'] !== null ? number_format((float)$row['AvgGold'], 2) : '0.00'; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="border: 1px solid black; padding: 10px; text-align: center;">
                        No players found for this team.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
