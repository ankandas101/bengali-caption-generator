<?php
require_once 'auth.php';

// Log the user out
logoutUser();

// Redirect to login page
header('Location: login.php');
exit;