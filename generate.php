<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include authentication and database functions
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
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$userId = $isLoggedIn ? getCurrentUserId() : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['keyword'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keyword is required']);
    exit;
}

$keyword = $data['keyword'];

// API Key Management Functions
function getAvailableApiKey() {
    global $pdo;
    
    try {
        // Get active API keys ordered by usage (least used first)
        $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE is_active = 1 ORDER BY last_error_count ASC, usage_count ASC LIMIT 1");
        $stmt->execute();
        $api_key = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$api_key) {
            throw new Exception('No active API keys found in database');
        }
        
        return $api_key;
    } catch (Exception $e) {
        // Fallback to environment variable or hardcoded key
        $fallback_key = getenv('OPENROUTER_API_KEY') ?: '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';
        return [
            'id' => 0,
            'api_key' => $fallback_key,
            'description' => 'Fallback Key',
            'is_active' => 1,
            'usage_count' => 0,
            'last_error_count' => 0
        ];
    }
}

function updateApiKeyUsage($api_key_id, $success = true, $error_message = null) {
    global $pdo;
    
    if ($api_key_id == 0) return; // Skip for fallback key
    
    try {
        if ($success) {
            $stmt = $pdo->prepare("UPDATE api_keys SET usage_count = usage_count + 1, last_used = NOW(), last_error_count = 0, last_error_message = NULL WHERE id = ?");
            $stmt->execute([$api_key_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE api_keys SET last_error_count = last_error_count + 1, last_error_message = ? WHERE id = ?");
            $stmt->execute([$error_message, $api_key_id]);
        }
    } catch (Exception $e) {
        error_log("Failed to update API key usage: " . $e->getMessage());
    }
}

function markApiKeyInactive($api_key_id, $error_message) {
    global $pdo;
    
    if ($api_key_id == 0) return; // Skip for fallback key
    
    try {
        $stmt = $pdo->prepare("UPDATE api_keys SET is_active = 0, last_error_message = ? WHERE id = ?");
        $stmt->execute([$error_message, $api_key_id]);
    } catch (Exception $e) {
        error_log("Failed to mark API key inactive: " . $e->getMessage());
    }
}

function callOpenRouterAPI($prompt, $api_key) {
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'HTTP-Referer: /captionmaker/',
        'X-Title: Bengali Caption Generator'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $postData = [
        'model' => 'openai/gpt-4.1-mini',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt . " Generate 4 different variations of this caption. Each caption should be on a new line."
                    ]
                ]
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $curlError
    ];
}

// Split keywords if multiple are provided
$keywords = array_map('trim', explode(',', $keyword));

// Create a more detailed prompt based on the number of keywords
if (count($keywords) > 1) {
    $prompt = "Generate a short (1-2 lines), emotional, and engaging Facebook-style caption in Bengali that combines these themes: " . implode(', ', $keywords) . ". The caption should be catchy, creative, and relatable for young Bangladeshi audiences. Use poetic or romantic tones if suitable. Include emojis to enhance expression. Make sure to incorporate all the themes naturally.";
} else {
    $prompt = "Generate a short (1-2 lines), emotional, and engaging Facebook-style caption in Bengali about {$keyword}. The caption should be catchy, creative, and relatable for young Bangladeshi audiences. Use poetic or romantic tones if suitable. Include emojis to enhance expression.";
}

// Get available API key
$api_key_data = getAvailableApiKey();
$max_retries = 3;
$attempt = 0;

while ($attempt < $max_retries) {
    $attempt++;
    
    // Call API with current key
    $api_result = callOpenRouterAPI($prompt, $api_key_data['api_key']);
    
    if ($api_result['error']) {
        // Curl error
        $error_message = "Curl error: " . $api_result['error'];
        updateApiKeyUsage($api_key_data['id'], false, $error_message);
        
        if ($attempt >= $max_retries) {
            http_response_code(500);
            echo json_encode(['error' => $error_message]);
            exit;
        }
        
        // Try next API key
        $api_key_data = getAvailableApiKey();
        continue;
    }
    
    $response = $api_result['response'];
    $httpCode = $api_result['httpCode'];
    
    // Log the response for debugging
    error_log("API Response (Attempt {$attempt}): " . $response);
    error_log("HTTP Code: " . $httpCode);
    
    if ($httpCode === 200) {
        // Success - update usage and break
        updateApiKeyUsage($api_key_data['id'], true);
        break;
    } else {
        // API error
        $errorData = json_decode($response, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'Failed to generate caption';
        
        // Mark key as inactive for specific authentication errors
        if ($httpCode === 401 || $httpCode === 403) {
            markApiKeyInactive($api_key_data['id'], $errorMessage);
        } else {
            updateApiKeyUsage($api_key_data['id'], false, $errorMessage);
        }
        
        if ($attempt >= $max_retries) {
            http_response_code(500);
            echo json_encode(['error' => $errorMessage]);
            exit;
        }
        
        // Try next API key
        $api_key_data = getAvailableApiKey();
    }
}

$result = json_decode($response, true);

// Log the decoded result for debugging
error_log("Decoded Result: " . print_r($result, true));

// Check for the response in the correct format
if (!isset($result['choices'][0]['message']['content'])) {
    // Try alternative response format
    if (isset($result['choices'][0]['text'])) {
        $captions = explode("\n", $result['choices'][0]['text']);
        $captions = array_filter($captions, 'trim'); // Remove empty lines
        $captions = array_slice($captions, 0, 4); // Take only first 4 captions
    } else {
        error_log("API Response Structure: " . print_r($result, true));
        http_response_code(500);
        echo json_encode(['error' => 'Invalid response format from API: ' . json_encode($result)]);
        exit;
    }
} else {
    $captions = explode("\n", $result['choices'][0]['message']['content']);
    $captions = array_filter($captions, 'trim'); // Remove empty lines
    $captions = array_slice($captions, 0, 4); // Take only first 4 captions
}

// Ensure we're sending a valid JSON response
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to encode response: ' . json_last_error_msg()]);
    exit;
}

// Save to history if user is logged in
if ($isLoggedIn && $userId) {
    saveUserHistory($userId, $keyword, $captions);
}

echo json_encode(['captions' => $captions]);
?>