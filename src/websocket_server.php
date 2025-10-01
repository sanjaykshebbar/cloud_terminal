<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 3.3.0
 * Info: Explicitly defines the private SSH key path for the connection.
 * ---------------------------------------------
 * Changelog:
 * - v3.3.0 (2025-10-01): Added the '-i' flag to the ssh command to specify the
 * exact private key file, fixing authentication issues in environments
 * like XAMPP where PHP runs as a different user.
 * - v3.2.0: Added verbose logging to debug connection hangs.
 * - v3.0.0: Refactored to use token-based authentication.
 */

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/db.php.bak';

class TerminalProxy implements MessageComponentInterface {
    protected $clients;
    protected $processes = [];
    protected $pipes = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "----------------------------------------\n";
        echo "New connection! ({$conn->resourceId})\n";
        
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);
        $token = $queryParams['token'] ?? null;
        $machineId = $queryParams['machineId'] ?? null;
        
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM websocket_tokens WHERE token = ? AND expires_at > datetime('now')");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            echo "!!! AUTH FAILED: Invalid or expired token. Closing connection.\n";
            $conn->send("ERROR: Authentication failed (Invalid or expired token).\r\n");
            $conn->close();
            return;
        }
        
        echo "Token validated for user ID: {$tokenData['user_id']}\n";
        $db->prepare("DELETE FROM websocket_tokens WHERE token = ?")->execute([$token]);
        
        $userId = $tokenData['user_id'];

        $machineStmt = $db->prepare("SELECT IPAddress FROM machines WHERE id = ?");
        $machineStmt->execute([$machineId]);
        $machine = $machineStmt->fetch(PDO::FETCH_ASSOC);

        $userStmt = $db->prepare("SELECT Username FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$machine || !$user) {
            echo "!!! ERROR: Could not find user or machine in database.\n";
            $conn->send("ERROR: Invalid user or machine data.\n");
            $conn->close();
            return;
        }
        
        $username = $user['Username'];
        $ipAddress = $machine['IPAddress'];
        echo "Attempting to connect as '{$username}' to '{$ipAddress}'\n";
        
        // IMPORTANT: Update this path to your exact private key location on the XAMPP server machine
        $privateKeyPath = 'C:/Users/Sanjay KS/.ssh/id_rsa';

        $ssh_command = sprintf(
            'ssh -t -o StrictHostKeyChecking=no -i %s %s@%s',
            escapeshellarg($privateKeyPath),
            escapeshellarg($username),
            escapeshellarg($ipAddress)
        );
        echo "Executing command: {$ssh_command}\n";
        
        $descriptorspec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $this->processes[$conn->resourceId] = proc_open($ssh_command, $descriptorspec, $this->pipes[$conn->resourceId]);
        
        if (is_resource($this->processes[$conn->resourceId])) {
            echo "SSH process started successfully. Waiting for output...\n";
            $pipes = $this->pipes[$conn->resourceId];
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            Loop::addPeriodicTimer(0.01, function() use ($conn, $pipes) {
                if (isset($pipes[1]) && is_resource($pipes[1])) { $stdout = stream_get_contents($pipes[1]); if (!empty($stdout)) $conn->send($stdout); }
                if (isset($pipes[2]) && is_resource($pipes[2])) { $stderr = stream_get_contents($pipes[2]); if (!empty($stderr)) $conn->send($stderr); }
            });
        } else {
             echo "!!! FATAL: proc_open() failed to start the SSH process.\n";
             $conn->send("ERROR: Failed to start SSH process on the server.\r\n");
             $conn->close();
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        if (isset($this->processes[$from->resourceId]) && is_resource($this->processes[$from->resourceId])) {
            fwrite($this->pipes[$from->resourceId][0], $msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Connection {$conn->resourceId} has disconnected\n";
        if (isset($this->processes[$conn->resourceId])) {
            $pipes = $this->pipes[$conn->resourceId];
            if(is_resource($pipes[0])) fclose($pipes[0]);
            if(is_resource($pipes[1])) fclose($pipes[1]);
            if(is_resource($pipes[2])) fclose($pipes[2]);
            proc_close($this->processes[$conn->resourceId]);
        }
        $this->clients->detach($conn);
        unset($this->processes[$conn->resourceId], $this->pipes[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(new HttpServer(new WsServer(new TerminalProxy())), 8080);
echo "Passwordless WebSocket Terminal Server running on port 8080\n";
$server->run();