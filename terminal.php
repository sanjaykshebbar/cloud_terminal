<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: Hosts the in-browser terminal for SSH connections using Xterm.js
 */
require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$machine_id = $_GET['id'] ?? null;
if (!$machine_id) {
    die("Error: No machine ID was provided.");
}

// Optional: A final check to ensure the logged-in user has permission for this machine.
$db = get_db_connection();
$stmt = $db->prepare("SELECT machine_id FROM user_machine_permissions WHERE user_id = ? AND machine_id = ?");
$stmt->execute([$current_user['id'], $machine_id]);
if ($stmt->fetch() === false && $current_user['UserType'] !== 'Admin') {
     die("Access Denied: You do not have permission to connect to this machine.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-T">
    <title>SSH Terminal</title>
    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css" />
    <style>
        body, html { margin: 0; padding: 0; height: 100%; background-color: #1e1e1e; }
        #terminal { width: 100%; height: 100%; }
    </style>
</head>
<body>
    <div id="terminal"></div>

    <script>
        const term = new Terminal({
            cursorBlink: true,
            fontSize: 14,
            fontFamily: 'Menlo, Monaco, "Courier New", monospace',
            theme: {
                background: '#1e1e1e',
                foreground: '#d4d4d4',
                cursor: '#d4d4d4'
            }
        });
        term.open(document.getElementById('terminal'));

        // Get the machine ID from the URL to pass to the WebSocket server
        const machineId = <?= json_encode($machine_id) ?>;
        
        // Connect to the WebSocket server, passing the machineId as a query parameter
        const socket = new WebSocket(`ws://localhost:8080?machineId=${machineId}`);

        // When the WebSocket connection is established
        socket.onopen = () => {
            term.write('Welcome to your Cloud Terminal!\r\n');
            term.write('Connecting to remote machine...\r\n');
        };

        // When the browser receives a message from the server (SSH output)
        socket.onmessage = (event) => {
            term.write(event.data);
        };

        // When the user types something in the terminal, send it to the server
        term.onData(data => {
            socket.send(data);
        });

        // When the connection is closed
        socket.onclose = (event) => {
            term.write(`\r\n\n--- CONNECTION CLOSED (Code: ${event.code}) ---`);
        };

        socket.onerror = () => {
            term.write('\r\n\n--- CONNECTION ERROR ---');
            term.write('\r\nCould not connect to the WebSocket server. Is it running?');
        }
    </script>
</body>
</html>