<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$email = "";
$bio = "";
$profile_image = "";
$error = "";
$success = "";

// Get user data
$sql = "SELECT email, bio, profile_image FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $email, $bio, $profile_image);
        mysqli_stmt_fetch($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["update_profile"])){
        $new_username = trim($_POST["username"]);
        $new_email = trim($_POST["email"]);
        $new_bio = trim($_POST["bio"]);
        
        // Validate username
        if(empty($new_username)){
            $error = "Please enter username.";
        } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)){
            $error = "Username can only contain letters, numbers, and underscores.";
        } else{
            // Check if username is taken
            $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "si", $new_username, $user_id);
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    if(mysqli_stmt_num_rows($stmt) > 0){
                        $error = "This username is already taken.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Validate email
        if(empty($new_email)){
            $error = "Please enter an email address.";
        } elseif(!filter_var($new_email, FILTER_VALIDATE_EMAIL)){
            $error = "Please enter a valid email address.";
        }
        
        if(empty($error)){
            $sql = "UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssi", $new_username, $new_email, $new_bio, $user_id);
                if(mysqli_stmt_execute($stmt)){
                    $_SESSION["username"] = $new_username;
                    $username = $new_username;
                    $email = $new_email;
                    $bio = $new_bio;
                    $success = "Profile updated successfully!";
                } else{
                    $error = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif(isset($_POST["change_password"])){
        $current_password = trim($_POST["current_password"]);
        $new_password = trim($_POST["new_password"]);
        $confirm_password = trim($_POST["confirm_password"]);
        
        // Validate current password
        $sql = "SELECT password FROM users WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $hashed_password);
                mysqli_stmt_fetch($stmt);
                
                if(!password_verify($current_password, $hashed_password)){
                    $error = "Current password is incorrect.";
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        // Validate new password
        if(empty($new_password)){
            $error = "Please enter a new password.";
        } elseif(strlen($new_password) < 6){
            $error = "Password must have at least 6 characters.";
        }
        
        // Validate confirm password
        if(empty($confirm_password)){
            $error = "Please confirm the new password.";
        } elseif($new_password != $confirm_password){
            $error = "Passwords do not match.";
        }
        
        if(empty($error)){
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "si", $param_password, $user_id);
                if(mysqli_stmt_execute($stmt)){
                    $success = "Password changed successfully!";
                } else{
                    $error = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif(isset($_FILES["profile_image"])){
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if($check === false){
            $error = "File is not an image.";
        }
        
        // Check file size
        if($_FILES["profile_image"]["size"] > 5000000){
            $error = "File is too large. Maximum size is 5MB.";
        }
        
        // Allow certain file formats
        if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif"){
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        
        if(empty($error)){
            if(move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)){
                $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "si", $new_filename, $user_id);
                    if(mysqli_stmt_execute($stmt)){
                        $profile_image = $new_filename;
                        $success = "Profile image updated successfully!";
                    } else{
                        $error = "Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else{
                $error = "Failed to upload image.";
            }
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
    <title>My Profile - TBerichten</title>
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
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image-container">
                    <?php if(!empty($profile_image)): ?>
                        <img src="uploads/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image-placeholder">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="profile-image-form">
                        <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display: none;">
                        <label for="profile_image" class="btn btn-secondary">Change Photo</label>
                    </form>
                </div>
                <h1><?php echo htmlspecialchars($username); ?>'s Profile</h1>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="profile-sections">
                <div class="profile-section">
                    <h2>Profile Information</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
                        </div>
                        <div class="form-group">
                            <input type="submit" name="update_profile" class="btn btn-primary" value="Update Profile">
                        </div>
                    </form>
                </div>

                <div class="profile-section">
                    <h2>Change Password</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="submit" name="change_password" class="btn btn-primary" value="Change Password">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html> 