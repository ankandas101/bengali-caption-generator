<?php
session_start();
require_once 'db_config.php';

// Function to check if user is an admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Function to authenticate admin
function adminLogin($username, $password) {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return ['error' => 'Database connection failed. Please make sure MySQL is running.'];
    }
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if user is admin
            if ($user['is_admin'] == 1) {
                // Set admin session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;
                $_SESSION['is_admin'] = true;
                
                $stmt->close();
                $conn->close();
                return true;
            } else {
                $stmt->close();
                $conn->close();
                return ['error' => 'You do not have admin privileges.'];
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    return ['error' => 'Invalid username or password.'];
}

// Function to get all users
function getAllUsers() {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return ['error' => 'Database connection failed. Please make sure MySQL is running.'];
    }
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $users;
}

// Function to get all captions (from history)
function getAllCaptions() {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return ['error' => 'Database connection failed. Please make sure MySQL is running.'];
    }
    
    // Prepare statement to join history with users to get usernames
    $stmt = $conn->prepare("SELECT h.id, h.user_id, u.username, h.keywords, h.captions, h.timestamp 
                           FROM history h 
                           JOIN users u ON h.user_id = u.id 
                           ORDER BY h.timestamp DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $captions = [];
    
    while ($row = $result->fetch_assoc()) {
        // Convert JSON string back to array
        $row['captions'] = json_decode($row['captions'], true);
        $captions[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $captions;
}

// Function to get all favorites
function getAllFavorites() {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return ['error' => 'Database connection failed. Please make sure MySQL is running.'];
    }
    
    // Prepare statement to join favorites with users to get usernames
    $stmt = $conn->prepare("SELECT f.id, f.user_id, u.username, f.caption, f.timestamp 
                           FROM favorites f 
                           JOIN users u ON f.user_id = u.id 
                           ORDER BY f.timestamp DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $favorites = [];
    
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $favorites;
}

// Function to get user statistics
function getUserStats() {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return ['error' => 'Database connection failed. Please make sure MySQL is running.'];
    }
    
    $stats = [
        'total_users' => 0,
        'total_captions' => 0,
        'total_favorites' => 0,
        'recent_users' => [],
        'popular_keywords' => []
    ];
    
    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_users'] = $row['count'];
    }
    
    // Get total captions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM history");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_captions'] = $row['count'];
    }
    
    // Get total favorites
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM favorites");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_favorites'] = $row['count'];
    }
    
    // Get recent users (last 5)
    $stmt = $conn->prepare("SELECT id, username, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stats['recent_users'][] = $row;
    }
    
    // Get popular keywords (this is a simplified approach)
    $stmt = $conn->prepare("SELECT keywords FROM history");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $keywordCounts = [];
    while ($row = $result->fetch_assoc()) {
        $keywords = explode(',', $row['keywords']);
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword)) {
                if (isset($keywordCounts[$keyword])) {
                    $keywordCounts[$keyword]++;
                } else {
                    $keywordCounts[$keyword] = 1;
                }
            }
        }
    }
    
    // Sort by count and get top 10
    arsort($keywordCounts);
    $stats['popular_keywords'] = array_slice($keywordCounts, 0, 10, true);
    
    $stmt->close();
    $conn->close();
    
    return $stats;
}
?>