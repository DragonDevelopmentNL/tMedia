<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/config.php";

// Require login
require_login();

// Get profile user ID from URL or use current user's ID
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : get_current_user_id();
$current_user_id = get_current_user_id();

// Check if current user is following the profile user
$is_following = false;
if ($profile_user_id !== $current_user_id) {
    $check_sql = "SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $current_user_id, $profile_user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    $is_following = mysqli_stmt_num_rows($check_stmt) > 0;
}

// Handle follow/unfollow action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['follow_action'])) {
    if ($profile_user_id !== $current_user_id) {
        if ($_POST['follow_action'] === 'follow') {
            $sql = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $profile_user_id);
            mysqli_stmt_execute($stmt);
            $is_following = true;
        } else {
            $sql = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $profile_user_id);
            mysqli_stmt_execute($stmt);
            $is_following = false;
        }
    }
}

// Get profile information
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count
        FROM users u 
        WHERE u.id = ?";

try {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing query: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception("Error getting result: " . mysqli_error($conn));
    }
    
    $profile = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Get user's posts
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC";

try {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $profile_user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing query: " . mysqli_stmt_error($stmt));
    }
    
    $posts_result = mysqli_stmt_get_result($stmt);
    if (!$posts_result) {
        throw new Exception("Error getting result: " . mysqli_error($conn));
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - TBerichten</title>
    <link rel="stylesheet" href="<?php echo get_asset_url('css/style.css'); ?>">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-right: 30px;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-info {
            flex: 1;
        }
        .profile-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }
        .profile-bio {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .profile-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        .stat {
            text-align: center;
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            min-width: 100px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .stat:hover {
            background: #e9ecef;
        }
        .stat-value {
            font-weight: bold;
            font-size: 1.4em;
            color: #007bff;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        .follow-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            margin-top: 15px;
        }
        .follow-btn:hover {
            background-color: #0056b3;
        }
        .follow-btn.following {
            background-color: #6c757d;
        }
        .follow-btn.following:hover {
            background-color: #5a6268;
        }
        .posts-container {
            margin-top: 30px;
        }
        .post {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .post-content {
            margin: 10px 0;
            line-height: 1.6;
            color: #333;
        }
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .post-actions {
            display: flex;
            gap: 15px;
        }
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 20px;
            color: #666;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .action-btn:hover {
            background-color: #f0f0f0;
            color: #333;
        }
        .delete-btn {
            color: #dc3545;
        }
        .delete-btn:hover {
            background-color: #ffebee;
        }
        .post-date {
            color: #999;
            font-size: 0.9em;
        }
        .no-posts {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            color: #666;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
            color: #333;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .user-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .user-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .user-item a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }
        .user-item a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">TBerichten</div>
        <div class="nav-links">
            <a href="<?php echo get_url('index.php'); ?>">Home</a>
            <a href="<?php echo get_url('profile.php'); ?>" class="active">Profile</a>
            <a href="<?php echo get_url('settings.php'); ?>">Settings</a>
            <a href="<?php echo get_url('logout.php'); ?>">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo get_asset_url($profile['profile_image'] ?? 'images/default-profile.png'); ?>" alt="Profile" class="profile-image">
                <div class="profile-info">
                    <h2><?php echo h($profile['username']); ?></h2>
                    <p class="profile-bio"><?php echo h($profile['bio'] ?? 'No bio yet.'); ?></p>
                    <?php if ($profile_user_id !== $current_user_id): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="follow_action" value="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                            <button type="submit" class="follow-btn <?php echo $is_following ? 'following' : ''; ?>">
                                <?php echo $is_following ? 'Following' : 'Follow'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <div class="profile-stats">
                        <div class="stat" onclick="showModal('followers')">
                            <div class="stat-value"><?php echo $profile['followers_count']; ?></div>
                            <div class="stat-label">Followers</div>
                        </div>
                        <div class="stat" onclick="showModal('following')">
                            <div class="stat-value"><?php echo $profile['following_count']; ?></div>
                            <div class="stat-label">Following</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $profile['post_count']; ?></div>
                            <div class="stat-label">Posts</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="posts-container">
                <h3>Posts</h3>
                <?php if (mysqli_num_rows($posts_result) > 0): ?>
                    <?php while($post = mysqli_fetch_assoc($posts_result)): ?>
                        <div class="post" id="post-<?php echo $post['id']; ?>">
                            <div class="post-content">
                                <?php echo nl2br(h($post['content'])); ?>
                            </div>
                            <div class="post-meta">
                                <div class="post-date">
                                    <?php echo date('F j, Y g:i a', strtotime($post['created_at'])); ?>
                                </div>
                                <div class="post-actions">
                                    <button class="action-btn">
                                        <span class="like-count"><?php echo $post['like_count']; ?></span> Likes
                                    </button>
                                    <button class="action-btn">
                                        <span class="comment-count"><?php echo $post['comment_count']; ?></span> Comments
                                    </button>
                                    <?php if ($profile_user_id === $current_user_id): ?>
                                        <button class="action-btn delete-btn" onclick="deletePost(<?php echo $post['id']; ?>)">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-posts">
                        <p>No posts yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Followers Modal -->
    <div id="followersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Followers</h3>
                <button class="close-btn" onclick="closeModal('followers')">&times;</button>
            </div>
            <div class="user-list" id="followersList">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Following Modal -->
    <div id="followingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Following</h3>
                <button class="close-btn" onclick="closeModal('following')">&times;</button>
            </div>
            <div class="user-list" id="followingList">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
    function deletePost(postId) {
        if (confirm('Are you sure you want to delete this post?')) {
            const formData = new FormData();
            formData.append('post_id', postId);
            
            fetch('<?php echo get_url("api/delete_post.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const post = document.getElementById('post-' + postId);
                    if (post) {
                        post.remove();
                    }
                } else {
                    alert('Error deleting post: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error deleting post: ' + error);
            });
        }
    }

    function showModal(type) {
        const modal = document.getElementById(type + 'Modal');
        const list = document.getElementById(type + 'List');
        
        // Fetch users
        fetch('<?php echo get_url("api/get_" . type . ".php"); ?>?user_id=<?php echo $profile_user_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    list.innerHTML = data.users.map(user => `
                        <div class="user-item">
                            <img src="${user.profile_image || '<?php echo get_asset_url("images/default-profile.png"); ?>'}" alt="Profile">
                            <a href="<?php echo get_url("profile.php?id="); ?>${user.id}">${user.username}</a>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p>Error loading users.</p>';
                }
            })
            .catch(error => {
                list.innerHTML = '<p>Error loading users.</p>';
            });
        
        modal.style.display = 'block';
    }

    function closeModal(type) {
        document.getElementById(type + 'Modal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html> 