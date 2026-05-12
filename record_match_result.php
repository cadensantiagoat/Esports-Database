<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_permission($db, 'edit_match_data');

function fetch_match_context(mysqli $db, int $match_id): ?array
{
    $stmt = $db->prepare("
        SELECT
            m.matchID,
            m.matchDate,
            m.teamWon,
            MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN mt.teamID END) AS BlueTeamID,
            MAX(CASE WHEN mt.sidePlayed = 'Blue' THEN t.teamName END) AS BlueTeam,
            MAX(CASE WHEN mt.sidePlayed = 'Red' THEN mt.teamID END) AS RedTeamID,
            MAX(CASE WHEN mt.sidePlayed = 'Red' THEN t.teamName END) AS RedTeam
        FROM Matches m
        JOIN MatchTeam mt ON mt.matchID = m.matchID
        JOIN Teams t ON t.teamID = mt.teamID
        WHERE m.matchID = ?
        GROUP BY m.matchID, m.matchDate, m.teamWon
        LIMIT 1
    ");
    $stmt->bind_param('i', $match_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function fetch_match_players(mysqli $db, int $match_id): array
{
    $stmt = $db->prepare("
        SELECT
            mt.teamID,
            t.teamName,
            mt.sidePlayed,
            p.userID,
            p.gameTag,
            tm.roleInTeam
        FROM MatchTeam mt
        JOIN Teams t ON t.teamID = mt.teamID
        JOIN TeamMembers tm
          ON tm.teamID = mt.teamID
         AND tm.status = 'Active'
         AND tm.roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub')
        JOIN Players p ON p.userID = tm.userID
        WHERE mt.matchID = ?
        ORDER BY
            CASE mt.sidePlayed WHEN 'Blue' THEN 1 WHEN 'Red' THEN 2 ELSE 3 END,
            CASE tm.roleInTeam
                WHEN 'Top' THEN 1
                WHEN 'Jgl' THEN 2
                WHEN 'Mid' THEN 3
                WHEN 'Bot' THEN 4
                WHEN 'Sup' THEN 5
                WHEN 'Sub' THEN 6
                ELSE 7
            END,
            p.gameTag
    ");
    $stmt->bind_param('i', $match_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $players_by_team = [];
    while ($row = $result->fetch_assoc()) {
        $team_id = (int)$row['teamID'];
        if (!isset($players_by_team[$team_id])) {
            $players_by_team[$team_id] = [
                'teamID' => $team_id,
                'teamName' => $row['teamName'],
                'sidePlayed' => $row['sidePlayed'],
                'players' => [],
            ];
        }
        $players_by_team[$team_id]['players'][] = [
            'userID' => (int)$row['userID'],
            'gameTag' => $row['gameTag'],
            'roleInTeam' => $row['roleInTeam'],
        ];
    }

    $stmt->close();
    return $players_by_team;
}

function fetch_existing_round(mysqli $db, int $match_id): ?array
{
    $stmt = $db->prepare("
        SELECT roundID, duration, outcome
        FROM Rounds
        WHERE matchID = ?
          AND roundNumber = 1
        LIMIT 1
    ");
    $stmt->bind_param('i', $match_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function fetch_existing_player_stats(mysqli $db, int $round_id): array
{
    $stmt = $db->prepare("
        SELECT userID, championPlayed, kills, deaths, assists, creepScore, goldEarned
        FROM PlayerStats
        WHERE roundID = ?
    ");
    $stmt->bind_param('i', $round_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[(int)$row['userID']] = [
            'champion' => $row['championPlayed'] ?? '',
            'kills' => (string)$row['kills'],
            'deaths' => (string)$row['deaths'],
            'assists' => (string)$row['assists'],
            'creep_score' => (string)$row['creepScore'],
            'gold_earned' => (string)$row['goldEarned'],
        ];
    }

    $stmt->close();
    return $stats;
}

function normalize_duration(string $duration): ?string
{
    $duration = trim($duration);
    if ($duration === '') {
        return null;
    }
    if (preg_match('/^\d{2}:\d{2}$/', $duration)) {
        return $duration . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) {
        return $duration;
    }
    return null;
}

function player_field_value(string $field, int $user_id, array $existing_stats): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$field]) && is_array($_POST[$field]) && array_key_exists((string)$user_id, $_POST[$field])) {
        return (string)$_POST[$field][(string)$user_id];
    }
    return $existing_stats[$user_id][$field] ?? '';
}

function player_checked(int $user_id, array $existing_stats): bool
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST['played']) && is_array($_POST['played']) && isset($_POST['played'][(string)$user_id]);
    }
    if ($existing_stats !== []) {
        return isset($existing_stats[$user_id]);
    }
    return true;
}

$error_message = '';
$selected_match_id = intval($_GET['match_id'] ?? ($_POST['match_id'] ?? 0));

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

$match_context = null;
$players_by_team = [];
$existing_round = null;
$existing_stats = [];

if ($selected_match_id > 0) {
    $match_context = fetch_match_context($db, $selected_match_id);
    if ($match_context) {
        $players_by_team = fetch_match_players($db, $selected_match_id);
        $existing_round = fetch_existing_round($db, $selected_match_id);
        if ($existing_round) {
            $existing_stats = fetch_existing_player_stats($db, (int)$existing_round['roundID']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = intval($_POST['match_id'] ?? 0);
    $winning_team_id = intval($_POST['winning_team_id'] ?? 0);
    $duration = normalize_duration($_POST['duration'] ?? '');

    if ($match_id <= 0 || !$match_context) {
        $error_message = 'Please select a valid match.';
    } elseif ($winning_team_id <= 0) {
        $error_message = 'Please select a winner.';
    } elseif (!in_array($winning_team_id, [(int)$match_context['BlueTeamID'], (int)$match_context['RedTeamID']], true)) {
        $error_message = 'Winner must be one of the teams in the match.';
    } elseif ($duration === null) {
        $error_message = 'Please provide a valid round duration.';
    } elseif (count($players_by_team) !== 2) {
        $error_message = 'This match must have exactly two teams before stats can be recorded.';
    } else {
        $posted_played = $_POST['played'] ?? [];
        $champions = $_POST['champion'] ?? [];
        $kills_input = $_POST['kills'] ?? [];
        $deaths_input = $_POST['deaths'] ?? [];
        $assists_input = $_POST['assists'] ?? [];
        $cs_input = $_POST['creep_score'] ?? [];
        $gold_input = $_POST['gold_earned'] ?? [];

        $team_player_counts = [];
        $player_payloads = [];

        foreach ($players_by_team as $team_id => $team_info) {
            $team_player_counts[$team_id] = 0;
            foreach ($team_info['players'] as $player) {
                $user_id = $player['userID'];
                $user_key = (string)$user_id;
                $played = is_array($posted_played) && isset($posted_played[$user_key]);

                if (!$played) {
                    continue;
                }

                $team_player_counts[$team_id]++;

                $champion = trim((string)($champions[$user_key] ?? ''));
                $kills = filter_var($kills_input[$user_key] ?? null, FILTER_VALIDATE_INT);
                $deaths = filter_var($deaths_input[$user_key] ?? null, FILTER_VALIDATE_INT);
                $assists = filter_var($assists_input[$user_key] ?? null, FILTER_VALIDATE_INT);
                $creep_score = filter_var($cs_input[$user_key] ?? null, FILTER_VALIDATE_INT);
                $gold_earned = filter_var($gold_input[$user_key] ?? null, FILTER_VALIDATE_INT);

                if (strlen($champion) > 50) {
                    $error_message = 'Champion names must be 50 characters or fewer.';
                    break 2;
                }

                foreach ([$kills, $deaths, $assists, $creep_score, $gold_earned] as $numeric_value) {
                    if ($numeric_value === false || $numeric_value < 0) {
                        $error_message = 'Kills, deaths, assists, CS, and gold must be non-negative integers.';
                        break 3;
                    }
                }

                $player_payloads[] = [
                    'teamID' => (int)$team_id,
                    'userID' => $user_id,
                    'champion' => $champion,
                    'kills' => (int)$kills,
                    'deaths' => (int)$deaths,
                    'assists' => (int)$assists,
                    'creepScore' => (int)$creep_score,
                    'goldEarned' => (int)$gold_earned,
                ];
            }
        }

        foreach ($players_by_team as $team_id => $_team_info) {
            if (($team_player_counts[$team_id] ?? 0) === 0) {
                $error_message = 'Select at least one player from each team who participated in the match.';
                break;
            }
        }

        if ($error_message === '') {
            $round_outcome = ((int)$winning_team_id === (int)$match_context['BlueTeamID']) ? 'Team1 Win' : 'Team2 Win';
            $team_totals = [];
            foreach ($players_by_team as $team_id => $_team_info) {
                $team_totals[(int)$team_id] = ['kills' => 0, 'gold' => 0];
            }
            foreach ($player_payloads as $payload) {
                $team_totals[$payload['teamID']]['kills'] += $payload['kills'];
                $team_totals[$payload['teamID']]['gold'] += $payload['goldEarned'];
            }

            $db->begin_transaction();

            try {
                $existing_round_id = $existing_round ? (int)$existing_round['roundID'] : 0;
                $round_id = $existing_round_id;

                if ($existing_round_id > 0) {
                    $round_stmt = $db->prepare("
                        UPDATE Rounds
                        SET duration = ?, outcome = ?
                        WHERE roundID = ?
                    ");
                    $round_stmt->bind_param('ssi', $duration, $round_outcome, $existing_round_id);
                    if (!$round_stmt->execute()) {
                        throw new RuntimeException('Unable to update round data.');
                    }
                    $round_stmt->close();
                } else {
                    $round_number = 1;
                    $round_stmt = $db->prepare("
                        INSERT INTO Rounds (matchID, roundNumber, duration, outcome)
                        VALUES (?, ?, ?, ?)
                    ");
                    $round_stmt->bind_param('iiss', $match_id, $round_number, $duration, $round_outcome);
                    if (!$round_stmt->execute()) {
                        throw new RuntimeException('Unable to create round data.');
                    }
                    $round_id = (int)$round_stmt->insert_id;
                    $round_stmt->close();
                }

                $match_stmt = $db->prepare("UPDATE Matches SET teamWon = ? WHERE matchID = ?");
                $match_stmt->bind_param('ii', $winning_team_id, $match_id);
                if (!$match_stmt->execute()) {
                    throw new RuntimeException('Unable to save the winning team.');
                }
                $match_stmt->close();

                $win_match_outcome = 'Win';
                $loss_match_outcome = 'Loss';
                $matchteam_stmt = $db->prepare("
                    UPDATE MatchTeam
                    SET outcome = CASE WHEN teamID = ? THEN ? ELSE ? END
                    WHERE matchID = ?
                ");
                $matchteam_stmt->bind_param('issi', $winning_team_id, $win_match_outcome, $loss_match_outcome, $match_id);
                if (!$matchteam_stmt->execute()) {
                    throw new RuntimeException('Unable to update team match outcomes.');
                }
                $matchteam_stmt->close();

                $player_upsert = $db->prepare("
                    INSERT INTO PlayerStats
                        (userID, roundID, teamID, championPlayed, kills, deaths, assists, creepScore, goldEarned)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        teamID = VALUES(teamID),
                        championPlayed = VALUES(championPlayed),
                        kills = VALUES(kills),
                        deaths = VALUES(deaths),
                        assists = VALUES(assists),
                        creepScore = VALUES(creepScore),
                        goldEarned = VALUES(goldEarned)
                ");

                foreach ($player_payloads as $payload) {
                    $player_upsert->bind_param(
                        'iiisiiiii',
                        $payload['userID'],
                        $round_id,
                        $payload['teamID'],
                        $payload['champion'],
                        $payload['kills'],
                        $payload['deaths'],
                        $payload['assists'],
                        $payload['creepScore'],
                        $payload['goldEarned']
                    );
                    if (!$player_upsert->execute()) {
                        throw new RuntimeException('Unable to save one or more player stat rows.');
                    }
                }
                $player_upsert->close();

                $player_delete = $db->prepare("DELETE FROM PlayerStats WHERE userID = ? AND roundID = ?");
                foreach ($players_by_team as $team_info) {
                    foreach ($team_info['players'] as $player) {
                        $user_id = $player['userID'];
                        $played = is_array($posted_played) && isset($posted_played[(string)$user_id]);
                        if ($played) {
                            continue;
                        }
                        $player_delete->bind_param('ii', $user_id, $round_id);
                        if (!$player_delete->execute()) {
                            throw new RuntimeException('Unable to remove unchecked player stats.');
                        }
                    }
                }
                $player_delete->close();

                $teamstats_upsert = $db->prepare("
                    INSERT INTO TeamStats
                        (teamID, roundID, totalKills, totalGold, roundOutcome, totalPoints)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        totalKills = VALUES(totalKills),
                        totalGold = VALUES(totalGold),
                        roundOutcome = VALUES(roundOutcome),
                        totalPoints = VALUES(totalPoints)
                ");

                foreach ($players_by_team as $team_id => $_team_info) {
                    $team_id = (int)$team_id;
                    $round_team_outcome = ($team_id === $winning_team_id) ? 'Win' : 'Loss';
                    $total_points = ($team_id === $winning_team_id) ? 1 : 0;
                    $total_kills = $team_totals[$team_id]['kills'];
                    $total_gold = $team_totals[$team_id]['gold'];

                    $teamstats_upsert->bind_param(
                        'iiiisi',
                        $team_id,
                        $round_id,
                        $total_kills,
                        $total_gold,
                        $round_team_outcome,
                        $total_points
                    );
                    if (!$teamstats_upsert->execute()) {
                        throw new RuntimeException('Unable to save team totals.');
                    }
                }
                $teamstats_upsert->close();

                $db->commit();
                header('Location: match_stats.php?match_id=' . $match_id);
                exit();
            } catch (Throwable $e) {
                $db->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
}

$selected_winner_id = (int)($_POST['winning_team_id'] ?? ($match_context['teamWon'] ?? 0));
$duration_value = $_POST['duration'] ?? ($existing_round['duration'] ?? '00:35:00');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Record Match Result</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div align="left">
    <h1>Record Match Result &amp; Player Stats</h1>
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

    <?php if ($match_context && !empty($players_by_team)): ?>
        <br>
        <p>
            <strong>Match:</strong>
            <?php echo htmlspecialchars($match_context['matchDate'] . ' - ' . $match_context['BlueTeam'] . ' vs ' . $match_context['RedTeam']); ?>
        </p>
        <form action="record_match_result.php" method="POST">
            <input type="hidden" name="match_id" value="<?php echo (int)$selected_match_id; ?>">

            <label for="winning_team_id">Winner:</label><br>
            <select id="winning_team_id" name="winning_team_id" required>
                <option value="">Select Winner</option>
                <option value="<?php echo (int)$match_context['BlueTeamID']; ?>" <?php echo $selected_winner_id === (int)$match_context['BlueTeamID'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($match_context['BlueTeam']); ?>
                </option>
                <option value="<?php echo (int)$match_context['RedTeamID']; ?>" <?php echo $selected_winner_id === (int)$match_context['RedTeamID'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($match_context['RedTeam']); ?>
                </option>
            </select><br><br>

            <label for="duration">Round Duration:</label><br>
            <input type="time" id="duration" name="duration" step="1" value="<?php echo htmlspecialchars($duration_value); ?>" required><br><br>

            <?php foreach ($players_by_team as $team_info): ?>
                <h2><?php echo htmlspecialchars($team_info['teamName']); ?> (<?php echo htmlspecialchars($team_info['sidePlayed']); ?>)</h2>
                <table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 1px solid black; padding: 5px;">Played</th>
                        <th style="border: 1px solid black; padding: 5px;">Player</th>
                        <th style="border: 1px solid black; padding: 5px;">Role</th>
                        <th style="border: 1px solid black; padding: 5px;">Champion</th>
                        <th style="border: 1px solid black; padding: 5px;">Kills</th>
                        <th style="border: 1px solid black; padding: 5px;">Deaths</th>
                        <th style="border: 1px solid black; padding: 5px;">Assists</th>
                        <th style="border: 1px solid black; padding: 5px;">CS</th>
                        <th style="border: 1px solid black; padding: 5px;">Gold</th>
                    </tr>
                    <?php foreach ($team_info['players'] as $player): ?>
                        <?php $user_id = (int)$player['userID']; ?>
                        <tr>
                            <td style="border: 1px solid black; padding: 5px; text-align: center;">
                                <input type="checkbox" name="played[<?php echo $user_id; ?>]" <?php echo player_checked($user_id, $existing_stats) ? 'checked' : ''; ?>>
                            </td>
                            <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($player['gameTag']); ?></td>
                            <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo htmlspecialchars($player['roleInTeam']); ?></td>
                            <td style="border: 1px solid black; padding: 5px;">
                                <input type="text" name="champion[<?php echo $user_id; ?>]" maxlength="50" value="<?php echo htmlspecialchars(player_field_value('champion', $user_id, $existing_stats)); ?>">
                            </td>
                            <td style="border: 1px solid black; padding: 5px;">
                                <input type="number" name="kills[<?php echo $user_id; ?>]" min="0" value="<?php echo htmlspecialchars(player_field_value('kills', $user_id, $existing_stats) ?: '0'); ?>" style="width: 70px;">
                            </td>
                            <td style="border: 1px solid black; padding: 5px;">
                                <input type="number" name="deaths[<?php echo $user_id; ?>]" min="0" value="<?php echo htmlspecialchars(player_field_value('deaths', $user_id, $existing_stats) ?: '0'); ?>" style="width: 70px;">
                            </td>
                            <td style="border: 1px solid black; padding: 5px;">
                                <input type="number" name="assists[<?php echo $user_id; ?>]" min="0" value="<?php echo htmlspecialchars(player_field_value('assists', $user_id, $existing_stats) ?: '0'); ?>" style="width: 70px;">
                            </td>
                            <td style="border: 1px solid black; padding: 5px;">
                                <input type="number" name="creep_score[<?php echo $user_id; ?>]" min="0" value="<?php echo htmlspecialchars(player_field_value('creep_score', $user_id, $existing_stats) ?: '0'); ?>" style="width: 90px;">
                            </td>
                            <td style="border: 1px solid black; padding: 5px;">
                                <input type="number" name="gold_earned[<?php echo $user_id; ?>]" min="0" value="<?php echo htmlspecialchars(player_field_value('gold_earned', $user_id, $existing_stats) ?: '0'); ?>" style="width: 100px;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>

            <button type="submit">Save Result and Stats</button>
        </form>
    <?php elseif ($selected_match_id > 0): ?>
        <p style="color:red;">No active players were found for one or both teams in this match.</p>
    <?php endif; ?>
</div>
</body>
</html>