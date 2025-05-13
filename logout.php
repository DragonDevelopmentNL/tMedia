<?php
require_once "config/database.php";
require_once "config/config.php";

// Update last login time if user is logged in
if(is_logged_in()) {
    // Check if last_login column exists
    $check_column = "SHOW COLUMNS FROM users LIKE 'last_login'";
    $result = mysqli_query($conn, $check_column);
    
    if(mysqli_num_rows($result) > 0) {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if(isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
redirect("login.php");
?> 