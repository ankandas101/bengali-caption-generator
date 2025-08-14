<?php
// Setup script to create the api_keys table
require_once 'db_config.php';

// Create connection using MySQLi
$conn = getConnection();

if (!$conn) {
    die("Could not connect to database");
}

// SQL to create api_keys table
$sql = "CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    usage_count INT NOT NULL DEFAULT 0,
    last_error_count INT NOT NULL DEFAULT 0,
    last_error_message TEXT,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ api_keys table created successfully!\n";
    
    // Check if table was created
    $result = $conn->query("SELECT COUNT(*) as count FROM api_keys");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "📊 Current API keys count: " . $row['count'] . "\n";
    }
    
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>