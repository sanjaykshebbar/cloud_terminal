<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.1.0
 * Info: This is the main landing page for the Cloud Terminal application.
 * It serves as the login page and now includes a section to display
 * detailed error messages to the user upon a failed login attempt.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-29): Added a PHP block to handle and display specific
 * login errors passed as URL parameters from auth.php.
 * - v1.0.0: Initial creation of the login page with a form and a
 * development-only link to the setup page.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Terminal - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center h-screen font-sans">
    <div class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-sm">
        <h1 class="text-3xl font-bold mb-6 text-center text-indigo-400">Cloud Terminal 🔑</h1>
        
        <?php if (isset($_GET['error'])): ?>
            <?php
            $error_message = '';
            switch ($_GET['error']) {
                case 'user_not_found':
                    $error_message = 'No account found with that username.';
                    break;
                case 'user_inactive':
                    $error_message = 'This account has been disabled. Please contact an administrator.';
                    break;
                case 'invalid_credentials':
                    $error_message = 'Incorrect password. Please try again.';
                    break;
                default:
                    $error_message = 'An unknown error occurred. Please try again.';
                    break;
            }
            ?>
            <div class="bg-red-800 border border-red-600 text-red-200 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Login Failed:</strong>
                <span class="block sm:inline"><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <form action="src/auth.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block mb-2 text-sm font-medium text-gray-300">Username</label>
                <input type="text" name="username" id="username" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" placeholder="your-username" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-300">Password</label>
                <input type="password" name="password" id="password" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" required>
            </div>
            <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Login</button>
        </form>

        <div class="text-center mt-6">
            <p class="text-xs text-gray-500 mb-2">-- For Development Only --</p>
            <a href="setup.php" class="w-full inline-block bg-gray-600 hover:bg-gray-700 focus:ring-4 focus:outline-none focus:ring-gray-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                Run First-Time Setup
            </a>
        </div>
    </div>
</body>
</html>