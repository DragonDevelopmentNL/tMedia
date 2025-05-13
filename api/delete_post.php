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

// First check if the post belongs to the user
$sql = "SELECT user_id FROM posts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);

if(!$post || $post["user_id"] != $user_id) {
    http_response_code(403);
    echo json_encode(["error" => "Not authorized to delete this post"]);
    exit;
}

// Delete the post
$sql = "DELETE FROM posts WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);

if(mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete post"]);
}

mysqli_stmt_close($stmt);
?> 