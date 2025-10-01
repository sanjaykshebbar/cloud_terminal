<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: User profile page for managing personal details and SSH public key.
 */
require_once __DIR__ . '/src/session_check.php';
$current_user = validate_active_session();

$db = get_db_connection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logic to update the SSH key
    $ssh_key = $_POST['ssh_public_key'];
    $stmt = $db->prepare("UPDATE users SET ssh_public_key = ? WHERE id = ?");
    $stmt->execute([$ssh_key, $current_user['id']]);
    $message = 'Your SSH public key has been updated successfully!';
    $current_user['ssh_public_key'] = $ssh_key; // Update the local variable too
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8 max-w-2xl">
        <h1 class="text-3xl font-bold mb-6">My Profile</h1>
        <?php if ($message): ?>
            <div class="bg-green-800 text-green-200 p-4 rounded-lg mb-6"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" class="bg-gray-800 p-8 rounded-lg">
            <div class="mb-6">
                <label for="ssh_public_key" class="block mb-2 font-medium">Your SSH Public Key</label>
                <textarea name="ssh_public_key" id="ssh_public_key" rows="8" class="w-full bg-gray-700 p-2 rounded-lg font-mono" placeholder="ssh-rsa AAAA..."><?= htmlspecialchars($current_user['ssh_public_key'] ?? '') ?></textarea>
                <p class="text-xs text-gray-400 mt-2">Paste your public key here (e.g., from your `~/.ssh/id_rsa.pub` file). This will be used for passwordless login.</p>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Save Profile</button>
        </form>
        <div class="text-center mt-6">
            <a href="dashboard.php" class="text-indigo-400 hover:underline">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>