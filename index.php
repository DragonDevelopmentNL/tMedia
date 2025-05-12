<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
$logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TBerichten - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">TBerichten</div>
        <div class="nav-links">
            <?php if($logged_in): ?>
                <a href="profile.php">My Profile</a>
                <a href="create-post.php">New Post</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <?php if($logged_in): ?>
            <div class="feed">
                <?php
                // Fetch posts from database
                $sql = "SELECT p.*, u.username, u.profile_image 
                        FROM posts p 
                        JOIN users u ON p.user_id = u.id 
                        ORDER BY p.created_at DESC";
                $result = mysqli_query($conn, $sql);

                if(mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="post">';
                        echo '<div class="post-header">';
                        echo '<img src="' . ($row['profile_image'] ?? 'assets/images/default-avatar.png') . '" alt="Profile" class="post-avatar">';
                        echo '<span class="post-username">' . htmlspecialchars($row['username']) . '</span>';
                        echo '</div>';
                        echo '<div class="post-content">' . htmlspecialchars($row['content']) . '</div>';
                        echo '<div class="post-actions">';
                        echo '<button class="like-btn" data-post-id="' . $row['id'] . '">❤️ ' . $row['likes'] . '</button>';
                        echo '<button class="share-btn" data-post-id="' . $row['id'] . '">📤 Share</button>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-posts">No posts available yet.</p>';
                }
                ?>
            </div>
        <?php else: ?>
            <div class="welcome-section">
                <h1>Welcome to TBerichten</h1>
                <p>Log in or create an account to view and share posts!</p>
                <div class="welcome-buttons">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html> 