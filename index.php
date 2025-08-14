<?php
require_once 'auth.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$username = '';

if ($isLoggedIn) {
    $username = $_SESSION['username'] ?? 'User';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bengali Caption Generator</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header-container">
            <h1>Bengali Caption Generator</h1>
            <div class="user-menu">
                <?php if ($isLoggedIn): ?>
                    <span class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <a href="admin_dashboard.php" class="auth-link">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="user_dashboard.php" class="auth-link">My Dashboard</a>
                    <a href="logout.php" class="auth-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="auth-link">Login</a>
                    <a href="register.php" class="auth-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="input-section">
            <div class="keyword-input">
                <input type="text" id="keyword" placeholder="ক্যাপশন টপিক লিখুন অথবা নিচে থেকে বেছে নিন এবং ক্যাপশন জেনারেট বাটনে ক্লিক করুন">
            </div>
            
            <div class="keyword-buttons">
                <button class="keyword-btn" data-keyword="Love">Love</button>
                <button class="keyword-btn" data-keyword="Friendship">Friendship</button>
                <button class="keyword-btn" data-keyword="Life">Life</button>
                <button class="keyword-btn" data-keyword="Success">Success</button>
                <button class="keyword-btn" data-keyword="Motivation">Motivation</button>
                <button class="keyword-btn" data-keyword="Happiness">Happiness</button>
                <button class="keyword-btn" data-keyword="Dream">Dream</button>
                <button class="keyword-btn" data-keyword="Hope">Hope</button>
                <button class="keyword-btn" data-keyword="Travel">Travel</button>
                <button class="keyword-btn" data-keyword="Family">Family</button>
                <button class="keyword-btn" data-keyword="Nature">Nature</button>
                <button class="keyword-btn" data-keyword="Peace">Peace</button>
            </div>
            
            <div id="selectedKeywordsList" class="selected-keywords"></div>
            
            <button id="generateBtn" class="generate-btn">Generate</button>
        </div>

        <div id="loading" class="loading hidden">
            <div class="spinner"></div>
            <p>Generating captions...</p>
        </div>

        <div id="result" class="result hidden">
            <div id="captionsContainer" class="captions-container"></div>
            <button id="generateMoreBtn" class="generate-more-btn hidden">Generate More</button>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="tabs-container">
            <div class="tabs">
                <button id="historyTab" class="tab-btn active">History</button>
                <button id="favoritesTab" class="tab-btn">Favorites</button>
            </div>
            <div id="historyContainer" class="tab-content"></div>
            <div id="favoritesContainer" class="tab-content hidden"></div>
        </div>
        <?php else: ?>
        <div class="login-prompt">
            <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to access your caption history and favorites.</p>
        </div>
        <?php endif; ?>
        
        <div class="version">| <a href="https://fb.com/ankandas.fb" target="_blank"> &copy; Ankan Das </a> |</div>
    </div>
    
    <script>
        // Pass PHP variables to JavaScript
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const currentUsername = '<?php echo $isLoggedIn ? htmlspecialchars($username) : ''; ?>';
    </script>
    <script src="script.js"></script>
    <script src="animations.js"></script>
</body>
</html>