<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/config.php";

// Require login
require_login();

// Get user ID from query parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Get following
$sql = "SELECT u.id, u.username, u.profile_image 
        FROM users u 
        JOIN follows f ON u.id = f.following_id 
        WHERE f.follower_id = ?";

try {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing query: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception("Error getting result: " . mysqli_error($conn));
    }
    
    $users = [];
    while ($user = mysqli_fetch_assoc($result)) {
        $users[] = $user;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 