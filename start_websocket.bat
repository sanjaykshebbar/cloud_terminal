@echo off
TITLE Cloud Terminal WebSocket Server

ECHO Starting the WebSocket server...
ECHO Press Ctrl+C to stop the server.
ECHO ---------------------------------------

:: IMPORTANT: Change this path to your specific XAMPP PHP location
C:\xampp\php\php.exe C:\xampp\htdocs\cloud_terminal\src\websocket_server.php

ECHO Server has been stopped.
pause