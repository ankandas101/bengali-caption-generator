<?php
require_once 'db_config.php';

// Check if database is available
if (!$use_database) {
    echo "<p>Database functionality is disabled. Please enable it in db_config.php first.</p>";
    exit;
}

// Check if MySQL is running
if (!isMySQLRunning()) {
    echo "<p>MySQL server is not running. Please start your MySQL server in XAMPP.</p>";
    exit;
}

// Function to add is_admin column to users table if it doesn't exist
function addAdminColumn() {
    $conn = getConnection();
    
    if (!$conn) {
        return "Failed to connect to database.";
    }
    
    // Check if column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0";
        
        if ($conn->query($sql) === TRUE) {
            $message = "Added 'is_admin' column to users table.";
        } else {
            $message = "Error adding column: " . $conn->error;
        }
    } else {
        $message = "The 'is_admin' column already exists.";
    }
    
    $conn->close();
    return $message;
}

// Function to create admin user if it doesn't exist
function createAdminUser() {
    $conn = getConnection();
    
    if (!$conn) {
        return "Failed to connect to database.";
    }
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Admin user doesn't exist, create it
        $username = 'admin';
        $email = 'admin@example.com';
        $password = password_hash('admin123', PASSWORD_DEFAULT); // Default password: admin123
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            $message = "Created admin user with username 'admin' and password 'admin123'.";
        } else {
            $message = "Error creating admin user: " . $stmt->error;
        }
    } else {
        // Admin exists, make sure is_admin flag is set
        $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
        $stmt->execute();
        $message = "Admin user already exists. Ensured admin privileges are set.";
    }
    
    $stmt->close();
    $conn->close();
    return $message;
}

// Run the setup
$columnMessage = addAdminColumn();
$userMessage = createAdminUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Bengali Caption Generator</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-message {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        .setup-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .setup-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .setup-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .setup-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .setup-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .setup-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background-color 0.3s;
        }
        
        .setup-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Admin Setup</h1>
            <p>Bengali Caption Generator</p>
        </div>
        
        <div class="setup-message <?php echo strpos($columnMessage, 'Error') !== false ? 'setup-error' : 'setup-success'; ?>">
            <?php echo $columnMessage; ?>
        </div>
        
        <div class="setup-message <?php echo strpos($userMessage, 'Error') !== false ? 'setup-error' : 'setup-success'; ?>">
            <?php echo $userMessage; ?>
        </div>
        
        <div class="setup-message setup-info">
            <h3>Admin Login Details:</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p><strong>Important:</strong> Please change this password after your first login for security reasons.</p>
        </div>
        
        <div class="setup-actions">
            <a href="admin_login.php" class="setup-btn">Go to Admin Login</a>
            <a href="index.php" class="setup-btn">Back to Homepage</a>
        </div>
    </div>
</body>
</html>