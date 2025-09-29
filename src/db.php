<?php
/**
 * Code Author: SanjayKS
 * Email ID: sanjaykehebbar@gmail.com
 * ---------------------------------------------
 * Version: 1.0.3
 * Info: This script is a library. It DEFINES the functions for database
 * setup but does not execute them automatically. It is called by run_db_setup.php.
 * ---------------------------------------------
 * Changelog:
 * - v1.0.3 (2025-09-29): Removed all 'echo' statements from initialize_database()
 * to ensure it only returns silent success or an exception on error. This fixes
 * the "invalid JSON" issue during setup.
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
    
    // The following SQL statements remain unchanged.
    $users_table_sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, Fname TEXT NOT NULL, LName TEXT NOT NULL,
        Username TEXT UNIQUE NOT NULL, EmailID TEXT UNIQUE NOT NULL, Password TEXT NOT NULL,
        UserType TEXT CHECK(UserType IN ('Admin', 'Faculty', 'Learner')) DEFAULT NULL
    );";

    $machines_table_sql = "
    CREATE TABLE IF NOT EXISTS machines (
        id INTEGER PRIMARY KEY AUTOINCREMENT, IPAddress TEXT NOT NULL,
        MachineName TEXT NOT NULL, SSHKEYS TEXT DEFAULT NULL
    );";

    $permissions_table_sql = "
    CREATE TABLE IF NOT EXISTS user_machine_permissions (
        user_id INTEGER NOT NULL, machine_id INTEGER NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
        PRIMARY KEY (user_id, machine_id)
    );";

    // ** THE FIX IS HERE **
    // All echo statements have been removed. The function is now silent.
    $db->exec($users_table_sql);
    $db->exec($machines_table_sql);
    $db->exec($permissions_table_sql);
}

?>