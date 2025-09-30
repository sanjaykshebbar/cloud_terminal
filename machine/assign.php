<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.1.0
 * Info: Form to assign users to a specific machine, now with a live search filter.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-30): Added a JavaScript-based search bar to filter the user list in real-time.
 * - v1.0.0: Initial creation of the assignment form.
 */
require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

$db = get_db_connection();
$machine_id = $_GET['id'] ?? null;

// Fetch machine, user, and permission data (this part is unchanged)
$machine_stmt = $db->prepare("SELECT * FROM machines WHERE id = ?");
$machine_stmt->execute([$machine_id]);
$machine = $machine_stmt->fetch(PDO::FETCH_ASSOC);

$all_users = $db->query("SELECT id, Fname, LName, Username FROM users ORDER BY LName")->fetchAll(PDO::FETCH_ASSOC);

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

            <div class="mb-6">
                <input type="text" id="user-search-input" placeholder="Search for users by name or username..." class="w-full bg-gray-700 p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 border-0">
            </div>

            <div class="space-y-4 max-h-96 overflow-y-auto pr-2" id="user-list">
                <h3 class="text-xl font-semibold">Select users to grant access:</h3>
                <?php foreach ($all_users as $user): ?>
                <label class="user-item flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition cursor-pointer">
                    <input type="checkbox" name="user_ids[]" value="<?= $user['id'] ?>" 
                           class="h-5 w-5 rounded bg-gray-900 border-gray-600 text-indigo-600 focus:ring-indigo-500"
                           <?= in_array($user['id'], $assigned_user_ids) ? 'checked' : '' ?>>
                    <span class="user-name ml-4 text-lg"><?= htmlspecialchars($user['Fname'] . ' ' . $user['LName']) ?></span>
                    <span class="user-username ml-auto text-sm text-gray-400">(@<?= htmlspecialchars($user['Username']) ?>)</span>
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

    <script>
        const searchInput = document.getElementById('user-search-input');
        const userItems = document.querySelectorAll('.user-item');

        searchInput.addEventListener('input', (event) => {
            const searchTerm = event.target.value.toLowerCase();

            userItems.forEach(item => {
                const name = item.querySelector('.user-name').textContent.toLowerCase();
                const username = item.querySelector('.user-username').textContent.toLowerCase();

                // If the name or username includes the search term, show the item, otherwise hide it
                if (name.includes(searchTerm) || username.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>