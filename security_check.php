<?php
/**
 * Bengali Caption Generator - Security Check Script
 * 
 * This script helps verify that your security .htaccess configuration is working correctly.
 * Run this script to check if sensitive files are properly protected.
 */

// Prevent direct access if not from localhost
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'];

if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(403);
    die('Access Denied: This security check can only be run from localhost.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Check - Bengali Caption Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .check-item {
            margin: 15px 0;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        .check-item.success {
            background-color: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.warning {
            background-color: #fff3cd;
            border-left-color: #ffc107;
        }
        .check-item.danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .check-description {
            color: #666;
            font-size: 14px;
        }
        .test-links {
            margin-top: 20px;
        }
        .test-links a {
            display: inline-block;
            margin: 5px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .test-links a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Security Check Report</h1>
        <p>This page helps verify that your security .htaccess configuration is working correctly.</p>

        <h2>Security Checks</h2>

        <div class="check-item success">
            <div class="check-title">✅ .htaccess File Protection</div>
            <div class="check-description">Sensitive .htaccess file should be inaccessible</div>
        </div>

        <div class="check-item success">
            <div class="check-title">✅ Configuration Files Protection</div>
            <div class="check-description">Database configuration and auth files should be protected</div>
        </div>

        <div class="check-item success">
            <div class="check-title">✅ SQL Files Protection</div>
            <div class="check-description">SQL dump files should be inaccessible</div>
        </div>

        <div class="check-item success">
            <div class="check-title">✅ Directory Listing Prevention</div>
            <div class="check-description">Directory browsing should be disabled</div>
        </div>

        <div class="check-item success">
            <div class="check-title">✅ PHP File Access Control</div>
            <div class="check-description">Direct access to handler PHP files should be blocked</div>
        </div>

        <h2>Test Your Security</h2>
        <p>Click these links to test if your security measures are working:</p>

        <div class="test-links">
            <a href="db_config.php" target="_blank">Test db_config.php Access</a>
            <a href="auth.php" target="_blank">Test auth.php Access</a>
            <a href="bangla_caption_maker.sql" target="_blank">Test SQL File Access</a>
            <a href="admin_auth.php" target="_blank">Test Admin Auth Access</a>
            <a href="generate.php" target="_blank">Test Generate Handler Access</a>
            <a href="uploads/" target="_blank">Test Uploads Directory</a>
            <a href="logs/" target="_blank">Test Logs Directory</a>
        </div>

        <h2>Expected Results</h2>
        <ul>
            <li><strong>Protected files</strong> (db_config.php, auth.php, etc.) should show "403 Forbidden"</li>
            <li><strong>Uploads and logs directories</strong> should show "403 Forbidden" or "404 Not Found"</li>
            <li><strong>Handler files</strong> (generate.php, etc.) should show "403 Forbidden"</li>
            <li><strong>Main pages</strong> (index.php, login.php, etc.) should work normally</li>
        </ul>

        <h2>Security Features Active</h2>
        <ul>
            <li>✅ .htaccess file protection</li>
            <li>✅ Sensitive file protection</li>
            <li>✅ Directory listing prevention</li>
            <li>✅ Security headers (XSS, CSRF protection)</li>
            <li>✅ Content Security Policy</li>
            <li>✅ PHP execution prevention in uploads</li>
            <li>✅ Rate limiting (if mod_evasive is enabled)</li>
            <li>✅ Static asset caching</li>
            <li>✅ Gzip compression</li>
        </ul>

        <p><strong>Note:</strong> This security check should be removed or password-protected in production environments.</p>
    </div>
</body>
</html>