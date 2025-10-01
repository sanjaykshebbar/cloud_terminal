<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.1.0
 * Info: WebSocket server. Updated to force PTY allocation for a true interactive shell.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-30): Added the '-t' flag to the ssh command to fix the
 * "Pseudo-terminal will not be allocated" warning and enable an interactive session.
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
    // ... (The rest of the class is unchanged) ...

    public function onOpen(ConnectionInterface $conn) {
        // --- (Code at the top of onOpen is unchanged) ---
        
        // ✨ THE FIX IS HERE: Added the "-t" flag to the command ✨
        $ssh_command = 'ssh -t -o StrictHostKeyChecking=no YOUR_REMOTE_USER@' . $machine['IPAddress'];
        
        // --- (The rest of the function is unchanged) ---
        $descriptorspec = [
           0 => ["pipe", "r"],
           1 => ["pipe", "w"],
           2 => ["pipe", "w"]
        ];
        
        $this->processes[$conn->resourceId] = proc_open($ssh_command, $descriptorspec, $this->pipes[$conn->resourceId]);

        // ... (The rest of the class is unchanged) ...
    }
    
    // ... onMessage, onClose, and onError methods are unchanged ...
}

// ... (Code to run the server is unchanged) ...
$server = IoServer::factory(new HttpServer(new WsServer(new TerminalProxy())), 8080);
echo "WebSocket Terminal Server running on port 8080\n";
$server->run();