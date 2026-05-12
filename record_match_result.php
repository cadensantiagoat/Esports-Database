<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_permission($db, 'edit_match_data');

$error_message = '';
$success_message = '';

$selected_match_id = intval($_GET['match_id'] ?? ($_POST['match_id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = intval($_POST['match_id'] ?? 0);
    $winning_team_id = intval($_POST['winning_team_id'] ?? 0);

    if ($match_id <= 0 || $winning_team_id <= 0) {
        $error_message = 'Please select a match and winner.';
    } else {
        $check_stmt = $db->prepare("
            SELECT COUNT(*) AS count_rows
            FROM MatchTeam
            WHERE matchID = ?
              AND teamID = ?
        ");
        $check_stmt->bind_param('ii', $match_id, $winning_team_id);
        $check_stmt->execute();
        $check_row = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if (!$check_row || (int)$check_row['count_rows'] !== 1) {
            $error_message = 'Winner must be one of the teams in the match.';
        } else {
            $match_update = $db->prepare("UPDATE Matches SET teamWon = ? WHERE matchID = ?");
            $match_update->bind_param('ii', $winning_team_id, $match_id);
            $match_update->execute();
            $match_update->close();

            $win = 'Win';
            $loss = 'Loss';

            $win_stmt = $db->prepare("
                UPDATE MatchTeam
                SET outcome = ?
                WHERE matchID = ?
                  AND teamID = ?
            ");
            $win_stmt->bind_param('sii', $win, $match_id, $winning_team_id);
            $win_stmt->execute();
            $win_stmt->close();

            $loss_stmt = $db->prepare("
                UPDATE MatchTeam
                SET outcome = ?
                WHERE matchID = ?
                  AND teamID <> ?
            ");
            $loss_stmt->bind_param('sii', $loss, $match_id, $winning_team_id);
            $loss_stmt->execute();
            $loss_stmt->close();

            header('Location: match_stats.php?match_id=' . $match_id);
            exit();
        }
    }
}

$matches_result = $db->query("
    SELECT 
        m.matchID,
        m.matchDate,
        MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN t.teamName END) AS BlueTeam,
        MAX(CASE WHEN mt.sidePlayed = 'Red' THEN t.teamName END) AS RedTeam
    FROM Matches m
    JOIN MatchTeam mt ON mt.matchID = m.matchID
    JOIN Teams t ON t.teamID = mt.teamID
    GROUP BY m.matchID, m.matchDate
    ORDER BY m.matchDate DESC
");

$teams_in_match = null;
if ($selected_match_id > 0) {
    $teams_stmt = $db->prepare("
        SELECT t.teamID, t.teamName
        FROM MatchTeam mt
        JOIN Teams t ON t.teamID = mt.teamID
        WHERE mt.matchID = ?
        ORDER BY mt.sidePlayed
    ");
    $teams_stmt->bind_param('i', $selected_match_id);
    $teams_stmt->execute();
    $teams_in_match = $teams_stmt->get_result();
    $teams_stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Record Match Result</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div align="left">
    <h1>Record Match Result</h1>
    <p><a href="home_page.php">← Back to Homepage</a></p>

    <?php if ($error_message): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="record_match_result.php" method="GET">
        <label for="match_id">Select Match:</label><br>
        <select id="match_id" name="match_id" required>
            <option value="">Choose Match</option>
            <?php while ($match = $matches_result->fetch_assoc()): ?>
                <option value="<?php echo (int)$match['matchID']; ?>"
                    <?php echo $selected_match_id === (int)$match['matchID'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($match['matchDate'] . ' - ' . $match['BlueTeam'] . ' vs ' . $match['RedTeam']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Load</button>
    </form>

    <?php if ($teams_in_match): ?>
        <br>
        <form action="record_match_result.php" method="POST">
            <input type="hidden" name="match_id" value="<?php echo (int)$selected_match_id; ?>">

            <label for="winning_team_id">Winner:</label><br>
            <select id="winning_team_id" name="winning_team_id" required>
                <option value="">Select Winner</option>
                <?php while ($team = $teams_in_match->fetch_assoc()): ?>
                    <option value="<?php echo (int)$team['teamID']; ?>">
                        <?php echo htmlspecialchars($team['teamName']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br><br>

            <button type="submit">Save Result</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>