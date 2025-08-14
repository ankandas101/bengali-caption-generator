<?php
session_start();
require_once 'db_config.php';

// Function to register a new user
function registerUser($username, $email, $password) {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return ['error' => 'Database connection failed. Please make sure MySQL is running.'];
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    $result = $stmt->execute();
    
    if ($result) {
        $user_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return $user_id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['error' => $error];
    }
}

// Function to login a user
function loginUser($username, $password) {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return false;
    }
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Function to get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Function to get current username
function getCurrentUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

// Function to logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return true;
}

// Function to save caption to history
function saveUserHistory($userId, $keywords, $captions) {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return false;
    }
    
    // Convert captions array to JSON string
    $captionsJson = json_encode($captions);
    
    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO history (user_id, keywords, captions) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $keywords, $captionsJson);
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to get user history
function getUserHistory($userId) {
    $conn = getConnection();
    
    // Check if connection was successful
    if (!$conn) {
        return [];
    }
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id, keywords, captions, timestamp FROM history WHERE user_id = ? ORDER BY timestamp DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    
    while ($row = $result->fetch_assoc()) {
        // Convert JSON string back to array
        $row['captions'] = json_decode($row['captions'], true);
        $history[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $history;
}

// Function to add caption to favorites
function addUserFavorite($userId, $caption) {
    $conn = getConnection();
    
    // Check if caption already exists in favorites
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND caption = ?");
    $stmt->bind_param("is", $userId, $caption);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Caption already in favorites
        $stmt->close();
        $conn->close();
        return true;
    }
    
    // Insert new favorite
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, caption) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $caption);
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to remove caption from favorites
function removeUserFavorite($userId, $caption) {
    $conn = getConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND caption = ?");
    $stmt->bind_param("is", $userId, $caption);
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to get user favorites
function getUserFavorites($userId) {
    $conn = getConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id, caption, timestamp FROM favorites WHERE user_id = ? ORDER BY timestamp DESC");
    $stmt->bind_param("i", $userId);
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

// Function to check if a caption is in favorites
function isInFavorites($userId, $caption) {
    $conn = getConnection();
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND caption = ?");
    $stmt->bind_param("is", $userId, $caption);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exists = $result->num_rows > 0;
    
    $stmt->close();
    $conn->close();
    
    return $exists;
}
?>