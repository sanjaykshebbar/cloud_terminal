<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: A form for creating and editing users. Enters "edit mode" if a
 * user ID is passed as a URL parameter.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation of the combined create/edit form.
 */
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once '../src/db.php.bak';

$user = null;
$editMode = false;
if (isset($_GET['id'])) {
    $editMode = true;
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editMode ? 'Edit' : 'Create' ?> User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-8 max-w-lg">
        <h1 class="text-3xl font-bold mb-6"><?= $editMode ? 'Edit' : 'Create' ?> User</h1>
        <form action="actions.php" method="POST" class="bg-gray-800 p-8 rounded-lg">
            <input type="hidden" name="action" value="<?= $editMode ? 'update_user' : 'create_user' ?>">
            <?php if ($editMode): ?>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <input type="text" name="Fname" placeholder="First Name" value="<?= htmlspecialchars($user['Fname'] ?? '') ?>" class="bg-gray-700 p-2 rounded-lg w-full" required>
                <input type="text" name="LName" placeholder="Last Name" value="<?= htmlspecialchars($user['LName'] ?? '') ?>" class="bg-gray-700 p-2 rounded-lg w-full" required>
            </div>
            <div class="mb-4">
                <input type="text" name="Username" placeholder="Username" value="<?= htmlspecialchars($user['Username'] ?? '') ?>" class="bg-gray-700 p-2 rounded-lg w-full" required>
            </div>
            <div class="mb-4">
                <input type="email" name="EmailID" placeholder="Email Address" value="<?= htmlspecialchars($user['EmailID'] ?? '') ?>" class="bg-gray-700 p-2 rounded-lg w-full" required>
            </div>
            <div class="mb-4">
                <input type="password" name="Password" placeholder="Password (leave blank if not changing)" class="bg-gray-700 p-2 rounded-lg w-full" <?= !$editMode ? 'required' : '' ?>>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <select name="UserType" class="bg-gray-700 p-2 rounded-lg w-full">
                    <option value="Learner" <?= ($user['UserType'] ?? '') == 'Learner' ? 'selected' : '' ?>>Learner</option>
                    <option value="Faculty" <?= ($user['UserType'] ?? '') == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="Admin" <?= ($user['UserType'] ?? '') == 'Admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <select name="is_active" class="bg-gray-700 p-2 rounded-lg w-full">
                    <option value="1" <?= ($user['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($user['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Disabled</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg">
                <?= $editMode ? 'Update User' : 'Create User' ?>
            </button>
        </form>
         <div class="text-center mt-6">
            <a href="index.php" class="text-indigo-400 hover:underline">&larr; Back to User List</a>
        </div>
    </div>
</body>
</html>