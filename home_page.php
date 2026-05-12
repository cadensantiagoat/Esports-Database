<?php
  require_once('auth_helpers.php');
  require_once('db_connect.php');

  // Get all Teams
  $teams_sql = "SELECT teamID AS TeamID, teamName AS TeamName FROM Teams ORDER BY teamName";
  $teams_result = mysqli_query($db, $teams_sql);

  // Get all Matches with blue/red teams.
  $matches_sql = "SELECT 
                    m.matchID AS MatchID,
                    m.matchDate AS MatchDate,
                    MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN t.teamName END) AS Team1_Name,
                    MAX(CASE WHEN mt.sidePlayed = 'Red' THEN t.teamName END) AS Team2_Name
                  FROM Matches m
                  JOIN MatchTeam mt ON mt.matchID = m.matchID
                  JOIN Teams t ON t.teamID = mt.teamID
                  GROUP BY m.matchID, m.matchDate
                  ORDER BY m.matchDate DESC";
  $matches_result = mysqli_query($db, $matches_sql);
  $team_deleted_notice = trim($_GET['team_deleted'] ?? '');
?>

<!DOCTYPE html>
<html>
<head>
    <title>LCK Esports Database</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>LCK Esports League</h1>
        
        <p>
            <?php if (is_logged_in()): ?>
                Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> (<?php echo htmlspecialchars(current_user_role()); ?>)!

                <?php if (has_permission($db, 'create_team')): ?>
                    | <a href="createTeam.php">Create team</a>
                <?php endif; ?>

                <?php if (has_permission($db, 'create_matches')): ?>
                    | <a href="create_match.php">Create match</a>
                <?php endif; ?>

                <?php if (has_permission($db, 'edit_match_data')): ?>
                    | <a href="record_match_result.php">Record match</a>
                <?php endif; ?>

                <?php if (has_permission($db, 'manage_roles')): ?>
                    | <a href="manage_roles.php">Manage roles</a>
                <?php endif; ?>

                | <a href="reset_password.php">Reset password</a> |
                <a href="logout.php">Logout</a>
            <?php else: ?>
                Viewing as Visitor. <a href="login.php">Login</a> or <a href="register.php">Register</a>
            <?php endif; ?>
        </p>

        <hr>
        <?php if ($team_deleted_notice !== ''): ?>
            <p style="color: green;">Team deleted successfully: <?php echo htmlspecialchars($team_deleted_notice); ?></p>
        <?php endif; ?>

        <h2>Active Teams</h2>
        <table style="border-collapse: collapse; width: 50%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Team ID</th>
                <th style="border: 1px solid black; padding: 5px;">Team Name</th>
                <th style="border: 1px solid black; padding: 5px;">Action</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($teams_result)): ?>
            <tr>
                <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['TeamID']; ?></td>
                <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['TeamName']); ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                    <a href="team_stats.php?team_id=<?php echo $row['TeamID']; ?>"><strong>View Team</strong></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <br><br>

        <h2>Matches</h2>
        <table style="border-collapse: collapse; width: 80%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Match Date</th>
                <th style="border: 1px solid black; padding: 5px;">Home</th>
                <th style="border: 1px solid black; padding: 5px;">vs</th>
                <th style="border: 1px solid black; padding: 5px;">Away</th>
                <th style="border: 1px solid black; padding: 5px;">Action</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($matches_result)): ?>
            <tr>
                <td style="border: 1px solid black; padding: 5px;"><?php echo $row['MatchDate']; ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: right;"><?php echo htmlspecialchars($row['Team1_Name']); ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;">vs</td>
                <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['Team2_Name']); ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                    <a href="match_stats.php?match_id=<?php echo $row['MatchID']; ?>"><strong>View Stats</strong></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>