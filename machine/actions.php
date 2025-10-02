<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 2.4.1
 * Info: A complete and clean version of the machine actions handler.
 * ---------------------------------------------
 * Changelog:
 * - v2.4.1 (2025-10-02): Moved the 'use' statement for phpseclib to the top
 * level of the script to resolve a "name is already in use" fatal error.
 * - v2.4.0: Restored all CRUD actions and cleaned up provisioning logic.
 */

require_once '../src/session_check.php';
$current_user = validate_active_session();

// Security Check: Only Admins can perform these actions
if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden: You do not have permission to perform this action.');
}

// ✨ --- THIS IS THE FIX --- ✨
// All 'use' statements MUST be at the top level of the script.
require_once '../vendor/autoload.php';
use phpseclib3\Net\SSH2;

$actor_id = $current_user['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$db = get_db_connection();

/**
 * Logs an activity to the database.
 */
function log_activity($db, $actor_id, $action, $target_id = null, $details = null) {
    $stmt = $db->prepare("INSERT INTO activity_logs (actor_id, action, target_id, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$actor_id, $action, $target_id, $details]);
}

switch ($action) {
    case 'create_machine':
        $stmt = $db->prepare("INSERT INTO machines (MachineName, IPAddress, Protocol) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['MachineName'], $_POST['IPAddress'], $_POST['Protocol']]);
        $new_machine_id = $db->lastInsertId();
        log_activity($db, $actor_id, 'MACHINE_CREATE', $new_machine_id, "Created machine: {$_POST['MachineName']}");
        header("Location: ../machine/index.php?status=created");
        exit();
        break;

    case 'update_machine':
        $machine_id = $_POST['machine_id'];
        $stmt = $db->prepare("UPDATE machines SET MachineName=?, IPAddress=?, Protocol=? WHERE id=?");
        $stmt->execute([$_POST['MachineName'], $_POST['IPAddress'], $_POST['Protocol'], $machine_id]);
        log_activity($db, $actor_id, 'MACHINE_UPDATE', $machine_id, "Updated machine: {$_POST['MachineName']}");
        header("Location: ../machine/index.php?status=updated");
        exit();
        break;

    case 'provision_users':
        $machine_id = $_POST['machine_id'];
        $user_ids_to_assign = $_POST['user_ids'] ?? [];
        $sudo_user = $_POST['sudo_user'];
        $sudo_pass = $_POST['sudo_pass'];

        $machine_stmt = $db->prepare("SELECT IPAddress FROM machines WHERE id = ?");
        $machine_stmt->execute([$machine_id]);
        $machine = $machine_stmt->fetch(PDO::FETCH_ASSOC);

        $users_to_provision = [];
        if (!empty($user_ids_to_assign)) {
            $user_placeholders = rtrim(str_repeat('?,', count($user_ids_to_assign)), ',');
            $users_stmt = $db->prepare("SELECT id, Username FROM users WHERE id IN ($user_placeholders)");
            $users_stmt->execute($user_ids_to_assign);
            $users_to_provision = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $ssh = new SSH2($machine['IPAddress']);
        if (!$ssh->login($sudo_user, $sudo_pass)) {
            header("Location: ../machine/assign.php?id={$machine_id}&error=auth_failed");
            exit();
        }

        $delete_stmt = $db->prepare("DELETE FROM user_machine_permissions WHERE machine_id = ?");
        $delete_stmt->execute([$machine_id]);
        $insert_stmt = $db->prepare("INSERT INTO user_machine_permissions (user_id, machine_id) VALUES (?, ?)");
        $keys_stmt = $db->prepare("SELECT public_key FROM user_ssh_keys WHERE user_id = ?");

        foreach ($users_to_provision as $user) {
            $username = $user['Username'];
            $command = "sh -c 'id -u " . escapeshellarg($username) . " &>/dev/null || useradd -m -s /bin/bash " . escapeshellarg($username) . "'";
            $full_command = "echo " . escapeshellarg($sudo_pass) . " | sudo -S -p '' " . $command;
            $ssh->exec($full_command);
            
            $keys_stmt->execute([$user['id']]);
            $public_keys = $keys_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            if (!empty($public_keys)) {
                $all_keys_string = implode("\n", $public_keys);
                $commands = [
                    "mkdir -p /home/$username/.ssh",
                    "sh -c 'echo " . escapeshellarg($all_keys_string) . " > /home/$username/.ssh/authorized_keys'",
                    "chmod 700 /home/$username/.ssh",
                    "chmod 600 /home/$username/.ssh/authorized_keys",
                    "chown -R $username:$username /home/$username/.ssh"
                ];
                foreach ($commands as $cmd) {
                    $ssh->exec("echo " . escapeshellarg($sudo_pass) . " | sudo -S -p '' " . $cmd);
                }
            }
            $insert_stmt->execute([$user['id'], $machine_id]);
        }
        
        log_activity($db, $actor_id, 'MACHINE_PROVISION_USERS', $machine_id, "Provisioned " . count($users_to_provision) . " users.");
        header("Location: ../machine/index.php?status=provisioned");
        exit();
        break;

    case 'delete':
        $machine_id = $_GET['id'];
        $stmt_get = $db->prepare("SELECT MachineName FROM machines WHERE id = ?");
        $stmt_get->execute([$machine_id]);
        $machine = $stmt_get->fetch(PDO::FETCH_ASSOC);

        $stmt_del = $db->prepare("DELETE FROM machines WHERE id = ?");
        $stmt_del->execute([$machine_id]);
        log_activity($db, $actor_id, 'MACHINE_DELETE', $machine_id, "Deleted machine: {$machine['MachineName']}");
        header("Location: ../machine/index.php?status=deleted");
        exit();
        break;
}

// Default redirect if no action is matched
header("Location: ../machine/index.php");
exit();
?>