<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.3.0
 * Info: User dashboard. Now fetches and displays machines assigned to the user.
 * ---------------------------------------------
 * Changelog:
 * - v1.3.0 (2025-09-30): Added DB query and display logic for user-specific machines.
 * - v1.2.0: Integrated centralized session validation.
 * - v1.1.0: Added the Admin Controls section.
 * - v1.0.0: Initial creation of the dashboard layout.
 */

require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$username = htmlspecialchars($current_user['Username']);
$user_type = $current_user['UserType'];

// ✨ NEW DB QUERY TO GET ASSIGNED MACHINES ✨
$db = get_db_connection();
$machines_stmt = $db->prepare(
    "SELECT m.* FROM machines m 
     JOIN user_machine_permissions p ON m.id = p.machine_id
     WHERE p.user_id = ?"
);
$machines_stmt->execute([$current_user['id']]);
$assigned_machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <!-- Navigation Bar (Unchanged) -->
        <nav class="bg-gray-800 shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex-shrink-0"><a href="dashboard.php" class="text-2xl font-bold text-indigo-400">🛰️ Cloud Terminal</a></div>
                    <div class="flex items-center">
                        <span class="mr-4 text-gray-300">Welcome, <strong class="font-medium"><?php echo $username; ?></strong></span>
                        <a href="src/logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="flex-grow p-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

                <!-- Admin Control Panel (Unchanged) -->
                <?php if ($user_type === 'Admin'): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-400">Admin Controls</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="user/index.php" class="bg-indigo-600 hover:bg-indigo-700 p-6 rounded-lg flex items-center space-x-4 transition">
                            <span class="text-4xl">👤</span><div><h3 class="font-bold text-lg">User Management</h3><p class="text-sm text-indigo-200">Create, edit, and manage users.</p></div>
                        </a>
                        <a href="#" class="bg-gray-700 p-6 rounded-lg flex items-center space-x-4 cursor-not-allowed opacity-50">
                            <span class="text-4xl">👥</span><div><h3 class="font-bold text-lg">Group Management</h3><p class="text-sm text-gray-400">Coming soon.</p></div>
                        </a>
                        <a href="machine/index.php" class="bg-green-600 hover:bg-green-700 p-6 rounded-lg flex items-center space-x-4 transition">
                            <span class="text-4xl">💻</span><div><h3 class="font-bold text-lg">Machine Management</h3><p class="text-sm text-green-200">Add, edit, and manage machines.</p></div>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ✨ UPDATED MACHINE DISPLAY AREA ✨ -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Your Assigned Machines</h2>
                    <?php if (empty($assigned_machines)): ?>
                        <div class="bg-gray-800 p-6 rounded-lg shadow-inner text-center text-gray-400">
                            You have not been assigned any machines yet. Please contact an administrator.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($assigned_machines as $machine): ?>
                            <div class="bg-gray-800 rounded-lg shadow-lg p-6 flex flex-col">
                                <div class="flex items-start mb-4">
                                    <span class="text-5xl mr-4"><?= $machine['Protocol'] == 'SSH' ? '💻' : '🖥️' ?></span>
                                    <div>
                                        <h3 class="font-bold text-xl text-white"><?= htmlspecialchars($machine['MachineName']) ?></h3>
                                        <p class="font-mono text-sm text-gray-400"><?= htmlspecialchars($machine['IPAddress']) ?></p>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <a href="#" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                        Connect
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Footer (Unchanged) -->
        <footer class="bg-gray-800 text-center p-4 text-sm text-gray-500">
            Cloud Terminal &copy; <?php echo date('Y'); ?>. All Rights Reserved.
        </footer>
    </div>
</body>
</html>