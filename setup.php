<?php
// Include database configuration
require_once 'db_config.php';

// Set page title and add some styling
echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Bengali Caption Generator - Database Setup</title>\n";
echo "<link rel='stylesheet' href='style.css'>\n";
echo "<style>\n";
echo "  body { font-family: 'Poppins', sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }\n";
echo "  .setup-container { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo "  .success { color: #28a745; }\n";
echo "  .warning { color: #dc3545; }\n";
echo "  .info { color: #17a2b8; }\n";
echo "  .btn { display: inline-block; background: #4a6fa5; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";
echo "<div class='setup-container'>\n";
echo "<h1>Bengali Caption Generator - Database Setup</h1>\n";

// Check if database is available
if (isset($db_available) && $db_available) {
    echo "<p class='success'><strong>Success:</strong> Database and tables have been created successfully!</p>\n";
    echo "<p>The following database objects were created:</p>\n";
    echo "<ul>\n";
    echo "<li>Database: <code>bangla_caption_maker</code></li>\n";
    echo "<li>Tables: <code>users</code>, <code>history</code>, <code>favorites</code></li>\n";
    echo "</ul>\n";
} else {
    if (!$use_database) {
        echo "<p class='info'><strong>Notice:</strong> Database functionality is currently disabled in the configuration.</p>\n";
        echo "<p>To enable database functionality:</p>\n";
        echo "<ol>\n";
        echo "<li>Open <code>db_config.php</code></li>\n";
        echo "<li>Set <code>\$use_database = true;</code></li>\n";
        echo "<li>Refresh this page</li>\n";
        echo "</ol>\n";
    } else {
        echo "<p class='warning'><strong>Warning:</strong> Could not connect to MySQL server.</p>\n";
        echo "<p>Please make sure:</p>\n";
        echo "<ol>\n";
        echo "<li>MySQL service is running in XAMPP</li>\n";
        echo "<li>Database credentials in <code>db_config.php</code> are correct</li>\n";
        echo "</ol>\n";
    }
}

// Navigation links
echo "<div class='actions'>\n";
echo "<p>What would you like to do next?</p>\n";
echo "<a href='register.php' class='btn'>Register New Account</a>\n";
echo "<a href='login.php' class='btn'>Login</a>\n";
echo "<a href='index.php' class='btn'>Go to Homepage</a>\n";
echo "</div>\n";

// Add information about manual setup
echo "<div class='manual-setup'>\n";
echo "<h2>Manual Setup</h2>\n";
echo "<p>If you prefer to set up the database manually:</p>\n";
echo "<ol>\n";
echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin/)</li>\n";
echo "<li>Create a new database named <code>bangla_caption_maker</code></li>\n";
echo "<li>Import the <code>database.sql</code> file</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n</html>\n";