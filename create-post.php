<?php
session_start();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/config.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: " . get_url("login.php"));
    exit;
}

// Get user information
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];

$post_err = "";
$content = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate post content
    if(empty(trim($_POST["content"]))) {
        $post_err = "Please enter some content.";
    } else {
        $content = trim($_POST["content"]);
        
        // Check if content starts with a command
        if(strpos($content, '/') === 0) {
            require_once __DIR__ . "/api/commands.php";
            $commandHandler = new CommandHandler($conn, $user_id);
            $command_result = $commandHandler->processCommand($content);
            
            if($command_result !== null) {
                $content = $command_result;
            }
        }
        
        // Insert post into database
        $sql = "INSERT INTO posts (user_id, content) VALUES (?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_id, $content);
            
            if(mysqli_stmt_execute($stmt)) {
                header("location: " . get_url("index.php"));
                exit;
            } else {
                $post_err = "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - TBerichten</title>
    <link rel="stylesheet" href="<?php echo get_asset_url('css/style.css'); ?>">
    <style>
        .command-help {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .command-help code {
            background: #e9ecef;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">TBerichten</div>
        <div class="nav-links">
            <a href="<?php echo get_url('index.php'); ?>">Home</a>
            <a href="<?php echo get_url('profile.php'); ?>">Profile</a>
            <a href="<?php echo get_url('create-post.php'); ?>" class="active">New Post</a>
            <a href="<?php echo get_url('settings.php'); ?>">Settings</a>
            <a href="<?php echo get_url('logout.php'); ?>">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="create-post">
            <h2>Create New Post</h2>
            
            <?php if(!empty($post_err)): ?>
                <div class="alert alert-danger"><?php echo $post_err; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="postForm">
                <div class="form-group">
                    <label for="content">What's on your mind?</label>
                    <textarea name="content" id="content" class="form-control" rows="4" required><?php echo htmlspecialchars($content); ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Post</button>
                </div>
            </form>

            <div class="command-help">
                <h3>Available Commands:</h3>
                <ul>
                    <li><code>/help</code> - Show this help message</li>
                    <li><code>/weather [city]</code> - Get weather information</li>
                    <li><code>/poll "question" option1 option2 ...</code> - Create a poll</li>
                    <li><code>/remind [time] [message]</code> - Set a reminder</li>
                    <li><code>/gif [query]</code> - Search for a GIF</li>
                    <li><code>/translate [text]</code> - Translate text</li>
                </ul>
            </div>
        </div>
    </main>
</body>
</html> 