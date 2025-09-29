<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: Secure backend handler for all user management actions (create,
 * update, delete). Includes a logging function to record all changes.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation with create, update, and delete
 * functions and integrated activity logging.
 */
session_start();

// Security: Centralized check for all actions
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once '../src/db.php.bak';

$actor_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

/**
 * Logs an activity to the database.
 * @param PDO $db The database connection.
 * @param int $actor_id The ID of the user performing the action.
 * @param string $action A short description of the action (e.g., 'USER_CREATE').
 * @param int|null $target_id The ID of the user/object being acted upon.
 * @param string|null $details More detailed information about the action.
 */
function log_activity($db, $actor_id, $action, $target_id = null, $details = null) {
    $stmt = $db->prepare("INSERT INTO activity_logs (actor_id, action, target_id, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$actor_id, $action, $target_id, $details]);
}

$db = get_db_connection();

switch ($action) {
    case 'create_user':
        $hashed_password = password_hash($_POST['Password'], PASSWORD_ARGON2ID);
        $stmt = $db->prepare("INSERT INTO users (Fname, LName, Username, EmailID, Password, UserType, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['Fname'], $_POST['LName'], $_POST['Username'], $_POST['EmailID'],
            $hashed_password, $_POST['UserType'], $_POST['is_active']
        ]);
        $new_user_id = $db->lastInsertId();
        log_activity($db, $actor_id, 'USER_CREATE', $new_user_id, "Created user: {$_POST['Username']}");
        break;

    case 'update_user':
        $user_id = $_POST['user_id'];
        if (!empty($_POST['Password'])) {
            $hashed_password = password_hash($_POST['Password'], PASSWORD_ARGON2ID);
            $stmt = $db->prepare("UPDATE users SET Fname=?, LName=?, Username=?, EmailID=?, Password=?, UserType=?, is_active=? WHERE id=?");
            $stmt->execute([$_POST['Fname'], $_POST['LName'], $_POST['Username'], $_POST['EmailID'], $hashed_password, $_POST['UserType'], $_POST['is_active'], $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET Fname=?, LName=?, Username=?, EmailID=?, UserType=?, is_active=? WHERE id=?");
            $stmt->execute([$_POST['Fname'], $_POST['LName'], $_POST['Username'], $_POST['EmailID'], $_POST['UserType'], $_POST['is_active'], $user_id]);
        }
        log_activity($db, $actor_id, 'USER_UPDATE', $user_id, "Updated user: {$_POST['Username']}");
        break;

    case 'delete':
        $user_id = $_GET['id'];
        // You might want to log user info before deleting
        $stmt_get = $db->prepare("SELECT Username FROM users WHERE id = ?");
        $stmt_get->execute([$user_id]);
        $user = $stmt_get->fetch(PDO::FETCH_ASSOC);

        $stmt_del = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->execute([$user_id]);
        log_activity($db, $actor_id, 'USER_DELETE', $user_id, "Deleted user: {$user['Username']}");
        break;
}

// Redirect back to the user list after any action
header("Location: index.php");
exit();
?>