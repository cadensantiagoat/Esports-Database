<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_permission($db, 'create_team');

$error_message = '';
$team_name = '';
$team_status = 'Active';
$assign_self_as_coach = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $team_status = $_POST['status'] ?? 'Active';
    $assign_self_as_coach = isset($_POST['assign_self_as_coach']);

    if ($team_name === '') {
        $error_message = 'Team name is required.';
    } elseif (strlen($team_name) > 100) {
        $error_message = 'Team name must be 100 characters or fewer.';
    } elseif (!in_array($team_status, ['Active', 'Inactive'], true)) {
        $error_message = 'Invalid team status.';
    } else {
        $check_stmt = $db->prepare('SELECT teamID FROM Teams WHERE teamName = ? LIMIT 1');
        $check_stmt->bind_param('s', $team_name);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($existing) {
            $error_message = 'A team with that name already exists.';
        } else {
            $insert_stmt = $db->prepare('INSERT INTO Teams (teamName, status) VALUES (?, ?)');
            $insert_stmt->bind_param('ss', $team_name, $team_status);

            if ($insert_stmt->execute()) {
                $new_team_id = (int)$insert_stmt->insert_id;
                $insert_stmt->close();

                // Coaches can optionally attach themselves as the new team's coach.
                if (has_permission($db, 'manage_own_team') && $assign_self_as_coach) {
                    $coach_user_id = current_user_id();
                    if ($coach_user_id !== null) {
                        $member_stmt = $db->prepare(
                            "INSERT INTO TeamMembers (teamID, userID, roleInTeam, status)
                             VALUES (?, ?, 'Coach', 'Active')
                             ON DUPLICATE KEY UPDATE roleInTeam = VALUES(roleInTeam), status = VALUES(status)"
                        );
                        $member_stmt->bind_param('ii', $new_team_id, $coach_user_id);
                        $member_stmt->execute();
                        $member_stmt->close();
                    }
                }

                header('Location: team_stats.php?team_id=' . $new_team_id);
                exit();
            }

            $error_message = 'Unable to create team right now. Please try again.';
            $insert_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>Create New Team</h1>
        <p><a href="home_page.php">← Back to Homepage</a></p>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="createTeam.php" method="POST" autocomplete="off">
            <label for="team_name">Team Name:</label><br>
            <input
                type="text"
                id="team_name"
                name="team_name"
                maxlength="100"
                required
                value="<?php echo htmlspecialchars($team_name); ?>"
            ><br><br>

            <label for="status">Status:</label><br>
            <select id="status" name="status">
                <option value="Active" <?php echo $team_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $team_status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select><br><br>

            <?php if (has_permission($db, 'manage_own_team')): ?>
                <label>
                    <input type="checkbox" name="assign_self_as_coach" <?php echo $assign_self_as_coach ? 'checked' : ''; ?>>
                    Assign me as this team's coach
                </label><br><br>
            <?php endif; ?>

            <button type="submit">Create Team</button>
        </form>
    </div>
</body>
</html>
