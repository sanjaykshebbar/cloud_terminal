<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.1.0
 * Info: Generates a single-use token for WebSocket authentication.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-10-01): Switched to gmdate() to generate expiration time
 * in UTC, preventing timezone-related token validation errors.
 * - v1.0.0: Initial creation.
 */
require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$machine_id = $_GET['id'] ?? null;
if (!$machine_id) {
    die("Error: No machine ID provided.");
}

$token = bin2hex(random_bytes(32));

// ✨ --- THIS IS THE FIX --- ✨
// Use gmdate() to ensure the time is in UTC, matching the database's 'now'.
$expires_at = gmdate('Y-m-d H:i:s', time() + 60); // Token is valid for 60 seconds

$db = get_db_connection();
$stmt = $db->prepare(
    "INSERT INTO websocket_tokens (user_id, machine_id, token, expires_at) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$current_user['id'], $machine_id, $token, $expires_at]);

header("Location: terminal.php?id={$machine_id}&token={$token}");
exit();
?>