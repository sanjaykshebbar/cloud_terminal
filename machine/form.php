<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: A form for creating and editing machines.
 */
require_once '../src/session_check.php';
$current_user = validate_active_session();

if ($current_user['UserType'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

$machine = null;
$editMode = false;
if (isset($_GET['id'])) {
    $editMode = true;
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM machines WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editMode ? 'Edit' : 'Add' ?> Machine</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8 max-w-lg">
        <h1 class="text-3xl font-bold mb-6"><?= $editMode ? 'Edit' : 'Add' ?> Machine</h1>
        <form action="actions.php" method="POST" class="bg-gray-800 p-8 rounded-lg">
            <input type="hidden" name="action" value="<?= $editMode ? 'update_machine' : 'create_machine' ?>">
            <?php if ($editMode): ?>
                <input type="hidden" name="machine_id" value="<?= $machine['id'] ?>">
            <?php endif; ?>

            <div class="mb-4">
                <label class="block mb-2">Friendly Name</label>
                <input type="text" name="MachineName" placeholder="e.g., Web Server (Production)" value="<?= htmlspecialchars($machine['MachineName'] ?? '') ?>" class="bg-gray-700 p-2 rounded-lg w-full" required>
            </div>
            <div class="mb-4">
                <label class="block mb-2">IP Address</label>
                <input type="text" name="IPAddress" placeholder="e.g., 192.168.1.100" value="<?= htmlspecialchars($machine['IPAddress'] ?? '') ?>" class="bg-gray-700 p-2 rounded-lg w-full" required>
            </div>
             <div class="mb-6">
                <label class="block mb-2">Protocol</label>
                <select name="Protocol" class="bg-gray-700 p-2 rounded-lg w-full">
                    <option value="SSH" <?= ($machine['Protocol'] ?? '') == 'SSH' ? 'selected' : '' ?>>SSH (for Linux/macOS)</option>
                    <option value="RDP" <?= ($machine['Protocol'] ?? '') == 'RDP' ? 'selected' : '' ?>>RDP (for Windows)</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg">
                <?= $editMode ? 'Update Machine' : 'Add Machine' ?>
            </button>
        </form>
         <div class="text-center mt-6">
            <a href="index.php" class="text-indigo-400 hover:underline">&larr; Back to Machine List</a>
        </div>
    </div>
</body>
</html>