<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 2.0.0
 * Info: Secure backend handler for all machine management actions. Includes
 * the advanced 'provision_users' feature to automate remote user setup
 * and SSH key installation using phpseclib.
 * ---------------------------------------------
 * Changelog:
 * - v2.0.0 (2025-10-01): Replaced 'assign_users' with a comprehensive 'provision_users'
 * action. This now uses phpseclib to connect to the remote host with sudo
 * credentials, create user accounts, and install their public SSH keys
 * for passwordless access. This removes the dependency on 'sshpass'.
 * - v1.1.0: Added initial user assignment logic.
 * - v1.0.0: Initial creation with basic CRUD functions and logging.
 */

// Centralized session validation to ensure user is an admin
require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden: You do not have permission to perform this action.');
}

// Composer's autoloader for phpseclib
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
        break;

    case 'update_machine':
        $machine_id = $_POST['machine_id'];
        $stmt = $db->prepare("UPDATE machines SET MachineName=?, IPAddress=?, Protocol=? WHERE id=?");
        $stmt->execute([$_POST['MachineName'], $_POST['IPAddress'], $_POST['Protocol'], $machine_id]);
        log_activity($db, $actor_id, 'MACHINE_UPDATE', $machine_id, "Updated machine: {$_POST['MachineName']}");
        break;

    case 'provision_users':
        $machine_id = $_POST['machine_id'];
        $user_ids_to_assign = $_POST['user_ids'] ?? [];
        
        $sudo_user = $_POST['sudo_user'];
        $sudo_pass = $_POST['sudo_pass'];

        // 1. Get machine and user details from our local database
        $machine_stmt = $db->prepare("SELECT IPAddress FROM machines WHERE id = ?");
        $machine_stmt->execute([$machine_id]);
        $machine = $machine_stmt->fetch(PDO::FETCH_ASSOC);

        $users_to_provision = [];
        if (!empty($user_ids_to_assign)) {
            $user_placeholders = rtrim(str_repeat('?,', count($user_ids_to_assign)), ',');
            $users_stmt = $db->prepare("SELECT id, Username, ssh_public_key FROM users WHERE id IN ($user_placeholders)");
            $users_stmt->execute($user_ids_to_assign);
            $users_to_provision = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 2. Connect to the remote machine using the provided sudo credentials
        $ssh = new SSH2($machine['IPAddress']);
        if (!$ssh->login($sudo_user, $sudo_pass)) {
            // In a real app, you'd redirect with a proper error message
            die('PROVISIONING FAILED: Could not log in to the remote machine. Please check the sudo username and password.');
        }

        // 3. Sync permissions in our local Cloud Terminal database
        // First, remove all old permissions for this machine
        $delete_stmt = $db->prepare("DELETE FROM user_machine_permissions WHERE machine_id = ?");
        $delete_stmt->execute([$machine_id]);
        
        // Then, prepare to insert the new ones
        $insert_stmt = $db->prepare("INSERT INTO user_machine_permissions (user_id, machine_id) VALUES (?, ?)");

        // 4. Loop through each selected user and provision them on the remote machine
        foreach ($users_to_provision as $user) {
            $username = $user['Username'];
            $pub_key = $user['ssh_public_key'];

            // A. Create the user on the remote machine (if they don't already exist)
            // The `||` ensures useradd only runs if the first command fails (i.e., user not found)
            $ssh->exec("id -u $username || sudo useradd -m -s /bin/bash $username");
            
            // B. If the user has a public key, install it for passwordless access
            if (!empty($pub_key)) {
                $ssh->exec("sudo mkdir -p /home/$username/.ssh");
                $ssh->exec("echo " . escapeshellarg($pub_key) . " | sudo tee /home/$username/.ssh/authorized_keys");
                $ssh->exec("sudo chmod 700 /home/$username/.ssh");
                $ssh->exec("sudo chmod 600 /home/$username/.ssh/authorized_keys");
                $ssh->exec("sudo chown -R $username:$username /home/$username/.ssh");
            }
            
            // C. Add the permission to our local database
            $insert_stmt->execute([$user['id'], $machine_id]);
        }
        
        log_activity($db, $actor_id, 'MACHINE_PROVISION_USERS', $machine_id, "Provisioned " . count($users_to_provision) . " users on machine ID {$machine_id}.");
        break;

    case 'delete':
        $machine_id = $_GET['id'];
        $stmt_get = $db->prepare("SELECT MachineName FROM machines WHERE id = ?");
        $stmt_get->execute([$machine_id]);
        $machine = $stmt_get->fetch(PDO::FETCH_ASSOC);

        $stmt_del = $db->prepare("DELETE FROM machines WHERE id = ?");
        $stmt_del->execute([$machine_id]);
        log_activity($db, $actor_id, 'MACHINE_DELETE', $machine_id, "Deleted machine: {$machine['MachineName']}");
        break;
}

// Redirect back to the machine list after any action
header("Location: ../machine/index.php?status=success");
exit();
?>