<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.2.0
 * Info: WebSocket server with an interactive login prompt for SSH connections.
 * ---------------------------------------------
 * Changelog:
 * - v1.2.0 (2025-10-01): Re-architected to be a state machine that prompts for
 * username and password before initiating the SSH connection using sshpass.
 * - v1.1.0: Added '-t' flag to force PTY allocation.
 * - v1.0.0: Initial creation of the WebSocket server.
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
    // Define states for our connection state machine
    const STATE_AWAITING_USERNAME = 0;
    const STATE_AWAITING_PASSWORD = 1;
    const STATE_CONNECTED = 2;

    protected $clients;
    protected $states;
    protected $authData;
    protected $processes;
    protected $pipes;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->states = new \SplObjectStorage;
        $this->authData = new \SplObjectStorage;
        $this->processes = new \SplObjectStorage;
        $this->pipes = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->states->attach($conn, self::STATE_AWAITING_USERNAME);
        $this->authData->attach($conn, ['machine' => null, 'username' => null]);
        
        // 1. Get Machine ID from URL and verify it
        $queryParams = [];
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);
        $machineId = $queryParams['machineId'] ?? null;

        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM machines WHERE id = ? AND Protocol = 'SSH'");
        $stmt->execute([$machineId]);
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$machine) {
            $conn->send("ERROR: Invalid Machine ID.\r\n");
            $conn->close();
            return;
        }
        
        // Store the machine for later use
        $this->authData[$conn]['machine'] = $machine;
        
        // 2. Prompt for username
        $conn->send("Connected to Gateway. Please provide credentials for " . $machine['IPAddress'] . "\r\n");
        $conn->send("Username: ");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $state = $this->states[$from];
        $authData = $this->authData[$from];

        // Sanitize the input a bit
        $input = trim(preg_replace('/[[:cntrl:]]/', '', $msg));

        switch ($state) {
            case self::STATE_AWAITING_USERNAME:
                // 3. Received username, now ask for password
                $authData['username'] = $input;
                $this->authData[$from] = $authData;
                $from->send("\r\nPassword: ");
                $this->states[$from] = self::STATE_AWAITING_PASSWORD;
                break;

            case self::STATE_AWAITING_PASSWORD:
                // 4. Received password, now attempt SSH connection
                $from->send("\r\n\r\nAuthenticating...\r\n");
                $password = $input;
                $username = $authData['username'];
                $ipAddress = $authData['machine']['IPAddress'];

                // Use sshpass to provide the password to the SSH command
                // Note: The password will be visible in the process list on the server.
                $ssh_command = sprintf(
                    'sshpass -p %s ssh -t -o StrictHostKeyChecking=no %s@%s',
                    escapeshellarg($password),
                    escapeshellarg($username),
                    escapeshellarg($ipAddress)
                );

                $descriptorspec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
                $process = proc_open($ssh_command, $descriptorspec, $pipes);

                if (is_resource($process)) {
                    $this->processes[$from] = $process;
                    $this->pipes[$from] = $pipes;
                    $this->states[$from] = self::STATE_CONNECTED;
                    stream_set_blocking($pipes[1], 0);
                    stream_set_blocking($pipes[2], 0);

                    Loop::addPeriodicTimer(0.01, function() use ($from, $pipes) {
                         if (is_resource($pipes[1])) {
                            $stdout = stream_get_contents($pipes[1]);
                            if (!empty($stdout)) $from->send($stdout);
                        }
                         if (is_resource($pipes[2])) {
                            $stderr = stream_get_contents($pipes[2]);
                            if (!empty($stderr)) $from->send($stderr);
                        }
                    });

                } else {
                    $from->send("Authentication failed or connection error.\r\n");
                    $from->close();
                }
                break;
            
            case self::STATE_CONNECTED:
                // 5. Already connected, just forward the data
                fwrite($this->pipes[$from][0], $msg);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Connection {$conn->resourceId} has disconnected\n";
        if (isset($this->processes[$conn])) {
            $pipes = $this->pipes[$conn];
            if(is_resource($pipes[0])) fclose($pipes[0]);
            if(is_resource($pipes[1])) fclose($pipes[1]);
            if(is_resource($pipes[2])) fclose($pipes[2]);
            proc_close($this->processes[$conn]);
        }
        // Clean up all associated data
        $this->clients->detach($conn);
        $this->states->detach($conn);
        $this->authData->detach($conn);
        $this->processes->detach($conn);
        $this->pipes->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Run the server
$server = IoServer::factory(new HttpServer(new WsServer(new TerminalProxy())), 8080);
echo "WebSocket Terminal Server running on port 8080\n";
$server->run();