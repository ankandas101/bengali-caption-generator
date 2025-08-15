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
        // Connect directly to the database
        $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
        if ($conn->connect_error) {
            return false;
        }
        
        // Set charset
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        return false;
    }
}



// Only try to connect if database functionality is enabled and MySQL is running
if ($use_database && isMySQLRunning()) {
    // The connection will be established by getConnection() function when needed
    $db_available = true;
} else {
    if (basename($_SERVER['PHP_SELF']) == 'db_config.php') {
        if (!$use_database) {
            echo "<p>Database functionality is disabled in the configuration.</p>";
        } else {
            echo "<p>MySQL server is not running. Please start your MySQL server in XAMPP.</p>";
        }
    }
}

?>