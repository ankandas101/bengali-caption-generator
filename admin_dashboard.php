<?php
require_once 'admin_auth.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

// Get data for dashboard
$users = getAllUsers();
$captions = getAllCaptions();
$favorites = getAllFavorites();
$stats = getUserStats();

// Handle tab switching
$activeTab = $_GET['tab'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bengali Caption Generator</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            padding: 20px 0;
        }
        
        .admin-sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .admin-sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .admin-sidebar-header p {
            font-size: 14px;
            opacity: 0.7;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-nav li a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .admin-nav li a:hover,
        .admin-nav li a.active {
            background-color: #34495e;
        }
        
        .admin-content {
            flex: 1;
            padding: 30px;
            background-color: #f5f7fa;
            overflow-y: auto;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            font-size: 24px;
            color: #2c3e50;
        }
        
        .admin-logout {
            padding: 8px 16px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .admin-logout:hover {
            background-color: #c0392b;
        }
        
        .admin-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .admin-card h3 {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .admin-card .value {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .admin-table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .admin-table-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table-header h2 {
            font-size: 18px;
            color: #2c3e50;
            margin: 0;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #7f8c8d;
        }
        
        .admin-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .admin-table .caption-text {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .admin-table .timestamp {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .admin-tab-content {
            display: none;
        }
        
        .admin-tab-content.active {
            display: block;
        }
        
        .keyword-chart {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        .keyword-item {
            background-color: #3498db;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .keyword-count {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 8px;
            font-size: 12px;
        }
        
        .caption-details {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
        
        .caption-item {
            margin-bottom: 8px;
            padding: 8px;
            background-color: #fff;
            border-radius: 4px;
            border-left: 3px solid #3498db;
        }
        
        .show-captions-btn {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .show-captions-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2>Admin Panel</h2>
                <p>Bengali Caption Generator</p>
            </div>
            
            <ul class="admin-nav">
                <li><a href="?tab=dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="?tab=users" class="<?php echo $activeTab === 'users' ? 'active' : ''; ?>">Users</a></li>
                <li><a href="?tab=captions" class="<?php echo $activeTab === 'captions' ? 'active' : ''; ?>">Generated Captions</a></li>
                <li><a href="?tab=favorites" class="<?php echo $activeTab === 'favorites' ? 'active' : ''; ?>">User Favorites</a></li>
                <li><a href="settings.php" target="_blank">API Settings</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <a href="logout.php" class="admin-logout">Logout</a>
            </div>
            
            <!-- Dashboard Tab -->
            <div class="admin-tab-content <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
                <div class="admin-cards">
                    <div class="admin-card">
                        <h3>Total Users</h3>
                        <div class="value"><?php echo $stats['total_users']; ?></div>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Total Captions Generated</h3>
                        <div class="value"><?php echo $stats['total_captions']; ?></div>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Total Favorites</h3>
                        <div class="value"><?php echo $stats['total_favorites']; ?></div>
                    </div>
                </div>
                
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h2>Recent Users</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_users'] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td class="timestamp"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h2>Popular Keywords</h2>
                    </div>
                    <div style="padding: 20px;">
                        <div class="keyword-chart">
                            <?php foreach ($stats['popular_keywords'] as $keyword => $count): ?>
                            <div class="keyword-item">
                                <?php echo htmlspecialchars($keyword); ?>
                                <span class="keyword-count"><?php echo $count; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Tab -->
            <div class="admin-tab-content <?php echo $activeTab === 'users' ? 'active' : ''; ?>" id="users">
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h2>All Users</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td class="timestamp"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Captions Tab -->
            <div class="admin-tab-content <?php echo $activeTab === 'captions' ? 'active' : ''; ?>" id="captions">
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h2>All Generated Captions</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Keywords</th>
                                <th>Generated On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($captions as $index => $caption): ?>
                            <tr>
                                <td><?php echo $caption['id']; ?></td>
                                <td><?php echo htmlspecialchars($caption['username']); ?></td>
                                <td><?php echo htmlspecialchars($caption['keywords']); ?></td>
                                <td class="timestamp"><?php echo date('M d, Y H:i', strtotime($caption['timestamp'])); ?></td>
                                <td>
                                    <button class="show-captions-btn" onclick="toggleCaptions(<?php echo $index; ?>)">View Captions</button>
                                    <div id="caption-details-<?php echo $index; ?>" class="caption-details">
                                        <?php if (is_array($caption['captions'])): ?>
                                            <?php foreach ($caption['captions'] as $captionText): ?>
                                                <div class="caption-item"><?php echo htmlspecialchars($captionText); ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="caption-item">No captions available</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Favorites Tab -->
            <div class="admin-tab-content <?php echo $activeTab === 'favorites' ? 'active' : ''; ?>" id="favorites">
                <div class="admin-table-container">
                    <div class="admin-table-header">
                        <h2>All User Favorites</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Caption</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($favorites as $favorite): ?>
                            <tr>
                                <td><?php echo $favorite['id']; ?></td>
                                <td><?php echo htmlspecialchars($favorite['username']); ?></td>
                                <td class="caption-text"><?php echo htmlspecialchars($favorite['caption']); ?></td>
                                <td class="timestamp"><?php echo date('M d, Y H:i', strtotime($favorite['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCaptions(index) {
            const detailsElement = document.getElementById(`caption-details-${index}`);
            if (detailsElement.style.display === 'block') {
                detailsElement.style.display = 'none';
            } else {
                detailsElement.style.display = 'block';
            }
        }
    </script>
</body>
</html>