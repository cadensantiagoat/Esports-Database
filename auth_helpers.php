<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user_id(): ?int
{
    return isset($_SESSION['userID']) ? (int)$_SESSION['userID'] : null;
}

function current_user_role(): string
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] === '') {
        return 'Observer';
    }
    return (string)$_SESSION['role'];
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function is_observer(): bool
{
    return !is_logged_in() || current_user_role() === 'Observer';
}

function require_login(string $redirect_to = 'login.php'): void
{
    if (!is_logged_in()) {
        header('Location: ' . $redirect_to);
        exit();
    }
}

function require_role(array $allowed_roles): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }

    if (!in_array(current_user_role(), $allowed_roles, true)) {
        http_response_code(403);
        echo "<h2>403 Forbidden</h2><p>You do not have permission to access this page.</p><p><a href='home_page.php'>Return Home</a></p>";
        exit();
    }
}

function can_edit_own_profile(int $target_user_id): bool
{
    return is_logged_in() && current_user_id() === $target_user_id;
}

function can_manage_roles(): bool
{
    return current_user_role() === 'League Owner';
}

function can_reset_any_password(): bool
{
    return current_user_role() === 'League Owner';
}

/*
 * Team scope helper.
 * esport_ddl2.sql does not map coaches to teams,
 * so this returns false for Coach by design until final DDL adds a team link.
 */
function can_manage_team(mysqli $db, int $team_id): bool
{
    $role = current_user_role();
    if ($role === 'League Owner') {
        return true;
    }

    $user_id = current_user_id();
    if ($user_id === null) {
        return false;
    }

    if ($role === 'Player') {
        $stmt = $db->prepare("SELECT TeamID FROM Players WHERE userID = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && (int)$row['TeamID'] === $team_id;
    }

    if ($role === 'Coach') {
        $stmt = $db->prepare("SELECT TeamID FROM Coaches WHERE userID = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && (int)$row['TeamID'] === $team_id;
    }

    return false;
}

function can_edit_stat_for_player(mysqli $db, int $stat_player_id): bool
{
    $role = current_user_role();

    if ($role === 'League Owner') {
        return true;
    }

    if ($role === 'Player') {
        return current_user_id() === $stat_player_id;
    }

    if ($role === 'Coach') {
        $stmt = $db->prepare("SELECT TeamID FROM Players WHERE userID = ? LIMIT 1");
        $stmt->bind_param("i", $stat_player_id);
        $stmt->execute();
        $player_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$player_row || !isset($player_row['TeamID'])) {
            return false;
        }

        return can_manage_team($db, (int)$player_row['TeamID']);
    }

    return false;
}
?>
