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

// Handle GET request to retrieve favorites
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $favorites = getUserFavorites($userId);
    echo json_encode(['success' => true, 'favorites' => $favorites]);
    exit;
}

// Handle POST request to add/remove favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if data is from form or JSON
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Handle remove by ID (from dashboard)
        if ($action === 'remove' && isset($_POST['id'])) {
            $favoriteId = $_POST['id'];
            $conn = getConnection();
            
            // Verify the favorite belongs to the user
            $stmt = $conn->prepare("DELETE FROM favorites WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $favoriteId, $userId);
            $result = $stmt->execute();
            
            $stmt->close();
            $conn->close();
            
            echo json_encode(['success' => $result]);
            exit;
        }
    } else {
        // Get JSON data from request body (from main page)
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['action']) || !isset($data['caption'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request data']);
            exit;
        }
        
        $caption = $data['caption'];
        $action = $data['action'];
        
        if ($action === 'add') {
            $result = addUserFavorite($userId, $caption);
        } elseif ($action === 'remove') {
            $result = removeUserFavorite($userId, $caption);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
        }
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update favorites']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);