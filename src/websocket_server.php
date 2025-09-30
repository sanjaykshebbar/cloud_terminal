<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: A WebSocket server using Ratchet to proxy browser terminal
 * commands to a real SSH process for a specific machine.
 * NOTE: This script must be run from the command line: php src/websocket_server.php
 */

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;

// Composer's autoloader makes everything work!
require dirname(__DIR__) . '/vendor/autoload.php';
// We need our database functions
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
        echo "New connection! ({$conn->resourceId})\n";
        
        // Get the machine ID from the WebSocket URL, e.g., ws://.../?machineId=5
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);
        $machineId = $queryParams['machineId'] ?? null;

        if (!$machineId) {
            $conn->send("ERROR: No machine ID specified.\n");
            $conn->close();
            return;
        }

        // Fetch machine details from the database
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM machines WHERE id = ? AND Protocol = 'SSH'");
        $stmt->execute([$machineId]);
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$machine) {
            $conn->send("ERROR: SSH machine with ID {$machineId} not found.\n");
            $conn->close();
            return;
        }

        // IMPORTANT: In a real app, you would fetch user-specific SSH credentials.
        // For now, this example assumes key-based auth is set up on the remote machine
        // for the user running the PHP script, or it will prompt for a password.
        // You must replace 'YOUR_REMOTE_USER' with a valid username.
        $ssh_command = 'ssh -o StrictHostKeyChecking=no YOUR_REMOTE_USER@' . $machine['IPAddress'];
        
        $descriptorspec = [
           0 => ["pipe", "r"],  // stdin for the process
           1 => ["pipe", "w"],  // stdout for the process
           2 => ["pipe", "w"]   // stderr for the process
        ];
        
        $this->processes[$conn->resourceId] = proc_open($ssh_command, $descriptorspec, $this->pipes[$conn->resourceId]);
        
        if (is_resource($this->processes[$conn->resourceId])) {
            stream_set_blocking($this->pipes[$conn->resourceId][1], 0);
            stream_set_blocking($this->pipes[$conn->resourceId][2], 0);
        }

        // Periodically check the SSH process for new output and send it to the browser
        Loop::addPeriodicTimer(0.01, function() use ($conn) {
            if (isset($this->pipes[$conn->resourceId])) {
                $stdout = stream_get_contents($this->pipes[$conn->resourceId][1]);
                $stderr = stream_get_contents($this->pipes[$conn->resourceId][2]);
                if (!empty($stdout)) $conn->send($stdout);
                if (!empty($stderr)) $conn->send($stderr);
            }
        });
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // When the browser sends a keystroke, write it to the SSH process
        if (is_resource($this->processes[$from->resourceId])) {
            fwrite($this->pipes[$from->resourceId][0], $msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        // Clean up the process and pipes
        if (isset($this->pipes[$conn->resourceId])) {
            fclose($this->pipes[$conn->resourceId][0]);
            fclose($this->pipes[$conn->resourceId][1]);
            fclose($this->pipes[$conn->resourceId][2]);
            proc_close($this->processes[$conn->resourceId]);
        }
        unset($this->processes[$conn->resourceId], $this->pipes[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Run the server on port 8080
$server = IoServer::factory(new HttpServer(new WsServer(new TerminalProxy())), 8080);
echo "WebSocket Terminal Server running on port 8080\n";
$server->run();