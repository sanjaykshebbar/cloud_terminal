<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 2.0.0
 * Info: A multi-key manager for a user's SSH public keys. Users can add,
 * view, and delete their keys for passwordless machine access.
 * ---------------------------------------------
 * Changelog:
 * - v2.0.0 (2025-10-01): Overhauled page to support adding, viewing, and
 * deleting multiple named SSH keys, interacting with the new user_ssh_keys table.
 * - v1.1.0: Added link to SSH key guide.
 * - v1.0.0: Initial creation for a single key.
 */
require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$db = get_db_connection();
$message = '';
$error = '';

// Handle form submissions for adding or deleting keys
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_key') {
        if (!empty($_POST['key_name']) && !empty($_POST['public_key'])) {
            $stmt = $db->prepare("INSERT INTO user_ssh_keys (user_id, key_name, public_key) VALUES (?, ?, ?)");
            $stmt->execute([$current_user['id'], $_POST['key_name'], $_POST['public_key']]);
            $message = 'New SSH key added successfully!';
        } else {
            $error = 'Key Name and Public Key fields cannot be empty.';
        }
    } elseif ($action === 'delete_key') {
        $key_id = $_POST['key_id'];
        // Ensure the user can only delete their own keys
        $stmt = $db->prepare("DELETE FROM user_ssh_keys WHERE id = ? AND user_id = ?");
        $stmt->execute([$key_id, $current_user['id']]);
        $message = 'SSH key deleted successfully!';
    }
}

// Fetch all keys for the current user to display
$keys_stmt = $db->prepare("SELECT * FROM user_ssh_keys WHERE user_id = ? ORDER BY key_name");
$keys_stmt->execute([$current_user['id']]);
$user_keys = $keys_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - SSH Keys</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8 max-w-4xl">
        <h1 class="text-3xl font-bold mb-6">My Profile & SSH Keys</h1>
        
        <?php if ($message): ?><div class="bg-green-800 border border-green-600 text-green-200 p-4 rounded-lg mb-6"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="bg-red-800 border border-red-600 text-red-200 p-4 rounded-lg mb-6"><?= $error ?></div><?php endif; ?>

        <div class="bg-gray-800 p-8 rounded-lg mb-8">
            <h2 class="text-2xl font-semibold mb-4">Add a New SSH Key</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_key">
                <div class="mb-4">
                    <label for="key_name" class="block mb-2 text-sm font-medium text-gray-300">Key Name</label>
                    <input name="key_name" id="key_name" type="text" placeholder="e.g., My Work Laptop" class="bg-gray-700 p-2 rounded-lg w-full" required>
                </div>
                <div class="mb-4">
                    <label for="public_key" class="block mb-2 text-sm font-medium text-gray-300">Public Key</label>
                    <textarea name="public_key" id="public_key" rows="4" class="w-full bg-gray-700 p-2 rounded-lg font-mono" placeholder="ssh-rsa AAAA..." required></textarea>
                </div>
                <div class="flex justify-between items-center">
                    <a href="how-to-ssh.php" target="_blank" class="text-sm text-indigo-400 hover:underline">How do I create an SSH Key?</a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Add Key</button>
                </div>
            </form>
        </div>

        <div class="bg-gray-800 p-8 rounded-lg">
            <h2 class="text-2xl font-semibold mb-4">Your Saved Keys</h2>
            <div class="space-y-4">
                <?php if (empty($user_keys)): ?>
                    <p class="text-gray-400">You have not added any SSH keys yet.</p>
                <?php else: ?>
                    <?php foreach ($user_keys as $key): ?>
                    <div class="bg-gray-700 p-4 rounded-lg flex items-center justify-between animate-fade-in">
                        <div>
                            <p class="font-bold text-white"><?= htmlspecialchars($key['key_name']) ?></p>
                            <p class="text-xs text-gray-400 font-mono truncate" title="<?= htmlspecialchars($key['public_key']) ?>">
                                <?= htmlspecialchars(substr($key['public_key'], 0, 70)) ?>...
                            </p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this key?');">
                            <input type="hidden" name="action" value="delete_key">
                            <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-300 font-semibold px-3">Delete</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-center mt-8">
            <a href="dashboard.php" class="text-indigo-400 hover:underline">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>