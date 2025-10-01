<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 2.2.0
 * Info: Improved sudo command execution for remote provisioning.
 * ---------------------------------------------
 * Changelog:
 * - v2.2.0 (2025-10-01): Rewrote the remote command execution logic to use
 * 'sudo -S', which reads the password from stdin. This fixes the
 * 'sudo: a password is required' error and makes provisioning reliable.
 * - v2.1.0: Added detailed debugging output to the 'provision_users' action.
 * - v2.0.0: Re-architected to use phpseclib for remote provisioning.
 */

require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once '../vendor/autoload.php';
use phpseclib3\Net\SSH2;

$actor_id = $current_user['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$db = get_db_connection();

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

        // Get machine and user details from DB
        $db = get_db_connection();
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

        echo "<pre style='background: #111827; color: #d1d5db; padding: 20px; font-family: monospace; line-height: 1.6;'>";
        echo "--- Starting Provisioning on " . htmlspecialchars($machine['IPAddress']) . " ---<br>";

        $ssh = new SSH2($machine['IPAddress']);
        if (!$ssh->login($sudo_user, $sudo_pass)) {
            die('PROVISIONING FAILED: Could not log in. Check sudo credentials.');
        }
        echo "Login successful as " . htmlspecialchars($sudo_user) . ".<br><br>";

        // Sync local DB permissions
        $delete_stmt = $db->prepare("DELETE FROM user_machine_permissions WHERE machine_id = ?");
        $delete_stmt->execute([$machine_id]);
        $insert_stmt = $db->prepare("INSERT INTO user_machine_permissions (user_id, machine_id) VALUES (?, ?)");

        // Corrected sudo command runner
        function run_sudo_command($ssh, $command, $password) {
            $full_command = "echo " . escapeshellarg($password) . " | sudo -S -p '' " . $command;
            echo "EXECUTING: <span style='color: #60a5fa;'>sudo $command</span><br>";
            $ssh->exec($full_command);
            $error = $ssh->getStdError();
            if(!empty($error)) {
                echo "<span style='color: #f87171;'>ERROR: " . htmlspecialchars($error) . "</span><br>";
            } else {
                echo "<span style='color: #4ade80;'>SUCCESS.</span><br>";
            }
        }

        $keys_stmt = $db->prepare("SELECT public_key FROM user_ssh_keys WHERE user_id = ?");

        foreach ($users_to_provision as $user) {
            $username = $user['Username'];
            echo "<br>--- Provisioning user: <strong style='color: #c4b5fd;'>" . htmlspecialchars($username) . "</strong> ---<br>";
            
            run_sudo_command($ssh, "sh -c 'id -u " . escapeshellarg($username) . " &>/dev/null || useradd -m -s /bin/bash " . escapeshellarg($username) . "'", $sudo_pass);
            
            $keys_stmt->execute([$user['id']]);
            $public_keys = $keys_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            if (!empty($public_keys)) {
                $all_keys_string = implode("\n", $public_keys);
                run_sudo_command($ssh, "mkdir -p /home/$username/.ssh", $sudo_pass);
                run_sudo_command($ssh, "sh -c 'echo " . escapeshellarg($all_keys_string) . " > /home/$username/.ssh/authorized_keys'", $sudo_pass);
                run_sudo_command($ssh, "chmod 700 /home/$username/.ssh", $sudo_pass);
                run_sudo_command($ssh, "chmod 600 /home/$username/.ssh/authorized_keys", $sudo_pass);
                run_sudo_command($ssh, "chown -R $username:$username /home/$username/.ssh", $sudo_pass);
            } else {
                echo "<span style='color: #fbbf24;'>WARNING: No public keys found for " . htmlspecialchars($username) . ". They will not have passwordless login.</span><br>";
            }
            
            $insert_stmt->execute([$user['id'], $machine_id]);
        }
        
        log_activity($db, $actor_id, 'MACHINE_PROVISION_USERS', $machine_id, "Provisioned " . count($users_to_provision) . " users.");
        echo "<br>--- Provisioning Complete! ---<br>";
        echo "<a href='../machine/index.php' style='color: #818cf8; font-size: 1.2em;'>Return to machine list</a>";
        echo "</pre>";
        
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

header("Location: ../machine/index.php");
exit();
?>