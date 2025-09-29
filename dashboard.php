<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.1.0
 * Info: The main user dashboard. Now includes a conditional panel for
 * administrator-specific controls.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-29): Added an Admin Controls section with buttons for
 * User, Group, and Machine management. Visibility is restricted to Admins.
 * - v1.0.0: Initial creation of the dashboard layout.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
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
                        <a href="src/logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-grow p-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

                <?php if ($user_type === 'Admin'): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-400">Admin Controls</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="user/index.php" class="bg-indigo-600 hover:bg-indigo-700 p-6 rounded-lg flex items-center space-x-4 transition">
                            <span class="text-4xl">👤</span>
                            <div>
                                <h3 class="font-bold text-lg">User Management</h3>
                                <p class="text-sm text-indigo-200">Create, edit, and manage users.</p>
                            </div>
                        </a>
                        <a href="#" class="bg-gray-700 p-6 rounded-lg flex items-center space-x-4 cursor-not-allowed opacity-50">
                            <span class="text-4xl">👥</span>
                            <div>
                                <h3 class="font-bold text-lg">Group Management</h3>
                                <p class="text-sm text-gray-400">Coming soon.</p>
                            </div>
                        </a>
                        <a href="machine/index.php" class="bg-green-600 hover:bg-green-700 p-6 rounded-lg flex items-center space-x-4 transition">
                            <span class="text-4xl">💻</span>
                            <div>
                                <h3 class="font-bold text-lg">Machine Management</h3>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-gray-800 p-6 rounded-lg shadow-inner">
                    <h2 class="text-xl font-semibold mb-4">Your Machines</h2>
                    <p class="text-gray-400">
                        Your assigned machines will appear here.
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