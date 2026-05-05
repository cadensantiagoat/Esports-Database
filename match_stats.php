<?php
session_start();
require_once('db_connect.php');

// Check if a match ID was passed in the URL
if (!isset($_GET['match_id'])) {
    die("<h2>Error: No Match ID provided.</h2><a href='home_page.php'>Return Home</a>");
}

$match_id = intval($_GET['match_id']); // Sanitize the input

// Query to get the Match Details (Teams and Date)
$match_sql = "SELECT 
                m.matchDate AS MatchDate,
                MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN t.teamName END) AS Team1_Name,
                MAX(CASE WHEN mt.sidePlayed = 'Red' THEN t.teamName END) AS Team2_Name
              FROM Matches m
              JOIN MatchTeam mt ON mt.matchID = m.matchID
              JOIN Teams t ON t.teamID = mt.teamID
              WHERE m.matchID = ?
              GROUP BY m.matchID, m.matchDate";
$stmt = $db->prepare($match_sql);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match_info = $stmt->get_result()->fetch_assoc();

if (!$match_info) {
    die("<h2>Error: Match not found.</h2><a href='home_page.php'>Return Home</a>");
}

// Query to get the Player Stats for this specific match
$stats_sql = "SELECT 
                p.gameTag AS GameTag,
                t.teamName AS TeamName,
                ps.kills AS Kills,
                ps.deaths AS Deaths,
                ps.assists AS Assists,
                ps.goldEarned AS GoldEarned
              FROM PlayerStats ps
              JOIN Players p ON ps.userID = p.userID
              JOIN Teams t ON ps.teamID = t.teamID
              JOIN Rounds r ON ps.roundID = r.roundID
              WHERE r.matchID = ?
              ORDER BY t.teamName DESC, p.gameTag ASC";
$stmt_stats = $db->prepare($stats_sql);
$stmt_stats->bind_param("i", $match_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Match Statistics</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Match Scoreboard</h1>
        <p><a href="home_page.php">← Back to Homepage</a></p>
        
        <h2><?php echo htmlspecialchars($match_info['Team1_Name']); ?> vs <?php echo htmlspecialchars($match_info['Team2_Name']); ?></h2>
        <p><strong>Date:</strong> <?php echo $match_info['MatchDate']; ?></p>

        <table style="border-collapse: collapse; width: 60%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Team</th>
                <th style="border: 1px solid black; padding: 5px;">Player IGN</th>
                <th style="border: 1px solid black; padding: 5px;">Kills</th>
                <th style="border: 1px solid black; padding: 5px;">Deaths</th>
                <th style="border: 1px solid black; padding: 5px;">Assists</th>
                <th style="border: 1px solid black; padding: 5px;">Gold</th>
            </tr>
            <?php while($row = $stats_result->fetch_assoc()): ?>
            <tr>
                <td style="border: 1px solid black; padding: 5px;"><strong><?php echo htmlspecialchars($row['TeamName']); ?></strong></td>
                <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['GameTag']); ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['Kills']; ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['Deaths']; ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['Assists']; ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo number_format($row['GoldEarned']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>