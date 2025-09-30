<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: Form to assign users to a specific machine.
 */
require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

$db = get_db_connection();
$machine_id = $_GET['id'] ?? null;

// Fetch the machine details
$machine_stmt = $db->prepare("SELECT * FROM machines WHERE id = ?");
$machine_stmt->execute([$machine_id]);
$machine = $machine_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all users
$all_users = $db->query("SELECT id, Fname, LName, Username FROM users ORDER BY LName")->fetchAll(PDO::FETCH_ASSOC);

// Fetch IDs of users already assigned to this machine
$assigned_stmt = $db->prepare("SELECT user_id FROM user_machine_permissions WHERE machine_id = ?");
$assigned_stmt->execute([$machine_id]);
$assigned_user_ids = $assigned_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Users to Machine</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8 max-w-lg">
        <h1 class="text-3xl font-bold mb-2">Assign Users</h1>
        <p class="text-lg text-gray-400 mb-6">For Machine: <strong class="text-indigo-400"><?= htmlspecialchars($machine['MachineName']) ?></strong></p>
        
        <form action="actions.php" method="POST" class="bg-gray-800 p-8 rounded-lg">
            <input type="hidden" name="action" value="assign_users">
            <input type="hidden" name="machine_id" value="<?= $machine['id'] ?>">

            <div class="space-y-4">
                <h3 class="text-xl font-semibold">Select users to grant access:</h3>
                <?php foreach ($all_users as $user): ?>
                <label class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition cursor-pointer">
                    <input type="checkbox" name="user_ids[]" value="<?= $user['id'] ?>" 
                           class="h-5 w-5 rounded bg-gray-900 border-gray-600 text-indigo-600 focus:ring-indigo-500"
                           <?= in_array($user['id'], $assigned_user_ids) ? 'checked' : '' ?>>
                    <span class="ml-4 text-lg"><?= htmlspecialchars($user['Fname'] . ' ' . $user['LName']) ?></span>
                    <span class="ml-auto text-sm text-gray-400">(@<?= htmlspecialchars($user['Username']) ?>)</span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="mt-8 w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg">
                Save Permissions
            </button>
        </form>
         <div class="text-center mt-6">
            <a href="index.php" class="text-indigo-400 hover:underline">&larr; Back to Machine List</a>
        </div>
    </div>
</body>
</html>