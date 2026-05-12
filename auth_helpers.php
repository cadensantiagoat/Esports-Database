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
        return 'Visitor';
    }
    return (string)$_SESSION['role'];
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function is_observer(): bool
{
    return !is_logged_in() || current_user_role() === 'Visitor';
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


function can_manage_roles(mysqli $db): bool
{
    return has_permission($db, 'manage_roles');
}

function can_reset_any_password(mysqli $db): bool
{
    return has_permission($db, 'reset_any_password');
}

/*
 * Team scope helper.
 * Team scope is resolved via TeamMembers.
 */
function can_manage_team(mysqli $db, int $team_id): bool
{
    if (has_permission($db, 'manage_all_teams')) {
        return true;
    }

    $user_id = current_user_id();
    if ($user_id === null) {
        return false;
    }

    if (has_permission($db, 'manage_own_team')) {
        $stmt = $db->prepare("SELECT 1 FROM TeamMembers WHERE teamID = ? AND userID = ? AND roleInTeam = 'Coach' AND status = 'Active' LIMIT 1");
        $stmt->bind_param("ii", $team_id, $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$row;
    }

    return false;
}

function can_edit_stat_for_player(mysqli $db, int $stat_player_id): bool
{
    if (has_permission($db, 'edit_all_stats')) {
        return true;
    }

    if (has_permission($db, 'edit_own_stats')) {
        return current_user_id() === $stat_player_id;
    }

    if (has_permission($db, 'manage_own_team')) {
        $stmt = $db->prepare("SELECT teamID FROM TeamMembers WHERE userID = ? AND roleInTeam IN ('Top','Jgl','Mid','Bot','Sup','Sub') AND status = 'Active' LIMIT 1");
        $stmt->bind_param("i", $stat_player_id);
        $stmt->execute();
        $player_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$player_row || !isset($player_row['teamID'])) {
            return false;
        }

        return can_manage_team($db, (int)$player_row['teamID']);
    }

    return false;
}


function current_role_id(): ?int
{
    return isset($_SESSION['roleID']) ? (int)$_SESSION['roleID'] : null;
}

function has_permission(mysqli $db, string $permission_name): bool
{
    $role_id = current_role_id();

    if ($role_id === null) {
        return false;
    }

    $stmt = $db->prepare("
        SELECT 1
        FROM RolePermissions
        WHERE roleID = ?
          AND permissionName = ?
        LIMIT 1
    ");

    $stmt->bind_param("is", $role_id, $permission_name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (bool)$row;
}

function require_permission(mysqli $db, string $permission_name): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }

    if (!has_permission($db, $permission_name)) {
        http_response_code(403);
        echo "<h2>403 Forbidden</h2><p>You do not have permission to access this page.</p><p><a href='home_page.php'>Return Home</a></p>";
        exit();
    }
}


?>
