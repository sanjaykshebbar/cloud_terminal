<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.7.0
 * Info: Dashboard with Connect buttons updated for token-based auth.
 * ---------------------------------------------
 * Changelog:
 * - v1.7.0 (2025-10-01): Updated SSH Connect button to point to the new
 * 'generate_token.php' script to initiate the secure token auth flow.
 * - v1.6.0: Added a "My Profile" link to the navigation bar.
 */

require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$username = htmlspecialchars($current_user['Username']);
$user_type = $current_user['UserType'];

$db = get_db_connection();
$assigned_machines = [];

if ($user_type === 'Admin') {
    $assigned_machines = $db->query("SELECT * FROM machines ORDER BY MachineName")->fetchAll(PDO::FETCH_ASSOC);
} else {
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
    <title>Dashboard - Cloud Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans">
    <div id="app" class="min-h-screen flex flex-col">
        <nav class="bg-gray-800 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex-shrink-0"><a href="dashboard.php" class="text-2xl font-bold text-indigo-400">🛰️ Cloud Terminal</a></div>
                    <div class="flex items-center">
                        <span class="text-gray-300 hidden sm:inline">Welcome, <strong class="font-medium"><?php echo $username; ?></strong></span>
                        <a href="profile.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium ml-4">My Profile</a>
                        <a href="src/logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg ml-2">Logout</a>
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
                        <a href="user/index.php" class="bg-indigo-600 hover:bg-indigo-700 p-6 rounded-lg flex items-center space-x-4"><span class="text-4xl">👤</span><div><h3 class="font-bold text-lg">User Management</h3><p class="text-sm text-indigo-200">Manage users.</p></div></a>
                        <a href="#" class="bg-gray-700 p-6 rounded-lg flex items-center space-x-4 cursor-not-allowed opacity-50"><span class="text-4xl">👥</span><div><h3 class="font-bold text-lg">Group Management</h3><p class="text-sm text-gray-400">Coming soon.</p></div></a>
                        <a href="machine/index.php" class="bg-green-600 hover:bg-green-700 p-6 rounded-lg flex items-center space-x-4"><span class="text-4xl">💻</span><div><h3 class="font-bold text-lg">Machine Management</h3><p class="text-sm text-green-200">Manage machines.</p></div></a>
                    </div>
                </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-semibold mb-4">Your Accessible Machines</h2>
                    <?php if (empty($assigned_machines)): ?>
                        <div class="bg-gray-800 p-6 rounded-lg shadow-inner text-center text-gray-400">You have not been assigned any machines yet.</div>
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
                                <div class="mt-auto pt-4">
                                    <?php
                                        $connect_link = '#';
                                        if ($machine['Protocol'] == 'SSH') {
                                            $connect_link = "generate_token.php?id=" . $machine['id'];
                                        } elseif ($machine['Protocol'] == 'RDP') {
                                            $connect_link = "rdp_handler.php?id=" . $machine['id'];
                                        }
                                    ?>
                                    <a href="<?= $connect_link ?>" 
                                       target="<?= $machine['Protocol'] == 'SSH' ? '_blank' : '_self' ?>"
                                       class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Connect</a>
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