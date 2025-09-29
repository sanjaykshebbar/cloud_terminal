<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.1
 * Info: This script handles the database setup for the Cloud Terminal application.
 * It creates a connection to an SQLite database and initializes the necessary
 * tables. This version is modified to run directly from a web browser.
 *
 * SECURITY WARNING: After running this script successfully, you MUST delete it
 * or move it outside of your web server's public directory.
 */

// Set content type to plain text for cleaner browser output
header('Content-Type: text/plain');

/**
 * Gets a connection to the SQLite database.
 * @return PDO The PDO database connection object.
 */
function get_db_connection() {
    // The path is relative to this file's location (src), so it correctly
    // points to the 'db' folder in the project root.
    $db_path = __DIR__ . '/../db/cloudterminal.db';
    
    // Ensure the directory exists.
    if (!is_dir(dirname($db_path))) {
        mkdir(dirname($db_path), 0755, true);
    }

    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
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
        Password TEXT NOT NULL,
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
        $db->exec($users_table_sql);
        echo "✅ 'users' table created successfully.\n";
        
        $db->exec($machines_table_sql);
        echo "✅ 'machines' table created successfully.\n";

        $db->exec($permissions_table_sql);
        echo "✅ 'user_machine_permissions' table created successfully.\n\n";

        echo "🎉 Database setup is complete.\n";

    } catch (PDOException $e) {
        http_response_code(500);
        die("Error creating tables: " . $e->getMessage());
    }
}

// --- SCRIPT EXECUTION ---
// This function is now called directly when the script is accessed.
initialize_database();

?>