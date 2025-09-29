<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: Main page for machine management. Lists all machines.
 */
require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

$db = get_db_connection();
$machines = $db->query("SELECT * FROM machines ORDER BY MachineName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Machine Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Machine Management</h1>
            <a href="form.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">+ Add New Machine</a>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-4">Icon</th>
                        <th class="p-4">Friendly Name</th>
                        <th class="p-4">IP Address</th>
                        <th class="p-4">Protocol</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($machines as $machine): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700/50">
                        <td class="p-4 text-3xl">
                            <?= $machine['Protocol'] == 'SSH' ? '💻' : '🖥️' ?>
                        </td>
                        <td class="p-4"><?= htmlspecialchars($machine['MachineName']) ?></td>
                        <td class="p-4 font-mono"><?= htmlspecialchars($machine['IPAddress']) ?></td>
                        <td class="p-4">
                            <span class="px-2 py-1 font-semibold leading-tight rounded-full <?= $machine['Protocol'] == 'SSH' ? 'bg-blue-800 text-blue-200' : 'bg-green-800 text-green-200' ?>">
                                <?= htmlspecialchars($machine['Protocol']) ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <a href="form.php?id=<?= $machine['id'] ?>" class="text-blue-400 hover:text-blue-300 mr-4">Edit</a>
                            <a href="actions.php?action=delete&id=<?= $machine['id'] ?>" class="text-red-400 hover:text-red-300" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-6">
            <a href="../dashboard.php" class="text-indigo-400 hover:underline">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>