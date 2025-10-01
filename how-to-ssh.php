<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * Version: 1.0.0
 * Info: A static guide page explaining how to generate SSH keys.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How to Create SSH Keys</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300 font-sans">
    <div class="container mx-auto p-8 max-w-3xl">
        <h1 class="text-4xl font-bold text-white mb-6">How to Generate an SSH Key</h1>
        <p class="mb-8">An SSH key allows you to log in to a remote server securely without a password. It consists of a private key (which you keep secret) and a public key (which you upload to services like this one). Follow the guide for your operating system below.</p>

        <div class="space-y-10">
            <div class="bg-gray-800 p-6 rounded-lg">
                <h2 class="text-2xl font-semibold text-white mb-4">Windows (10 / 11)</h2>
                <p class="mb-4">The easiest way is to use PowerShell or Windows Terminal.</p>
                <h3 class="font-bold text-lg text-white mb-2">Step 1: Generate the Key</h3>
                <p class="mb-2">Open PowerShell and run the following command. Press Enter to accept the default file location and you can optionally add a passphrase for extra security (or leave it blank).</p>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-sm"><code>ssh-keygen -t rsa -b 4096</code></pre>

                <h3 class="font-bold text-lg text-white mt-6 mb-2">Step 2: Copy the Public Key</h3>
                <p class="mb-2">Run the command below to display your public key. Copy the entire output, starting from `ssh-rsa`.</p>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-sm"><code>Get-Content $env:USERPROFILE\.ssh\id_rsa.pub | Set-Clipboard</code></pre>
                 <p class="text-xs text-gray-400 mt-1">This command automatically copies the key to your clipboard.</p>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg">
                <h2 class="text-2xl font-semibold text-white mb-4">macOS & Linux</h2>
                <h3 class="font-bold text-lg text-white mb-2">Step 1: Generate the Key</h3>
                <p class="mb-2">Open your Terminal and run the following command. Accept the defaults and optionally add a passphrase.</p>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-sm"><code>ssh-keygen -t rsa -b 4096</code></pre>

                <h3 class="font-bold text-lg text-white mt-6 mb-2">Step 2: Copy the Public Key</h3>
                <p class="mb-2">Run one of the commands below to display your public key. Copy the entire output.</p>
                <p class="font-bold text-sm text-white mb-2">For macOS:</p>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-sm"><code>pbcopy < ~/.ssh/id_rsa.pub</code></pre>
                <p class="text-xs text-gray-400 mt-1">This command automatically copies the key to your clipboard.</p>
                <p class="font-bold text-sm text-white mt-4 mb-2">For Linux:</p>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-sm"><code>cat ~/.ssh/id_rsa.pub</code></pre>
            </div>

            <div class="bg-green-900/50 border border-green-700 p-6 rounded-lg">
                 <h2 class="text-2xl font-semibold text-white mb-4">Final Step: Save Your Key</h2>
                 <p>Once you have copied your public key, go back to the **My Profile** page in the Cloud Terminal and paste the entire key into the text box, then click "Save Profile".</p>
            </div>
        </div>
        <div class="text-center mt-8">
            <button onclick="window.close()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Close this Window</button>
        </div>
    </div>
</body>
</html>