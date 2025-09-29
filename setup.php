<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.1
 * Info: A user-friendly, one-time setup wizard for the application.
 * It presents a button that, when clicked, triggers a background process
 * to create the database and tables, and then secures the DB script.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.1 (2025-09-29): Added standardized versioning and info block.
 * - v1.0.0 (Initial): Created the setup wizard functionality.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Terminal - First-Time Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center h-screen font-sans">
    <div id="setup-container" class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-md text-center">
        <h1 class="text-3xl font-bold mb-2">Welcome to Cloud Terminal</h1>
        <p class="text-gray-400 mb-6">This wizard will set up the database and tables required for the application.</p>
        
        <div id="status-message" class="my-4 p-3 rounded-lg text-left hidden"></div>

        <button id="setup-button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
            🚀 Start Setup
        </button>
    </div>

<script>
    // This JavaScript is the same as the previous version.
    const setupButton = document.getElementById('setup-button');
    const statusMessage = document.getElementById('status-message');

    setupButton.addEventListener('click', async () => {
        setupButton.disabled = true;
        setupButton.innerHTML = '⚙️ Setting up, please wait...';
        statusMessage.classList.add('hidden');

        try {
            const response = await fetch('src/run_db_setup.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                statusMessage.innerHTML = `<strong class="font-bold">Success!</strong><br>${result.message}`;
                statusMessage.classList.remove('hidden', 'bg-red-900');
                statusMessage.classList.add('bg-green-900', 'text-green-200');
                setupButton.classList.add('hidden');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            statusMessage.innerHTML = `<strong class="font-bold">Error!</strong><br>${error.message}`;
            statusMessage.classList.remove('hidden', 'bg-green-900');
            statusMessage.classList.add('bg-red-900', 'text-red-200');
            setupButton.disabled = false;
            setupButton.innerHTML = '🤔 Retry Setup';
        }
    });
</script>
</body>
</html>