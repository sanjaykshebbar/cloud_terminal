<?php
// src/run_db_setup.php

// Set the header to return JSON
header('Content-Type: application/json');

$db_file = __DIR__ . '/db.php';
$db_file_renamed = __DIR__ . '/db.php.bak';
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Check if the setup has already been run
if (file_exists($db_file_renamed)) {
    $response['message'] = 'Setup has already been completed. The db.php script is secured.';
    echo json_encode($response);
    exit();
}

// Check if the db.php file exists before trying to run it
if (!file_exists($db_file)) {
    $response['message'] = 'Error: The required setup file (db.php) was not found.';
    echo json_encode($response);
    exit();
}

try {
    // Include the database library
    require_once $db_file;
    
    // Call the function to set up the database and tables
    initialize_database();
    
    // If successful, rename the db.php file for security
    $rename_success = rename($db_file, $db_file_renamed);
    
    if ($rename_success) {
        $response['success'] = true;
        $response['message'] = 'Database and tables created successfully. The setup script has been secured.';
    } else {
        $response['message'] = 'Database setup was successful, but failed to rename db.php. Please rename it manually for security.';
    }

} catch (Exception $e) {
    $response['message'] = 'A database error occurred: ' . $e->getMessage();
}

echo json_encode($response);

?>