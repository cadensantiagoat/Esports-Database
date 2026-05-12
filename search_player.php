<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

$search_term = trim($_GET['q'] ?? '');
$results = null;
$error_message = '';

if ($search_term !== '') {
    $like_term = '%' . $search_term . '%';
    $search_sql = "
        SELECT
            p.userID,
            p.gameTag AS GameTag,
            p.playerRank AS PlayerRank,
            tm.teamID AS TeamID,
            t.teamName AS TeamName
        FROM Players p
        LEFT JOIN TeamMembers tm
          ON tm.userID = p.userID
         AND tm.roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub')
         AND tm.status = 'Active'
        LEFT JOIN Teams t ON t.teamID = tm.teamID
        WHERE p.gameTag LIKE ?
        ORDER BY p.gameTag ASC
    ";

    $stmt = $db->prepare($search_sql);
    if (!$stmt) {
        $error_message = 'Unable to prepare player search.';
    } else {
        $stmt->bind_param('s', $like_term);
        $stmt->execute();
        $results = $stmt->get_result();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Player</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Search Player</h1>
        <p><a href="home_page.php">← Back to Homepage</a></p>

        <form action="search_player.php" method="GET">
            <label for="q">Player name / IGN:</label><br>
            <input
                type="text"
                id="q"
                name="q"
                value="<?php echo htmlspecialchars($search_term); ?>"
                placeholder="e.g. Faker"
                required
            >
            <button type="submit">Search</button>
        </form>

        <?php if ($error_message !== ''): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($search_term !== ''): ?>
            <br>
            <h2>Results for "<?php echo htmlspecialchars($search_term); ?>"</h2>

            <table style="border-collapse: collapse; width: 80%;">
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid black; padding: 5px;">IGN</th>
                    <th style="border: 1px solid black; padding: 5px;">Team</th>
                    <th style="border: 1px solid black; padding: 5px;">Rank</th>
                    <th style="border: 1px solid black; padding: 5px;">Action</th>
                </tr>

                <?php if ($results && $results->num_rows > 0): ?>
                    <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['GameTag']); ?></td>
                        <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['TeamName'] ?? 'No Team'); ?></td>
                        <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo htmlspecialchars($row['PlayerRank'] ?? 'N/A'); ?></td>
                        <td style="border: 1px solid black; padding: 5px; text-align: center;">
                            <a href="player_stats.php?player_id=<?php echo (int)$row['userID']; ?>"><strong>View Player</strong></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="border: 1px solid black; padding: 10px; text-align: center;">
                            No players found matching that name.
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
