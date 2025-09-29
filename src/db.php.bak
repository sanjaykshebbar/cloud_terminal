<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.1.0
 * Info: This script is a library. It DEFINES the functions for database
 * setup but does not execute them automatically. It is called by run_db_setup.php.
 * ---------------------------------------------
 * Changelog:
 * - v1.1.0 (2025-09-29): Added the 'is_active' column to the users table and
 * created the 'activity_logs' table to support user management and logging.
 * - v1.0.3: Removed all 'echo' statements from initialize_database().
 * - v1.0.2: Converted script into a library file.
 * - v1.0.1: Modified script to run from browser.
 * - v1.0.0: Initial DB creation script.
 */

function get_db_connection() {
    $db_path = __DIR__ . '/../db/cloudterminal.db';
    if (!is_dir(dirname($db_path))) {
        mkdir(dirname($db_path), 0755, true);
    }
    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // We throw a new generic Exception to avoid leaking detailed DB info.
        throw new Exception("Database connection failed.");
    }
}

function initialize_database() {
    $db = get_db_connection();
    
    // SQL for 'users' table, now with the is_active column
    $users_table_sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        Fname TEXT NOT NULL,
        LName TEXT NOT NULL,
        Username TEXT UNIQUE NOT NULL,
        EmailID TEXT UNIQUE NOT NULL,
        Password TEXT NOT NULL,
        UserType TEXT CHECK(UserType IN ('Admin', 'Faculty', 'Learner')) DEFAULT NULL,
        is_active INTEGER NOT NULL DEFAULT 1
    );";

    // SQL for 'machines' table (Unchanged)
    $machines_table_sql = "
    CREATE TABLE IF NOT EXISTS machines (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        IPAddress TEXT NOT NULL,
        MachineName TEXT NOT NULL,
        SSHKEYS TEXT DEFAULT NULL
    );";

    // SQL for 'user_machine_permissions' table (Unchanged)
    $permissions_table_sql = "
    CREATE TABLE IF NOT EXISTS user_machine_permissions (
        user_id INTEGER NOT NULL,
        machine_id INTEGER NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
        PRIMARY KEY (user_id, machine_id)
    );";
    
    // SQL for the new 'activity_logs' table
    $logs_table_sql = "
    CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        actor_id INTEGER,
        action TEXT NOT NULL,
        target_id INTEGER,
        details TEXT,
        FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
    );";

    // Execute all table creation statements
    $db->exec($users_table_sql);
    $db->exec($machines_table_sql);
    $db->exec($permissions_table_sql);
    $db->exec($logs_table_sql); // <-- Added execution for the logs table
}

?>