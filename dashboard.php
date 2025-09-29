<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: The main user dashboard, displayed after a successful login.
 * It provides the primary interface for users to interact with the application.
 * This page is protected and requires an active session.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation of the dashboard layout with a
 * responsive navbar and main content area using Tailwind CSS. Includes
 * session protection to redirect unauthenticated users.
 */

// Start the session to access session variables
session_start();

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get the username from the session for display, use htmlspecialchars for security
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cloud Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans">
    <div id="app" class="min-h-screen flex flex-col">
        <nav class="bg-gray-800 shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex-shrink-0">
                        <a href="dashboard.php" class="text-2xl font-bold text-indigo-400">🛰️ Cloud Terminal</a>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-4 text-gray-300">Welcome, <strong class="font-medium"><?php echo $username; ?></strong></span>
                        <a href="src/logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-grow p-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold mb-6">Dashboard</h1>
                
                <div class="bg-gray-800 p-6 rounded-lg shadow-inner">
                    <h2 class="text-xl font-semibold mb-4">Your Machines</h2>
                    <p class="text-gray-400">
                        Your assigned machines and control buttons will appear here based on your user type.
                    </p>
                    </div>
            </div>
        </main>

        <footer class="bg-gray-800 text-center p-4 text-sm text-gray-500">
            Cloud Terminal &copy; <?php echo date('Y'); ?>. All Rights Reserved.
        </footer>
    </div>
</body>
</html>