<?php
require_once 'auth.php';
require_once 'db_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle GET request to retrieve history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $history = getUserHistory($userId);
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

// Handle POST request to save history
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action']) || $data['action'] !== 'save' || !isset($data['keywords']) || !isset($data['captions'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request data']);
        exit;
    }
    
    $keywords = $data['keywords'];
    $captions = $data['captions'];
    
    $result = saveUserHistory($userId, $keywords, $captions);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save history']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);