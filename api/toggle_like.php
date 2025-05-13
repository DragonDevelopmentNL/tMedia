<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/config.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// Check if post_id is provided
if(!isset($_POST["post_id"])) {
    http_response_code(400);
    echo json_encode(["error" => "No post ID provided"]);
    exit;
}

$post_id = $_POST["post_id"];
$user_id = $_SESSION["id"];

// Check if post exists
$sql = "SELECT id FROM posts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Post not found"]);
    exit;
}

// Check if user already liked the post
$sql = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

$liked = mysqli_stmt_num_rows($stmt) > 0;

if($liked) {
    // Unlike
    $sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    
    if(mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => true,
            "liked" => false
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to unlike post"]);
    }
} else {
    // Like
    $sql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    
    if(mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => true,
            "liked" => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to like post"]);
    }
}

mysqli_stmt_close($stmt);
?> 