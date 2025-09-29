<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.1.0
 * Info: Handles user authentication. Verifies credentials against the
 * database, sets session variables, and redirects to the dashboard.
 * This version includes granular error handling for login failures.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-29): Added specific error handling for 'user not found'
 * and 'user inactive' login attempts.
 * - v1.0.0: Initial creation of the authentication script.
 */

session_start();
require_once __DIR__ . '/db.php.bak'; // Use the renamed .bak file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE Username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1. Check if a user with that username was found
    if (!$user) {
        header('Location: ../index.php?error=user_not_found');
        exit();
    }

    // 2. Check if the user's account is active
    if ($user['is_active'] == 0) {
        header('Location: ../index.php?error=user_inactive');
        exit();
    }
    
    // 3. Verify the password
    if (password_verify($_POST['password'], $user['Password'])) {
        // --- SUCCESS ---
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['user_type'] = $user['UserType'];
        
        // Redirect to the dashboard
        header('Location: ../dashboard.php');
        exit();
    } else {
        // If password verification fails, it's an invalid credentials error
        header('Location: ../index.php?error=invalid_credentials');
        exit();
    }
}
?>