<?php
require_once('auth_helpers.php');
require_once('db_connect.php');
require_login();

if (!isset($_GET['stat_id'])) {
    die("<h2>Error: No Stat ID provided.</h2><a href='home_page.php'>Return Home</a>");
}

$stat_id = intval($_GET['stat_id']);

$stat_sql = "SELECT s.StatID, s.PlayerID, s.MatchID, s.Kills, s.Deaths, s.Assists, s.GoldEarned, p.GameTag
             FROM Stats s
             JOIN Players p ON p.userID = s.PlayerID
             WHERE s.StatID = ?";
$stmt = $db->prepare($stat_sql);
$stmt->bind_param("i", $stat_id);
$stmt->execute();
$stat_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stat_row) {
    die("<h2>Error: Stat row not found.</h2><a href='home_page.php'>Return Home</a>");
}

if (!can_edit_stat_for_player($db, (int)$stat_row['PlayerID'])) {
    http_response_code(403);
    die("<h2>403 Forbidden</h2><p>You are not allowed to edit this stat.</p><a href='home_page.php'>Return Home</a>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Player Stat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Edit Stat</h1>
        <p><a href="player_stats.php?player_id=<?php echo (int)$stat_row['PlayerID']; ?>">← Back to Player Stats</a></p>

        <p>
            <strong>Player:</strong> <?php echo htmlspecialchars($stat_row['GameTag']); ?><br>
            <strong>Match ID:</strong> <?php echo (int)$stat_row['MatchID']; ?>
        </p>

        <form action="update_stat.php" method="POST">
            <input type="hidden" name="stat_id" value="<?php echo (int)$stat_row['StatID']; ?>">

            <label for="kills">Kills:</label><br>
            <input type="number" id="kills" name="kills" min="0" value="<?php echo (int)$stat_row['Kills']; ?>" required><br><br>

            <label for="deaths">Deaths:</label><br>
            <input type="number" id="deaths" name="deaths" min="0" value="<?php echo (int)$stat_row['Deaths']; ?>" required><br><br>

            <label for="assists">Assists:</label><br>
            <input type="number" id="assists" name="assists" min="0" value="<?php echo (int)$stat_row['Assists']; ?>" required><br><br>

            <label for="gold">Gold Earned:</label><br>
            <input type="number" id="gold" name="gold" min="0" value="<?php echo (int)$stat_row['GoldEarned']; ?>" required><br><br>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>
