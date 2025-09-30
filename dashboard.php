<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.5.0
 * Info: The main user dashboard. It displays accessible machines based on
 * the user's role (Admins see all, others see assigned) and provides
 * functional "Connect" buttons for SSH and RDP protocols.
 * ---------------------------------------------
 * Changelog:
 * - v1.5.0 (2025-09-30): Updated the Connect button to link to the correct
 * handler (terminal.php for SSH, rdp_handler.php for RDP).
 * - v1.4.0: Implemented conditional logic to grant Admins access to all machines.
 * - v1.3.0: Added DB query and display logic for user-specific machines.
 * - v1.2.0: Integrated the centralized session validator.
 * - v1.1.0: Added the Admin Controls section.
 * - v1.0.0: Initial creation of the dashboard layout.
 */

// 1. Centralized session validation to ensure data is always current
require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

// 2. Get freshly validated user data
$username = htmlspecialchars($current_user['Username']);
$user_type = $current_user['UserType'];

// 3. Conditional logic to fetch machines based on user type
$db = get_db_connection();
$assigned_machines = [];

if ($user_type === 'Admin') {
    // If user is an Admin, get ALL machines
    $assigned_machines = $db->query("SELECT * FROM machines ORDER BY MachineName")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // For all other users, get only their explicitly assigned machines
    $machines_stmt = $db->prepare(
        "SELECT m.* FROM machines m 
         JOIN user_machine_permissions p ON m.id = p.machine_id
         WHERE p.user_id = ?"
    );
    $machines_stmt->execute([$current_user['id']]);
    $assigned_machines = $machines_stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
                    <div class="flex-shrink-0"><a href="dashboard.php" class="text-2xl font-bold text-indigo-400">🛰️ Cloud Terminal</a></div>
                    <div class="flex items-center">
                        <span class="mr-4 text-gray-300">Welcome, <strong class="font-medium"><?php echo $username; ?></strong></span>
                        <a href="src/logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Logout</a>
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
                        <a href="user/index.php" class="bg-indigo-600 hover:bg-indigo-700 p-6 rounded-lg flex items-center space-x-4 transition"><span class="text-4xl">👤</span><div><h3 class="font-bold text-lg">User Management</h3><p class="text-sm text-indigo-200">Create, edit, and manage users.</p></div></a>
                        <a href="#" class="bg-gray-700 p-6 rounded-lg flex items-center space-x-4 cursor-not-allowed opacity-50"><span class="text-4xl">👥</span><div><h3 class="font-bold text-lg">Group Management</h3><p class="text-sm text-gray-400">Coming soon.</p></div></a>
                        <a href="machine/index.php" class="bg-green-600 hover:bg-green-700 p-6 rounded-lg flex items-center space-x-4 transition"><span class="text-4xl">💻</span><div><h3 class="font-bold text-lg">Machine Management</h3><p class="text-sm text-green-200">Add, edit, and manage machines.</p></div></a>
                    </div>
                </div>
                <?php endif; ?>

                <div>
                    <h2 class="text-xl font-semibold mb-4">Your Accessible Machines</h2>
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
                                    <?php
                                        $connect_link = '#';
                                        if ($machine['Protocol'] == 'SSH') {
                                            $connect_link = "terminal.php?id=" . $machine['id'];
                                        } elseif ($machine['Protocol'] == 'RDP') {
                                            $connect_link = "rdp_handler.php?id=" . $machine['id'];
                                        }
                                    ?>
                                    <a href="<?= $connect_link ?>" 
                                       target="<?= $machine['Protocol'] == 'SSH' ? '_blank' : '_self' ?>"
                                       class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
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

        <footer class="bg-gray-800 text-center p-4 text-sm text-gray-500">
            Cloud Terminal &copy; <?php echo date('Y'); ?>. All Rights Reserved.
        </footer>
    </div>
</body>
</html>