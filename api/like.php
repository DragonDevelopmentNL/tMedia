<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;

if(!$post_id){
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geen post ID opgegeven']);
    exit;
}

// Check if user already liked the post
$user_id = $_SESSION["id"];
$sql = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if(mysqli_stmt_num_rows($stmt) > 0){
        // Unlike
        $sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
            mysqli_stmt_execute($stmt);
            
            // Update post likes count
            $sql = "UPDATE posts SET likes = likes - 1 WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $post_id);
                mysqli_stmt_execute($stmt);
            }
        }
    } else {
        // Like
        $sql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
            mysqli_stmt_execute($stmt);
            
            // Update post likes count
            $sql = "UPDATE posts SET likes = likes + 1 WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $post_id);
                mysqli_stmt_execute($stmt);
            }
        }
    }
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?> 