<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_permission($db, 'create_matches');

$error_message = '';

$teams_result = $db->query("SELECT teamID, teamName FROM Teams WHERE status = 'Active' ORDER BY teamName");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_date_input = trim($_POST['match_date'] ?? '');
    $blue_team_id = intval($_POST['blue_team_id'] ?? 0);
    $red_team_id = intval($_POST['red_team_id'] ?? 0);

    if ($match_date_input === '' || $blue_team_id <= 0 || $red_team_id <= 0) {
        $error_message = 'Please fill out all fields.';
    } elseif ($blue_team_id === $red_team_id) {
        $error_message = 'A team cannot play against itself.';
    } else {
        $match_date = str_replace('T', ' ', $match_date_input) . ':00';

        $match_stmt = $db->prepare("INSERT INTO Matches (matchDate, teamWon) VALUES (?, NULL)");
        $match_stmt->bind_param('s', $match_date);

        if ($match_stmt->execute()) {
            $match_id = (int)$match_stmt->insert_id;
            $match_stmt->close();

            $blue_side = 'Blue';
            $red_side = 'Red';

            $mt_stmt = $db->prepare("
                INSERT INTO MatchTeam (matchID, teamID, matchDate, sidePlayed, outcome)
                VALUES (?, ?, ?, ?, 'Pending')
            ");

            $mt_stmt->bind_param('iiss', $match_id, $blue_team_id, $match_date, $blue_side);
            $mt_stmt->execute();

            $mt_stmt->bind_param('iiss', $match_id, $red_team_id, $match_date, $red_side);
            $mt_stmt->execute();

            $mt_stmt->close();

            header('Location: record_match_result.php?match_id=' . $match_id);
            exit();
        } else {
            $error_message = 'Unable to create match.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Match</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div align="left">
    <h1>Create Match</h1>
    <p><a href="home_page.php">← Back to Homepage</a></p>

    <?php if ($error_message): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="create_match.php" method="POST">
        <label for="match_date">Match Date/Time:</label><br>
        <input type="datetime-local" id="match_date" name="match_date" required><br><br>

        <label for="blue_team_id">Blue Team:</label><br>
        <select id="blue_team_id" name="blue_team_id" required>
            <option value="">Select Blue Team</option>
            <?php while ($team = $teams_result->fetch_assoc()): ?>
                <option value="<?php echo (int)$team['teamID']; ?>">
                    <?php echo htmlspecialchars($team['teamName']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <?php
        $teams_result = $db->query("SELECT teamID, teamName FROM Teams WHERE status = 'Active' ORDER BY teamName");
        ?>

        <label for="red_team_id">Red Team:</label><br>
        <select id="red_team_id" name="red_team_id" required>
            <option value="">Select Red Team</option>
            <?php while ($team = $teams_result->fetch_assoc()): ?>
                <option value="<?php echo (int)$team['teamID']; ?>">
                    <?php echo htmlspecialchars($team['teamName']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Create Match</button>
    </form>
</div>
</body>
</html>