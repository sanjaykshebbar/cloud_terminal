<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.1.0
 * Info: Backend handler for machine management, now includes user assignment logic.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-30): Added 'assign_users' action to sync permissions.
 * - v1.0.0: Initial creation with create, update, delete actions.
 */
require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

$actor_id = $current_user['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$db = get_db_connection();

function log_activity($db, $actor_id, $action, $target_id = null, $details = null) {
    $stmt = $db->prepare("INSERT INTO activity_logs (actor_id, action, target_id, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$actor_id, $action, $target_id, $details]);
}

switch ($action) {
    case 'create_machine':
        // ... (this case is unchanged)
        $stmt = $db->prepare("INSERT INTO machines (MachineName, IPAddress, Protocol) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['MachineName'], $_POST['IPAddress'], $_POST['Protocol']]);
        $new_machine_id = $db->lastInsertId();
        log_activity($db, $actor_id, 'MACHINE_CREATE', $new_machine_id, "Created machine: {$_POST['MachineName']}");
        break;

    case 'update_machine':
        // ... (this case is unchanged)
        $machine_id = $_POST['machine_id'];
        $stmt = $db->prepare("UPDATE machines SET MachineName=?, IPAddress=?, Protocol=? WHERE id=?");
        $stmt->execute([$_POST['MachineName'], $_POST['IPAddress'], $_POST['Protocol'], $machine_id]);
        log_activity($db, $actor_id, 'MACHINE_UPDATE', $machine_id, "Updated machine: {$_POST['MachineName']}");
        break;

    case 'assign_users': // ✨ NEW LOGIC IS HERE ✨
        $machine_id = $_POST['machine_id'];
        $assigned_user_ids = $_POST['user_ids'] ?? []; // Default to empty array if no boxes are checked

        // 1. Delete all existing permissions for this machine
        $delete_stmt = $db->prepare("DELETE FROM user_machine_permissions WHERE machine_id = ?");
        $delete_stmt->execute([$machine_id]);

        // 2. Insert the new permissions based on checked boxes
        $insert_stmt = $db->prepare("INSERT INTO user_machine_permissions (user_id, machine_id) VALUES (?, ?)");
        foreach ($assigned_user_ids as $user_id) {
            $insert_stmt->execute([$user_id, $machine_id]);
        }
        
        log_activity($db, $actor_id, 'MACHINE_PERMISSIONS_UPDATE', $machine_id, "Updated user assignments for machine ID {$machine_id}.");
        break;

    case 'delete':
        // ... (this case is unchanged)
        $machine_id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM machines WHERE id = ?");
        $stmt->execute([$machine_id]);
        log_activity($db, $actor_id, 'MACHINE_DELETE', $machine_id, "Deleted machine with ID: {$machine_id}");
        break;
}

header("Location: index.php");
exit();
?>