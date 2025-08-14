<?php
require_once 'auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user information
$userId = getCurrentUserId();
$username = getCurrentUsername();

// Handle profile update
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newUsername = trim($_POST['username']);
    $newEmail = trim($_POST['email']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Get current user data
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT email, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    // Validate inputs
    if (empty($newUsername)) {
        $errors[] = "Username cannot be empty";
    }
    
    if (empty($newEmail)) {
        $errors[] = "Email cannot be empty";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $newEmail, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    // Check if username already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $newUsername, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    
    // Handle password change if provided
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        if (!password_verify($currentPassword, $userData['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (empty($newPassword)) {
            $errors[] = "New password is required";
        } elseif (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET username = ?, email = ?";
        $params = ["ssi", $newUsername, $newEmail, $userId];
        
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery .= ", password = ?";
            $params[0] = "sssi";
            array_splice($params, 3, 0, [$hashedPassword]);
        }
        
        $updateQuery .= " WHERE id = ?";
        
        $updateStmt = $conn->prepare($updateQuery);
        call_user_func_array([$updateStmt, 'bind_param'], $params);
        
        if ($updateStmt->execute()) {
            $success = true;
            $_SESSION['username'] = $newUsername;
            $username = $newUsername;
        } else {
            $errors[] = "Failed to update profile";
        }
        
        $updateStmt->close();
    }
    
    $stmt->close();
    $conn->close();
}

// Get user email
$conn = getConnection();
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$email = $userData['email'] ?? '';
$stmt->close();
$conn->close();

// Get user history and favorites (using functions from auth.php)

$history = getUserHistory($userId);
$favorites = getUserFavorites($userId);

// Get active tab from URL parameter
$activeTab = $_GET['tab'] ?? 'history';
$allowedTabs = ['history', 'favorites', 'profile'];
if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'history';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Bengali Caption Maker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-color: #e2e8f0;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .dashboard-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-title i {
            font-size: 32px;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .home-btn {
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .home-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-toggle {
            background: white;
            border: 2px solid var(--border-color);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: var(--transition);
        }

        .profile-toggle:hover {
            border-color: var(--primary-color);
        }

        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            min-width: 250px;
            margin-top: 10px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
        }

        .profile-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-email {
            font-size: 14px;
            opacity: 0.9;
        }

        .profile-actions {
            padding: 10px 0;
        }

        .profile-action-btn {
            display: block;
            width: 100%;
            padding: 12px 20px;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            color: var(--dark-color);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-action-btn:hover {
            background: var(--light-color);
        }

        .profile-action-btn.logout {
            color: var(--danger-color);
        }

        .dashboard-tabs {
            background: white;
            border-radius: var(--border-radius);
            padding: 5px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            display: flex;
            gap: 5px;
        }

        .dashboard-tab {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            border-radius: calc(var(--border-radius) - 5px);
            transition: var(--transition);
            position: relative;
        }

        .dashboard-tab:hover {
            background: var(--light-color);
        }

        .dashboard-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .dashboard-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            min-height: 400px;
        }

        .content-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .content-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .content-subtitle {
            color: var(--secondary-color);
            font-size: 16px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--border-color);
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .empty-state p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .generate-btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .generate-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .caption-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .caption-card {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 20px;
            transition: var(--transition);
        }

        .caption-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .caption-text {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .caption-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 14px;
            color: var(--secondary-color);
        }

        .keywords {
            background: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .caption-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .caption-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .copy-btn {
            background: var(--primary-color);
            color: white;
        }

        .copy-btn:hover {
            background: var(--primary-dark);
        }

        .remove-btn {
            background: var(--danger-color);
            color: white;
        }

        .remove-btn:hover {
            background: #dc2626;
        }

        .profile-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
        }

        .password-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid var(--border-color);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .section-subtitle {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 15px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-color: var(--success-color);
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: var(--danger-color);
        }

        .error-list {
            margin-bottom: 20px;
        }

        .error-item {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }

            .user-actions {
                width: 100%;
                justify-content: space-between;
            }

            .dashboard-tabs {
                flex-direction: column;
            }

            .dashboard-tab {
                text-align: center;
            }

            .dashboard-content {
                padding: 20px;
            }

            .caption-grid {
                grid-template-columns: 1fr;
            }

            .caption-actions {
                flex-direction: column;
            }

            .profile-menu {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                margin: 0;
                border-radius: 0;
                min-width: auto;
                transform: translateX(100%);
            }

            .profile-menu.active {
                transform: translateX(0);
            }
        }

        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 24px;
            }

            .content-title {
                font-size: 20px;
            }

            .caption-card {
                padding: 15px;
            }

            .profile-form {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-magic"></i>
                Bengali Caption Maker
            </div>
            <div class="user-actions">
                <a href="index.php" class="home-btn">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <div class="profile-dropdown">
                    <button class="profile-toggle" id="profileToggle">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($username); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-menu" id="profileMenu">
                        <div class="profile-header">
                            <div class="profile-name"><?php echo htmlspecialchars($username); ?></div>
                            <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
                        </div>
                        <div class="profile-actions">
                            <button class="profile-action-btn" onclick="showTab('profile')">
                                <i class="fas fa-user-edit"></i>
                                Edit Profile
                            </button>
                            <a href="logout.php" class="profile-action-btn logout">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-tabs">
            <button class="dashboard-tab <?php echo $activeTab === 'history' ? 'active' : ''; ?>" 
                    onclick="showTab('history')">
                <i class="fas fa-history"></i>
                History
            </button>
            <button class="dashboard-tab <?php echo $activeTab === 'favorites' ? 'active' : ''; ?>" 
                    onclick="showTab('favorites')">
                <i class="fas fa-star"></i>
                Favorites
            </button>
            <button class="dashboard-tab <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" 
                    onclick="showTab('profile')">
                <i class="fas fa-user-cog"></i>
                Profile
            </button>
        </div>

        <!-- History Tab -->
        <div id="history-content" class="dashboard-content" style="display: <?php echo $activeTab === 'history' ? 'block' : 'none'; ?>;">
            <div class="content-header">
                <h2 class="content-title">Your Caption History</h2>
                <p class="content-subtitle">All your previously generated captions in one place</p>
            </div>

            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>No History Yet</h3>
                    <p>You haven't generated any captions yet. Start creating amazing Bengali captions!</p>
                    <a href="index.php" class="generate-btn">
                        <i class="fas fa-plus"></i>
                        Generate First Caption
                    </a>
                </div>
            <?php else: ?>
                <div class="caption-grid">
                    <?php foreach ($history as $item): ?>
                        <div class="caption-card">
                            <div class="caption-meta">
                                <?php if (!empty($item['keywords'])): ?>
                                    <span class="keywords">
                                        <?php echo htmlspecialchars(is_string($item['keywords']) ? $item['keywords'] : implode(', ', array_slice($item['keywords'] ?? [], 0, 3))); ?>
                                    </span>
                                <?php endif; ?>
                                <span><?php echo date('M j, Y \a\t g:i A', strtotime($item['timestamp'])); ?></span>
                            </div>
                            <?php 
                            $captions = [];
                            if (isset($item['captions'])) {
                                if (is_string($item['captions'])) {
                                    $decoded = json_decode($item['captions'], true);
                                    $captions = is_array($decoded) ? $decoded : [$item['captions']];
                                } elseif (is_array($item['captions'])) {
                                    $captions = $item['captions'];
                                }
                            }
                            ?>
                            <?php foreach (array_slice($captions, 0, 3) as $caption): ?>
                                <div class="caption-text"><?php echo htmlspecialchars($caption); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($captions) > 3): ?>
                                <div class="caption-meta">
                                    +<?php echo count($captions) - 3; ?> more captions
                                </div>
                            <?php endif; ?>
                            <div class="caption-actions">
                                <button class="copy-btn enhanced-copy-btn" onclick="copyToClipboard('<?php echo addslashes($captions[0] ?? ''); ?>')">
                                <i class="fas fa-copy"></i>
                                <span class="btn-text">Copy</span>
                            </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorites Tab -->
        <div id="favorites-content" class="dashboard-content" style="display: <?php echo $activeTab === 'favorites' ? 'block' : 'none'; ?>;">
            <div class="content-header">
                <h2 class="content-title">Your Favorites</h2>
                <p class="content-subtitle">Your saved captions for quick access</p>
            </div>

            <?php if (empty($favorites)): ?>
                <div class="empty-state">
                    <i class="fas fa-star"></i>
                    <h3>No Favorites Yet</h3>
                    <p>Save your favorite captions to access them quickly later.</p>
                    <a href="index.php" class="generate-btn">
                        <i class="fas fa-plus"></i>
                        Generate Captions
                    </a>
                </div>
            <?php else: ?>
                <div class="caption-grid">
                    <?php foreach ($favorites as $favorite): ?>
                        <div class="caption-card" id="favorite-<?php echo $favorite['id']; ?>">
                            <div class="caption-text"><?php echo htmlspecialchars(is_string($favorite['caption']) ? $favorite['caption'] : (is_array($favorite['caption']) ? implode(' ', $favorite['caption']) : strval($favorite['caption']))); ?></div>
                            <div class="caption-meta">
                                Saved on <?php echo date('M j, Y \a\t g:i A', strtotime($favorite['timestamp'])); ?>
                            </div>
                            <div class="caption-actions">
                                <button class="copy-btn enhanced-copy-btn" onclick="copyToClipboard('<?php echo addslashes(is_string($favorite['caption']) ? $favorite['caption'] : (is_array($favorite['caption']) ? implode(' ', $favorite['caption']) : strval($favorite['caption']))); ?>')">
                                    <i class="fas fa-copy"></i>
                                    <span class="btn-text">Copy</span>
                                </button>
                                <button class="remove-btn" onclick="removeFavorite(<?php echo $favorite['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Profile Tab -->
        <div id="profile-content" class="dashboard-content" style="display: <?php echo $activeTab === 'profile' ? 'block' : 'none'; ?>;">
            <div class="content-header">
                <h2 class="content-title">Profile Settings</h2>
                <p class="content-subtitle">Update your account information</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <div class="error-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Profile updated successfully!
                </div>
            <?php endif; ?>

            <form method="post" class="profile-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="password-section">
                    <h3 class="section-title">Change Password</h3>
                    <p class="section-subtitle">Leave blank if you don't want to change your password</p>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter new password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" name="update_profile" class="generate-btn">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <script>
        // Global tab switching function
        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.dashboard-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.dashboard-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected content
            document.getElementById(tabName + '-content').style.display = 'block';
            
            // Add active class to selected tab
            document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }

        // Profile dropdown toggle
        const profileToggle = document.getElementById('profileToggle');
        const profileMenu = document.getElementById('profileMenu');

        profileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && e.target !== profileToggle) {
                profileMenu.classList.remove('active');
            }
        });

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                const originalClass = btn.className;
                
                // Add success styling
                btn.className = 'enhanced-copy-btn success';
                btn.innerHTML = '<i class="fas fa-check"></i><span class="btn-text">Copied!</span>';
                
                setTimeout(() => {
                    btn.className = originalClass;
                    btn.innerHTML = originalHTML;
                }, 2000);
            }).catch(err => {
                console.error('Could not copy text: ', err);
                alert('Could not copy text. Please select and copy manually.');
            });
        }

        // Remove favorite
        function removeFavorite(id) {
            if (confirm('Are you sure you want to remove this from favorites?')) {
                fetch('favorites_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const card = document.getElementById(`favorite-${id}`);
                        card.style.transform = 'scale(0.95)';
                        card.style.opacity = '0.5';
                        setTimeout(() => {
                            card.remove();
                            
                            // Check if no favorites left
                            const remainingCards = document.querySelectorAll('#favorites-content .caption-card');
                            if (remainingCards.length === 0) {
                                location.reload(); // Refresh to show empty state
                            }
                        }, 300);
                    } else {
                        alert('Failed to remove from favorites');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });

        // Add loading states
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth transitions
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>