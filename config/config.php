<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current directory path
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_path = rtrim($current_dir, '/');

// Define base URL
define('BASE_URL', $base_path);

// Define asset paths
define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');

// Function to get absolute URL
function get_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Function to get asset URL
function get_asset_url($path = '') {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Function to require login
function require_login() {
    if (!is_logged_in()) {
        header("location: " . get_url("login.php"));
        exit;
    }
}

// Function to get current user ID
function get_current_user_id() {
    return $_SESSION["id"] ?? null;
}

// Function to get current username
function get_current_username() {
    return $_SESSION["username"] ?? null;
}

// Function to sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to redirect
function redirect($path) {
    header("location: " . get_url($path));
    exit;
}
?> 