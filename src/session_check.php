<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: Provides a centralized function to validate and refresh user
 * sessions on every page load. This ensures that changes to user
 * status or permissions are reflected immediately.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation of the session validation function.
 */

// Ensure the main DB library is available
require_once __DIR__ . '/db.php.bak';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates the active session against the database.
 *
 * @return array|null The current, validated user data or null if invalid.
 */
function validate_active_session() {
    // 1. Check if a user ID exists in the session
    if (!isset($_SESSION['user_id'])) {
        header('Location: /cloud_terminal/index.php'); // Adjust path if needed
        exit();
    }

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Check if user was deleted or is inactive
    if (!$user || $user['is_active'] == 0) {
        // Destroy the stale session
        $_SESSION = [];
        session_destroy();

        $error_type = $user ? 'user_inactive' : 'user_deleted';
        header('Location: /cloud_terminal/index.php?error=' . $error_type); // Adjust path
        exit();
    }

    // 3. SUCCESS: Refresh session data with the latest from the DB
    $_SESSION['username'] = $user['Username'];
    $_SESSION['user_type'] = $user['UserType'];
    
    return $user; // Return fresh user data for the current page
}
?>