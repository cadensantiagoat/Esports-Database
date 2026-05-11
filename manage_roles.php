<?php
require_once 'auth_helpers.php';
require_once 'db_connect.php';

require_permission($db, 'manage_roles');

$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['user_id'] ?? 0);
    $new_role_id = intval($_POST['role_id'] ?? 0);

    if ($target_user_id <= 0 || $new_role_id <= 0) {
        $error_message = 'Invalid user or role selected.';
    } elseif ($target_user_id === current_user_id()) {
        $error_message = 'You cannot change your own role.';
    } else {
        $role_stmt = $db->prepare("
            SELECT roleID
            FROM Roles
            WHERE roleID = ?
              AND canBeAssigned = TRUE
            LIMIT 1
        ");
        $role_stmt->bind_param('i', $new_role_id);
        $role_stmt->execute();
        $new_role = $role_stmt->get_result()->fetch_assoc();
        $role_stmt->close();

        if (!$new_role) {
            $error_message = 'Selected role cannot be assigned.';
        } else {
            $target_stmt = $db->prepare("
                SELECT u.userID, r.canBeAssigned
                FROM Users u
                JOIN Roles r ON u.roleID = r.roleID
                WHERE u.userID = ?
                LIMIT 1
            ");
            $target_stmt->bind_param('i', $target_user_id);
            $target_stmt->execute();
            $target_user = $target_stmt->get_result()->fetch_assoc();
            $target_stmt->close();

            if (!$target_user) {
                $error_message = 'User not found.';
            } elseif ((int)$target_user['canBeAssigned'] !== 1) {
                $error_message = 'You cannot change this user’s role.';
            } else {
                $update_stmt = $db->prepare("
                    UPDATE Users
                    SET roleID = ?
                    WHERE userID = ?
                ");
                $update_stmt->bind_param('ii', $new_role_id, $target_user_id);
                $update_stmt->execute();
                $update_stmt->close();

                $message = 'Role updated successfully.';
            }
        }
    }
}

$roles_result = $db->query("
    SELECT roleID, roleName
    FROM Roles
    WHERE canBeAssigned = TRUE
    ORDER BY roleID
");

$roles = [];
while ($role = $roles_result->fetch_assoc()) {
    $roles[] = $role;
}

$users_result = $db->query("
    SELECT
        u.userID,
        u.firstName,
        u.lastName,
        u.username,
        u.email,
        r.roleID,
        r.roleName,
        r.canBeAssigned
    FROM Users u
    JOIN Roles r ON u.roleID = r.roleID
    ORDER BY u.username
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User Roles</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div align="left">
    <h1>Manage User Roles</h1>
    <p><a href="home_page.php">← Back to Homepage</a></p>

    <?php if ($message): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <table style="border-collapse: collapse; width: 95%;">
        <tr style="background-color: #f2f2f2;">
            <th style="border: 1px solid black; padding: 5px;">User</th>
            <th style="border: 1px solid black; padding: 5px;">Email</th>
            <th style="border: 1px solid black; padding: 5px;">Current Role</th>
            <th style="border: 1px solid black; padding: 5px;">New Role</th>
            <th style="border: 1px solid black; padding: 5px;">Action</th>
        </tr>

        <?php while ($user = $users_result->fetch_assoc()): ?>
            <tr>
                <td style="border: 1px solid black; padding: 5px;">
                    <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?><br>
                    <small><?php echo htmlspecialchars($user['username']); ?></small>
                </td>

                <td style="border: 1px solid black; padding: 5px;">
                    <?php echo htmlspecialchars($user['email']); ?>
                </td>

                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                    <?php echo htmlspecialchars($user['roleName']); ?>
                </td>

                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                    <?php if ((int)$user['canBeAssigned'] !== 1 || (int)$user['userID'] === current_user_id()): ?>
                        Locked
                    <?php else: ?>
                        <form action="manage_roles.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo (int)$user['userID']; ?>">

                            <select name="role_id">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo (int)$role['roleID']; ?>"
                                        <?php echo (int)$role['roleID'] === (int)$user['roleID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['roleName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                    <?php endif; ?>
                </td>

                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                    <?php if ((int)$user['canBeAssigned'] !== 1 || (int)$user['userID'] === current_user_id()): ?>
                        No changes allowed
                    <?php else: ?>
                            <button type="submit">Update Role</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>