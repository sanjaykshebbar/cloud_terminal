<?php
ini_set('display_errors', 1); // Temporary for debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: Main page for user management. Lists all users and provides
 * links to create, edit, and delete them. Access is restricted to Admins.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation of the user listing page.
 */
session_start();

// Security Check: Ensure user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    http_response_code(403);
    // For debugging, you can print a more helpful message:
    // die('Forbidden: You must be logged in as an Admin. Current UserType: ' . ($_SESSION['user_type'] ?? 'Not Set'));
    die('Forbidden: You do not have permission to access this page.');
}

require_once '../src/db.php.bak'; // Connect to the DB library

$db = get_db_connection();
$stmt = $db->query("SELECT id, Fname, LName, Username, EmailID, UserType, is_active FROM users ORDER BY LName, Fname");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">User Management</h1>
            <a href="form.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">
                + Create New User
            </a>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="p-4">Name</th>
                        <th class="p-4">Username</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-700/50">
                            <td class="p-4"><?= htmlspecialchars($user['Fname'] . ' ' . $user['LName']) ?></td>
                            <td class="p-4"><?= htmlspecialchars($user['Username']) ?></td>
                            <td class="p-4"><?= htmlspecialchars($user['EmailID']) ?></td>
                            <td class="p-4"><?= htmlspecialchars($user['UserType']) ?></td>
                            <td class="p-4">
                                <?php if ($user['is_active']): ?>
                                    <span class="bg-green-500 text-white text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full">Active</span>
                                <?php else: ?>
                                    <span class="bg-red-500 text-white text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <a href="form.php?id=<?= $user['id'] ?>" class="text-blue-400 hover:text-blue-300 mr-4">Edit</a>
                                <a href="actions.php?action=delete&id=<?= $user['id'] ?>" class="text-red-400 hover:text-red-300" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-8 text-gray-400">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-6">
            <a href="../dashboard.php" class="text-indigo-400 hover:underline">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>