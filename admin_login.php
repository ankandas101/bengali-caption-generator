<?php
require_once 'admin_auth.php';

// Check if admin is already logged in
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $result = adminLogin($username, $password);
        
        if ($result === true) {
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = $result['error'] ?? 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bengali Caption Generator</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .admin-login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            font-size: 24px;
            color: #333;
        }
        
        .admin-form-group {
            margin-bottom: 20px;
        }
        
        .admin-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .admin-form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .admin-btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .admin-btn:hover {
            background: #2980b9;
        }
        
        .admin-error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-login-container">
            <div class="admin-header">
                <h1>Admin Login</h1>
                <p>Bengali Caption Generator</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="admin-form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="admin-btn">Login</button>
            </form>
            
            <div class="back-link">
                <a href="index.php">Back to Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>