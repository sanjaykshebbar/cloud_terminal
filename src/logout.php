<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: Handles the user logout process. It securely destroys the session
 * and redirects the user to the login page.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation of the logout script.
 */

// Always start the session first
session_start();

// Unset all of the session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: ../index.php");
exit(); // Ensure no further code is executed after redirect
?>