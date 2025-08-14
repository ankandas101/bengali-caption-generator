<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = ''; // Default XAMPP password is empty
$db_name = 'bangla_caption_maker';

// Flag to enable/disable database functionality
$use_database = true; // Set to false to disable database functionality completely

// Variable to track if database is available
$db_available = false;

// Function to check if MySQL is running
function isMySQLRunning() {
    $connection = @fsockopen($GLOBALS['db_host'], 3306); 
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

// Function to get database connection
function getConnection() {
    global $db_host, $db_user, $db_password, $db_name, $use_database;
    
    // If database functionality is disabled, return false
    if (!$use_database) {
        return false;
    }
    
    // Check if MySQL is running before attempting connection
    if (!isMySQLRunning()) {
        return false;
    }
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
        if ($conn->connect_error) {
            return false;
        }
        return $conn;
    } catch (Exception $e) {
        return false;
    }
}

// Only try to connect if database functionality is enabled and MySQL is running
if ($use_database && isMySQLRunning()) {
    // Create connection
    try {
        $conn = new mysqli($db_host, $db_user, $db_password);
        
        // Check connection
        if ($conn->connect_error) {
            if (basename($_SERVER['PHP_SELF']) == 'db_config.php') {
                echo "Connection failed: " . $conn->connect_error;
            }
        } else {
            $db_available = true;
            
            // Create database if not exists
            $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
            if ($conn->query($sql) !== TRUE) {
                if (basename($_SERVER['PHP_SELF']) == 'db_config.php') {
                    echo "Error creating database: " . $conn->error;
                }
            } else {
                // Select the database
                $conn->select_db($db_name);
                
                // Include SQL file for table creation if this file is accessed directly
                if (basename($_SERVER['PHP_SELF']) == 'db_config.php') {
                    // Read SQL file
                    $sql_file = file_get_contents('database.sql');
                    
                    // Execute SQL commands
                    if ($sql_file) {
                        $queries = explode(';', $sql_file);
                        $success = true;
                        
                        foreach ($queries as $query) {
                            $query = trim($query);
                            if (!empty($query)) {
                                if ($conn->query($query) !== TRUE) {
                                    echo "Error executing SQL: " . $conn->error . "<br>";
                                    $success = false;
                                }
                            }
                        }
                        
                        if ($success) {
                            echo "<p>Database and tables created successfully!</p>";
                        }
                    } else {
                        echo "<p>Could not read SQL file. Please make sure 'database.sql' exists.</p>";
                    }
                } else {
                    // Create tables programmatically when included in other files
                    // Create users table
                    $sql = "CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        email VARCHAR(100) UNIQUE NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    
                    $conn->query($sql);
                    
                    // Create history table
                    $sql = "CREATE TABLE IF NOT EXISTS history (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        keywords TEXT NOT NULL,
                        captions TEXT NOT NULL,
                        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )";
                    
                    $conn->query($sql);
                    
                    // Create favorites table
                    $sql = "CREATE TABLE IF NOT EXISTS favorites (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        caption TEXT NOT NULL,
                        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )";
                    
                    $conn->query($sql);
                }
            }
            
            // Close initial connection
            $conn->close();
        }
    } catch (Exception $e) {
        if (basename($_SERVER['PHP_SELF']) == 'db_config.php') {
            echo "Connection failed: " . $e->getMessage();
        }
    }
} else {
    if (basename($_SERVER['PHP_SELF']) == 'db_config.php') {
        if (!$use_database) {
            echo "<p>Database functionality is disabled in the configuration.</p>";
        } else {
            echo "<p>MySQL server is not running. Please start your MySQL server in XAMPP.</p>";
        }
    }
}

// Note: saveUserHistory and getUserHistory functions are defined in auth.php
?>