<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/config.php";

// Require login
require_login();

$user_id = get_current_user_id();
$username = get_current_username();
$error_msg = "";
$success_msg = "";

// Handle post submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $sql = "INSERT INTO posts (user_id, content) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $content);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Post created successfully!";
            // Redirect to refresh the page
            header("Location: " . get_url("index.php"));
            exit;
        } else {
            $error_msg = "Error creating post.";
        }
    } else {
        $error_msg = "Please enter some content.";
    }
}

// Get all posts with user info and like/comment counts
$sql = "SELECT p.*, u.username, u.profile_image,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC";

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
    <title>Home - TBerichten</title>
    <link rel="stylesheet" href="<?php echo get_asset_url('css/style.css'); ?>">
    <style>
        .post-form {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post-form textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
            font-size: 16px;
            font-family: inherit;
        }
        .post-form textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .post-form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .post-form button:hover {
            background-color: #0056b3;
        }
        .post {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .post-user {
            font-weight: 500;
            color: #333;
            text-decoration: none;
        }
        .post-user:hover {
            text-decoration: underline;
        }
        .post-date {
            color: #666;
            font-size: 0.9em;
            margin-left: auto;
        }
        .post-content {
            margin: 10px 0;
            line-height: 1.6;
            color: #333;
        }
        .post-actions {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
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
        .action-btn.liked {
            color: #e91e63;
        }
        .action-btn.liked:hover {
            background-color: #fce4ec;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">TBerichten</div>
        <div class="nav-links">
            <a href="<?php echo get_url('index.php'); ?>" class="active">Home</a>
            <a href="<?php echo get_url('profile.php'); ?>">Profile</a>
            <a href="<?php echo get_url('settings.php'); ?>">Settings</a>
            <a href="<?php echo get_url('logout.php'); ?>">Logout</a>
        </div>
    </nav>

    <main class="container">
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <div class="post-form">
            <form method="post" id="postForm">
                <textarea name="content" id="content" placeholder="What's on your mind?" required></textarea>
                <button type="submit">Post</button>
            </form>
        </div>

        <?php while($post = mysqli_fetch_assoc($result)): ?>
            <div class="post" id="post-<?php echo $post['id']; ?>">
                <div class="post-header">
                    <img src="<?php echo get_asset_url($post['profile_image'] ?? 'images/default-profile.png'); ?>" alt="Profile" class="post-avatar">
                    <a href="<?php echo get_url('profile.php?id=' . $post['user_id']); ?>" class="post-user"><?php echo h($post['username']); ?></a>
                    <span class="post-date"><?php echo date('F j, Y g:i a', strtotime($post['created_at'])); ?></span>
                </div>
                <div class="post-content">
                    <?php echo nl2br(h($post['content'])); ?>
                </div>
                <div class="post-actions">
                    <button class="action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>)">
                        <span class="like-count"><?php echo $post['like_count']; ?></span> Likes
                    </button>
                    <button class="action-btn">
                        <span class="comment-count"><?php echo $post['comment_count']; ?></span> Comments
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    </main>

    <script>
    // Like functionality
    function toggleLike(postId) {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        fetch('<?php echo get_url("api/toggle_like.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const post = document.getElementById('post-' + postId);
                const likeBtn = post.querySelector('.action-btn');
                const likeCount = post.querySelector('.like-count');
                
                if (data.liked) {
                    likeBtn.classList.add('liked');
                    likeCount.textContent = parseInt(likeCount.textContent) + 1;
                } else {
                    likeBtn.classList.remove('liked');
                    likeCount.textContent = parseInt(likeCount.textContent) - 1;
                }
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
    </script>
</body>
</html> 