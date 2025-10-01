<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.5.1
 * Info: Added extensive console logging to debug the connection race condition.
 * ---------------------------------------------
 * Changelog:
 * - v1.5.1 (2025-10-01): Added detailed console.log statements to trace the
 * execution flow of the WebSocket and Xterm.js events.
 * - v1.5.0: Moved 'term.onData' to fix race condition.
 */
require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$machine_id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$machine_id || !$token) {
    die("Error: Missing required machine ID or authentication token.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSH Terminal</title>
    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css" />
    <style> body, html { margin: 0; padding: 0; height: 100%; background-color: #1e1e1e; } #terminal { width: 100%; height: 100%; } </style>
</head>
<body>
    <div id="terminal"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM fully loaded. Starting terminal script...');
            try {
                const term = new Terminal({ cursorBlink: true, fontSize: 14, theme: { background: '#1e1e1e', foreground: '#d4d4d4' } });
                term.open(document.getElementById('terminal'));
                console.log('Xterm.js terminal is open on the page.');

                const urlParams = new URLSearchParams(window.location.search);
                const machineId = urlParams.get('id');
                const token = urlParams.get('token');
                
                if (!token) {
                    throw new Error('Authentication token is missing.');
                }

                const wsUrl = `ws://${window.location.hostname}:8080?machineId=${machineId}&token=${token}`;
                console.log('Creating WebSocket connection to:', wsUrl);
                const socket = new WebSocket(wsUrl);

                socket.onopen = () => {
                    console.log('WebSocket connection has successfully OPENED.');
                    term.write('Welcome to your Cloud Terminal!\r\n');
                    term.write('Connecting to remote machine...\r\n');

                    console.log('Attaching user input listener (term.onData)...');
                    term.onData(data => {
                        console.log('User typed:', data);
                        // Check state before sending
                        if (socket.readyState === WebSocket.OPEN) {
                            socket.send(data);
                        } else {
                            console.error('Attempted to send data while socket was not open. State:', socket.readyState);
                        }
                    });
                };

                socket.onmessage = (event) => {
                    console.log('Received message from server:', event.data);
                    term.write(event.data);
                };

                socket.onclose = (event) => {
                    console.log('WebSocket connection has CLOSED. Code:', event.code);
                    term.write(`\r\n\n--- CONNECTION CLOSED (Code: ${event.code}) ---`);
                };

                socket.onerror = (event) => {
                    console.error('WebSocket ERROR occurred:', event);
                    term.write('\r\n\n--- CONNECTION ERROR ---\r\nCould not connect. Is the WebSocket server running?');
                };
            } catch (e) {
                console.error('A critical JavaScript error occurred:', e);
                document.body.innerHTML = '<div style="color: red; padding: 20px;"><h3>JavaScript Error:</h3><p>' + e.message + '</p></div>';
            }
        });
    </script>
</body>
</html>