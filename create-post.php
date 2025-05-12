<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $content = trim($_POST["content"]);
    
    // Validate content
    if(empty($content)){
        $error = "Please enter some content for your post.";
    } elseif(strlen($content) > 200){
        $error = "Post cannot be longer than 200 characters.";
    } else{
        $sql = "INSERT INTO posts (user_id, content) VALUES (?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "is", $_SESSION["id"], $content);
            if(mysqli_stmt_execute($stmt)){
                $success = "Post created successfully!";
                $content = ""; // Clear the form
            } else{
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - TBerichten</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">TBerichten</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="profile.php">My Profile</a>
            <a href="create-post.php">New Post</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="create-post-container">
            <h1>Create New Post</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="content">What's on your mind?</label>
                    <textarea name="content" id="content" class="form-control" rows="4" maxlength="200" required><?php echo htmlspecialchars($content ?? ''); ?></textarea>
                    <div class="char-counter">
                        <span id="char-count">0</span>/200 characters
                    </div>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Post">
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('content');
            const charCount = document.getElementById('char-count');
            
            textarea.addEventListener('input', function() {
                const remaining = this.value.length;
                charCount.textContent = remaining;
                
                if(remaining > 200) {
                    charCount.style.color = 'red';
                } else {
                    charCount.style.color = '';
                }
            });
        });
    </script>
</body>
</html> 