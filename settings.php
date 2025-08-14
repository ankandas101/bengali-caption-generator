<?php
require_once 'auth.php';
require_once 'db_config.php';

// Create PDO connection for API key management
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit();
}

// Handle API key operations
$message = '';
$error = '';

// Add new API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_api_key'])) {
    $api_key = trim($_POST['api_key']);
    $description = trim($_POST['description']);
    
    if (!empty($api_key)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO api_keys (api_key, description, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$api_key, $description]);
            $message = 'API key added successfully!';
        } catch (PDOException $e) {
            $error = 'Error adding API key: ' . $e->getMessage();
        }
    }
}

// Delete API key
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'API key deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting API key: ' . $e->getMessage();
    }
}

// Toggle API key status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    try {
        $stmt = $pdo->prepare("UPDATE api_keys SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'API key status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating API key status: ' . $e->getMessage();
    }
}

// Get all API keys
try {
    $stmt = $pdo->query("SELECT * FROM api_keys ORDER BY created_at DESC");
    $api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching API keys: ' . $e->getMessage();
    $api_keys = [];
}

// Test API key functionality
if (isset($_POST['test_api_key'])) {
    $test_key = trim($_POST['test_key']);
    if (!empty($test_key)) {
        $test_result = testApiKey($test_key);
    }
}

function testApiKey($api_key) {
    $ch = curl_init('https://openrouter.ai/api/v1/models');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $http_code === 200,
        'status' => $http_code,
        'message' => $http_code === 200 ? 'API key is valid' : 'API key is invalid or has issues'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Settings - Bangla Caption Maker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            color: #000000;
        }
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #f8f9fa;
        }
        .settings-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .settings-header h1 {
            color: #000000;
        }
        .settings-header p {
            color: #495057;
        }
        .api-key-form {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #000000;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 10px;
            background: #ffffff;
            color: #000000;
            font-size: 16px;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #6c757d;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn-success {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            padding: 8px 16px;
            font-size: 14px;
        }
        .api-keys-table {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .api-keys-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .api-keys-table th,
        .api-keys-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .api-keys-table th {
            background: #f8f9fa;
            color: #000000;
            font-weight: 600;
        }
        .api-keys-table td {
            color: #000000;
        }
        .api-key-preview {
            font-family: monospace;
            font-size: 12px;
            background: #f8f9fa;
            color: #000000;
            padding: 5px 10px;
            border-radius: 5px;
            word-break: break-all;
        }
        .status-active {
            color: #198754;
            font-weight: 600;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }
        .message.error {
            background: rgba(248, 113, 113, 0.2);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #000000;
            text-decoration: none;
            padding: 10px 20px;
            background: #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: #dee2e6;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="settings-header">
            <h1>API Key Management</h1>
            <p>Manage your OpenRouter API keys for the caption generator</p>
        </div>

        <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="api-key-form">
            <h2>Add New API Key</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="api_key">API Key:</label>
                    <input type="text" id="api_key" name="api_key" placeholder="Enter your OpenRouter API key" required>
                </div>
                <div class="form-group">
                    <label for="description">Description (optional):</label>
                    <input type="text" id="description" name="description" placeholder="e.g., Production Key, Backup Key">
                </div>
                <button type="submit" name="add_api_key" class="btn btn-primary">Add API Key</button>
            </form>
        </div>

        <div class="api-key-form">
            <h2>Test API Key</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="test_key">Test API Key:</label>
                    <input type="text" id="test_key" name="test_key" placeholder="Enter API key to test">
                </div>
                <button type="submit" name="test_api_key" class="btn btn-success">Test Key</button>
            </form>
            <?php if (isset($test_result)): ?>
                <div class="message <?php echo $test_result['success'] ? 'success' : 'error'; ?>" style="margin-top: 15px;">
                    <?php echo htmlspecialchars($test_result['message']); ?> (Status: <?php echo $test_result['status']; ?>)
                </div>
            <?php endif; ?>
        </div>

        <div class="api-keys-table">
            <h2 style="padding: 20px; margin: 0; color: white;">Manage API Keys</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>API Key Preview</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($api_keys as $key): ?>
                        <tr>
                            <td><?php echo $key['id']; ?></td>
                            <td>
                                <div class="api-key-preview">
                                    <?php echo substr($key['api_key'], 0, 8) . '...' . substr($key['api_key'], -4); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($key['description'] ?: 'No description'); ?></td>
                            <td>
                                <span class="status-<?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($key['created_at'])); ?></td>
                            <td>
                                <?php echo $key['last_used'] ? date('Y-m-d H:i', strtotime($key['last_used'])) : 'Never'; ?>
                            </td>
                            <td>
                                <a href="?toggle=<?php echo $key['id']; ?>" class="btn btn-success">
                                    <?php echo $key['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="?delete=<?php echo $key['id']; ?>" class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this API key?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($api_keys)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                No API keys found. Add your first API key above.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>