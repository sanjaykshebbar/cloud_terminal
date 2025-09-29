<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: Handles user authentication. Verifies credentials against the
 * database, sets session variables, and redirects to the dashboard.
 * ---------------------------------------------
 */

session_start();
require_once __DIR__ . '/db.php.bak'; // Use the renamed .bak file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE Username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify user exists and password is correct
    if ($user && password_verify($_POST['password'], $user['Password'])) {
        // --- IMPORTANT ---
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['user_type'] = $user['UserType'];
        
        // Redirect to the new dashboard
        header('Location: ../dashboard.php');
        exit();
    } else {
        // Redirect back to login with an error
        header('Location: ../index.php?error=invalid_credentials');
        exit();
    }
}
?>