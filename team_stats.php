<?php
require_once('auth_helpers.php');
require_once('db_connect.php');

if (!isset($_GET['team_id'])) {
    die("<h2>Error: No Team ID provided.</h2><a href='home_page.php'>Return Home</a>");
}

$team_id = intval($_GET['team_id']);
$error_message = '';
$success_message = '';

$team_sql = "SELECT teamID AS TeamID, teamName AS TeamName FROM Teams WHERE teamID = ?";
$stmt_team = $db->prepare($team_sql);
$stmt_team->bind_param("i", $team_id);
$stmt_team->execute();
$team_info = $stmt_team->get_result()->fetch_assoc();
$stmt_team->close();

if (!$team_info) {
    die("<h2>Error: Team not found.</h2><a href='home_page.php'>Return Home</a>");
}

$can_delete_team = can_manage_team($db, $team_id);
$can_manage_roster = can_manage_team($db, $team_id);

$valid_roster_roles = ['Top', 'Jgl', 'Mid', 'Bot', 'Sup', 'Sub'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_free_agent'])) {
    if (!$can_manage_roster) {
        http_response_code(403);
        die("<h2>403 Forbidden</h2><p>You are not allowed to manage this team.</p><a href='team_stats.php?team_id=" . (int)$team_id . "'>Return to Team</a>");
    }

    $user_id = intval($_POST['user_id'] ?? 0);
    $role_in_team = $_POST['role_in_team'] ?? '';

    if ($user_id <= 0) {
        $error_message = 'Invalid player selected.';
    } elseif (!in_array($role_in_team, $valid_roster_roles, true)) {
        $error_message = 'Please select a valid roster role.';
    }

    if ($error_message === '' && $role_in_team !== 'Sub') {
        $slot_stmt = $db->prepare("
            SELECT 1
            FROM TeamMembers
            WHERE teamID = ?
              AND roleInTeam = ?
              AND status = 'Active'
            LIMIT 1
        ");
        $slot_stmt->bind_param("is", $team_id, $role_in_team);
        $slot_stmt->execute();
        $slot_taken = $slot_stmt->get_result()->fetch_assoc();
        $slot_stmt->close();

        if ($slot_taken) {
            $error_message = 'That roster role is already filled on this team.';
        }
    }

    if ($error_message === '') {
        $check_stmt = $db->prepare("
            SELECT 
                u.userID,
                u.username,
                p.userID AS playerExists
            FROM Users u
            LEFT JOIN Players p ON p.userID = u.userID
            LEFT JOIN TeamMembers tm
              ON tm.userID = u.userID
             AND tm.status = 'Active'
             AND tm.roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub')
            WHERE u.userID = ?
              AND u.roleID = 2
              AND tm.teamMemberID IS NULL
            LIMIT 1
        ");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $free_agent = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if (!$free_agent) {
            $error_message = 'That user is not an available free agent.';
        } else {
            if (!$free_agent['playerExists']) {
                $default_rank = 'Unranked';

                $player_stmt = $db->prepare("
                    INSERT INTO Players (userID, gameTag, playerRank, lp)
                    VALUES (?, ?, ?, 0)
                ");
                $player_stmt->bind_param("iss", $user_id, $free_agent['username'], $default_rank);
                $player_stmt->execute();
                $player_stmt->close();
            }

            $member_stmt = $db->prepare("
                INSERT INTO TeamMembers (teamID, userID, roleInTeam, status)
                VALUES (?, ?, ?, 'Active')
            ");
            $member_stmt->bind_param("iis", $team_id, $user_id, $role_in_team);
            $member_stmt->execute();
            $member_stmt->close();

            header('Location: team_stats.php?team_id=' . $team_id . '&player_added=1');
            exit();
        }
    }
}

if (isset($_GET['player_added']) && $_GET['player_added'] === '1') {
    $success_message = 'Player added to roster successfully.';
}

// Average stats are computed across all recorded rounds for each player.
$players_sql = "SELECT
                    p.userID,
                    p.gameTag AS GameTag,
                    p.playerRank AS `Rank`,
                    COUNT(ps.playerStatsID) AS GamesPlayed,
                    ROUND(AVG(ps.kills), 2) AS AvgKills,
                    ROUND(AVG(ps.deaths), 2) AS AvgDeaths,
                    ROUND(AVG(ps.assists), 2) AS AvgAssists,
                    ROUND(AVG(ps.goldEarned), 2) AS AvgGold
                FROM Players p
                JOIN TeamMembers tm ON tm.userID = p.userID
                LEFT JOIN PlayerStats ps ON ps.userID = p.userID
                WHERE tm.teamID = ? AND tm.roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub') AND tm.status = 'Active'
                GROUP BY p.userID, p.gameTag, p.playerRank
                ORDER BY p.gameTag ASC";
$stmt_players = $db->prepare($players_sql);
$stmt_players->bind_param("i", $team_id);
$stmt_players->execute();
$players_result = $stmt_players->get_result();

$matches_sql = "SELECT
                    m.matchID AS MatchID,
                    m.matchDate AS MatchDate,
                    mt.sidePlayed AS SidePlayed,
                    mt.outcome AS Outcome,
                    opp.teamName AS OpponentName
                FROM MatchTeam mt
                JOIN Matches m ON m.matchID = mt.matchID
                LEFT JOIN MatchTeam mt_opp ON mt_opp.matchID = mt.matchID AND mt_opp.teamID <> mt.teamID
                LEFT JOIN Teams opp ON opp.teamID = mt_opp.teamID
                WHERE mt.teamID = ?
                ORDER BY m.matchDate DESC, m.matchID DESC";
$stmt_matches = $db->prepare($matches_sql);
$stmt_matches->bind_param("i", $team_id);
$stmt_matches->execute();
$matches_result = $stmt_matches->get_result();

$free_agents_result = null;

if ($can_manage_roster) {
    $free_agents_result = $db->query("
        SELECT
            u.userID,
            u.username,
            p.gameTag
        FROM Users u
        LEFT JOIN Players p ON p.userID = u.userID
        LEFT JOIN TeamMembers tm
          ON tm.userID = u.userID
         AND tm.status = 'Active'
         AND tm.roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub')
        WHERE u.roleID = 2
          AND tm.teamMemberID IS NULL
        ORDER BY u.username ASC
    ");
}
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

        <?php if ($success_message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <?php if ($can_delete_team): ?>
            <form action="delete_team.php" method="POST" onsubmit="return confirm('Delete this team and all related records? This cannot be undone.');">
                <input type="hidden" name="team_id" value="<?php echo (int)$team_info['TeamID']; ?>">
                <button type="submit" style="margin-bottom: 12px; background-color: #a20000; color: white;">Delete Team</button>
            </form>
        <?php endif; ?>

        <?php if ($can_manage_roster): ?>
            <h2>Add Free Agent</h2>

            <table style="border-collapse: collapse; width: 75%; margin-bottom: 25px;">
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid black; padding: 5px;">Player</th>
                    <th style="border: 1px solid black; padding: 5px;">Roster Role</th>
                    <th style="border: 1px solid black; padding: 5px;">Action</th>
                </tr>

                <?php if ($free_agents_result && $free_agents_result->num_rows > 0): ?>
                    <?php while ($free_agent = $free_agents_result->fetch_assoc()): ?>
                        <tr>
                            <form action="team_stats.php?team_id=<?php echo (int)$team_id; ?>" method="POST">
                                <input type="hidden" name="add_free_agent" value="1">
                                <input type="hidden" name="user_id" value="<?php echo (int)$free_agent['userID']; ?>">

                                <td style="border: 1px solid black; padding: 5px;">
                                    <?php echo htmlspecialchars($free_agent['gameTag'] ?: $free_agent['username']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($free_agent['username']); ?></small>
                                </td>

                                <td style="border: 1px solid black; padding: 5px;">
                                    <select name="role_in_team" required>
                                        <option value="">Select Role</option>
                                        <option value="Top">Top</option>
                                        <option value="Jgl">Jgl</option>
                                        <option value="Mid">Mid</option>
                                        <option value="Bot">Bot</option>
                                        <option value="Sup">Sup</option>
                                        <option value="Sub">Sub</option>
                                    </select>
                                </td>

                                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                                    <button type="submit">Add</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="border: 1px solid black; padding: 10px; text-align: center;">
                            No free agents available.
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>

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
                    <td style="border: 1px solid black; padding: 5px;">
                        <a href="player_stats.php?player_id=<?php echo (int)$row['userID']; ?>">
                            <?php echo htmlspecialchars($row['GameTag']); ?>
                        </a>
                    </td>
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

        <br><br>

        <h2>Team Match History</h2>
        <table style="border-collapse: collapse; width: 90%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Match ID</th>
                <th style="border: 1px solid black; padding: 5px;">Date</th>
                <th style="border: 1px solid black; padding: 5px;">Opponent</th>
                <th style="border: 1px solid black; padding: 5px;">Side</th>
                <th style="border: 1px solid black; padding: 5px;">Outcome</th>
                <th style="border: 1px solid black; padding: 5px;">Action</th>
            </tr>
            <?php if ($matches_result->num_rows > 0): ?>
                <?php while($match_row = $matches_result->fetch_assoc()): ?>
                <tr>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo (int)$match_row['MatchID']; ?></td>
                    <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($match_row['MatchDate']); ?></td>
                    <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($match_row['OpponentName'] ?? 'TBD'); ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo htmlspecialchars($match_row['SidePlayed'] ?? 'N/A'); ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo htmlspecialchars($match_row['Outcome'] ?? 'Pending'); ?></td>
                    <td style="border: 1px solid black; padding: 5px; text-align: center;">
                        <a href="match_stats.php?match_id=<?php echo (int)$match_row['MatchID']; ?>"><strong>View Match</strong></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="border: 1px solid black; padding: 10px; text-align: center;">
                        No matches found for this team.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>