<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.1.0
 * Info: A multi-step setup wizard for the application.
 * Step 1: Sets up the database and tables.
 * Step 2: Creates the initial administrator account.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-29): Added a second step to create an admin user after DB setup is complete.
 * - v1.0.1: Added standardized versioning and info block.
 * - v1.0.0: Created the initial setup wizard functionality.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Terminal - Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen font-sans py-8">
    <div class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-md">

        <div id="setup-container" class="text-center">
            <h1 class="text-3xl font-bold mb-2">Welcome to Cloud Terminal</h1>
            <p class="text-gray-400 mb-6">Step 1: Database and Table Setup</p>
            <button id="setup-button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                🚀 Start Database Setup
            </button>
        </div>

        <div id="admin-form-container" class="hidden">
            <h1 class="text-3xl font-bold mb-2 text-center">Setup Complete!</h1>
            <p class="text-gray-400 mb-6 text-center">Step 2: Create Your Admin Account</p>
            <form id="admin-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <input type="text" name="Fname" placeholder="First Name" class="bg-gray-700 p-2 rounded-lg w-full" required>
                    <input type="text" name="LName" placeholder="Last Name" class="bg-gray-700 p-2 rounded-lg w-full" required>
                </div>
                <div class="mb-4">
                    <input type="text" name="Username" placeholder="Username" class="bg-gray-700 p-2 rounded-lg w-full" required>
                </div>
                <div class="mb-4">
                    <input type="email" name="EmailID" placeholder="Email Address" class="bg-gray-700 p-2 rounded-lg w-full" required>
                </div>
                <div class="mb-6">
                    <input type="password" name="Password" placeholder="Password" class="bg-gray-700 p-2 rounded-lg w-full" required>
                </div>
                <button type="submit" id="create-admin-button" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                    ✅ Create Admin User
                </button>
            </form>
        </div>

        <div id="status-message" class="my-4 p-3 rounded-lg text-left hidden"></div>

        <div id="final-success" class="hidden text-center">
             <p class="text-gray-300 mb-6">You can now log in to the application.</p>
             <a href="index.php" class="w-full inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                Go to Login Page
             </a>
        </div>
    </div>

<script>
    // Elements for Step 1
    const setupContainer = document.getElementById('setup-container');
    const setupButton = document.getElementById('setup-button');
    
    // Elements for Step 2
    const adminFormContainer = document.getElementById('admin-form-container');
    const adminForm = document.getElementById('admin-form');
    const createAdminButton = document.getElementById('create-admin-button');

    // Common Elements
    const statusMessage = document.getElementById('status-message');
    const finalSuccess = document.getElementById('final-success');

    // --- Step 1 Logic ---
    setupButton.addEventListener('click', async () => {
        setupButton.disabled = true;
        setupButton.innerHTML = '⚙️ Setting up database...';
        hideStatus();

        try {
            const response = await fetch('src/run_db_setup.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                // Transition from Step 1 to Step 2
                setupContainer.classList.add('hidden');
                adminFormContainer.classList.remove('hidden');
                showStatus('success', `<strong>Success!</strong><br>${result.message}`);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            showStatus('error', `<strong>Error!</strong><br>${error.message}`);
            setupButton.disabled = false;
            setupButton.innerHTML = '🤔 Retry Database Setup';
        }
    });

    // --- Step 2 Logic ---
    adminForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        createAdminButton.disabled = true;
        createAdminButton.innerHTML = 'Creating User...';
        hideStatus();

        const formData = new FormData(adminForm);

        try {
            const response = await fetch('src/create_admin.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                adminFormContainer.classList.add('hidden');
                finalSuccess.classList.remove('hidden');
                showStatus('success', `<strong>Admin user '${result.username}' created!</strong><br>Setup is fully complete.`);
            } else {
                throw new Error(result.message);
            }
        } catch(error) {
            showStatus('error', `<strong>Error!</strong><br>${error.message}`);
            createAdminButton.disabled = false;
            createAdminButton.innerHTML = '✅ Create Admin User';
        }
    });

    // --- Helper Functions for UI ---
    function showStatus(type, message) {
        statusMessage.innerHTML = message;
        statusMessage.className = 'my-4 p-3 rounded-lg text-left'; // Reset classes
        if (type === 'success') {
            statusMessage.classList.add('bg-green-900', 'text-green-200');
        } else {
            statusMessage.classList.add('bg-red-900', 'text-red-200');
        }
    }

    function hideStatus() {
        statusMessage.classList.add('hidden');
    }
</script>
</body>
</html>