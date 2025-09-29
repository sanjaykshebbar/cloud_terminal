<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: Handles the creation of the first administrator account.
 * This script is called via fetch() from setup.php after the database
 * has been successfully initialized.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.0 (2025-09-29): Initial creation. Handles form input validation,
 * secure password hashing, and database insertion for the admin user.
 */

header('Content-Type: application/json');

// The DB script is now renamed, so we must include the .bak file
$db_library_file = __DIR__ . '/db.php.bak';

if (!file_exists($db_library_file)) {
    echo json_encode(['success' => false, 'message' => 'Database library not found. Please complete Step 1 first.']);
    exit();
}

require_once $db_library_file;

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// 1. Validate Input
if (empty($_POST['Username']) || empty($_POST['Password']) || empty($_POST['EmailID'])) {
    $response['message'] = 'Username, Password, and Email are required fields.';
    echo json_encode($response);
    exit();
}

$fname = trim($_POST['Fname']);
$lname = trim($_POST['LName']);
$username = trim($_POST['Username']);
$email = filter_var(trim($_POST['EmailID']), FILTER_VALIDATE_EMAIL);
$password = $_POST['Password'];

if (!$email) {
    $response['message'] = 'Invalid email address provided.';
    echo json_encode($response);
    exit();
}

// 2. Securely Hash the Password
// password_hash() is the modern, secure standard in PHP. It's stronger than a simple sha512.
$hashed_password = password_hash($password, PASSWORD_ARGON2ID);

try {
    $db = get_db_connection();

    // 3. Check if any user already exists
    $stmt = $db->query("SELECT id FROM users LIMIT 1");
    if ($stmt->fetch()) {
        $response['message'] = 'An admin user already exists. Setup cannot proceed.';
        echo json_encode($response);
        exit();
    }
    
    // 4. Insert the new Admin User
    $stmt = $db->prepare(
        "INSERT INTO users (Fname, LName, Username, EmailID, Password, UserType) 
         VALUES (?, ?, ?, ?, ?, 'Admin')"
    );
    
    $stmt->execute([$fname, $lname, $username, $email, $hashed_password]);

    $response['success'] = true;
    $response['message'] = 'Admin user created successfully.';
    $response['username'] = htmlspecialchars($username); // Send back username for UI message

} catch (Exception $e) {
    // Check for unique constraint violation (e.g., duplicate username/email)
    if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
        $response['message'] = 'That username or email is already taken.';
    } else {
        $response['message'] = 'Database error during user creation: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>