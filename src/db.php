<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.0
 * Info: This script handles the database setup for the Cloud Terminal application.
 * It creates a connection to an SQLite database and initializes the necessary
 * tables: 'users', 'machines', and a linking table 'user_machine_permissions'
 * to manage access control. This script is intended to be run once from the
 * command line to prepare the database schema.
 */

// src/db.php

/**
 * Gets a connection to the SQLite database.
 * @return PDO The PDO database connection object.
 */
function get_db_connection() {
    // Define the path to the database file relative to this script's location.
    $db_path = __DIR__ . '/../db/cloudterminal.db';
    
    // Ensure the directory exists.
    if (!is_dir(dirname($db_path))) {
        mkdir(dirname($db_path), 0755, true);
    }

    try {
        // Create a new PDO instance. The connection is a file-based SQLite DB.
        $pdo = new PDO('sqlite:' . $db_path);
        
        // Set the PDO error mode to exception for better error handling.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    } catch (PDOException $e) {
        // Halt script execution if connection fails.
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Creates the database schema (tables) if they don't already exist.
 */
function initialize_database() {
    $db = get_db_connection();

    // SQL statement for the 'users' table
    $users_table_sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        Fname TEXT NOT NULL,
        LName TEXT NOT NULL,
        Username TEXT UNIQUE NOT NULL,
        EmailID TEXT UNIQUE NOT NULL,
        Password TEXT NOT NULL, -- Will store the SHA512 hash
        UserType TEXT CHECK(UserType IN ('Admin', 'Faculty', 'Learner')) DEFAULT NULL
    );";

    // SQL statement for the 'machines' table
    $machines_table_sql = "
    CREATE TABLE IF NOT EXISTS machines (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        IPAddress TEXT NOT NULL,
        MachineName TEXT NOT NULL,
        SSHKEYS TEXT DEFAULT NULL
    );";

    // SQL statement for the linking table to manage permissions
    $permissions_table_sql = "
    CREATE TABLE IF NOT EXISTS user_machine_permissions (
        user_id INTEGER NOT NULL,
        machine_id INTEGER NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
        PRIMARY KEY (user_id, machine_id)
    );";

    try {
        // Execute all CREATE TABLE statements
        $db->exec($users_table_sql);
        $db->exec($machines_table_sql);
        $db->exec($permissions_table_sql);
        
        echo "✅ Database and tables created successfully (if they didn't exist).\n";

    } catch (PDOException $e) {
        die("Error creating tables: " . $e->getMessage());
    }
}

// This block allows the script to be executed directly from the command line for setup.
// The check `php_sapi_name() === 'cli'` ensures this part only runs in Command Line Interface mode.
if (php_sapi_name() === 'cli') {
    initialize_database();
}

?>