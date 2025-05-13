<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/config.php";

// Require login
require_login();

$user_id = get_current_user_id();
$username = get_current_username();
$success_msg = "";
$error_msg = "";

// Get current user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $bio = trim($_POST['bio']);
        
        $sql = "UPDATE users SET bio = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $bio, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Profile updated successfully!";
            $user['bio'] = $bio;
        } else {
            $error_msg = "Error updating profile.";
        }
    }
    
    if (isset($_POST['update_account'])) {
        $new_username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error_msg = "Current password is incorrect.";
        } else {
            $updates = [];
            $types = "";
            $params = [];
            
            // Check if username is changed
            if ($new_username !== $user['username']) {
                // Check if username is already taken
                $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $new_username, $user_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $error_msg = "Username is already taken.";
                } else {
                    $updates[] = "username = ?";
                    $types .= "s";
                    $params[] = $new_username;
                }
            }
            
            // Check if password is changed
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error_msg = "New passwords do not match.";
                } else {
                    $updates[] = "password = ?";
                    $types .= "s";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($error_msg) && !empty($updates)) {
                $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                $types .= "i";
                $params[] = $user_id;
                
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Account updated successfully!";
                    $user['username'] = $new_username;
                } else {
                    $error_msg = "Error updating account.";
                }
            }
        }
    }
    
    if (isset($_POST['delete_account'])) {
        $confirm_password = $_POST['confirm_password'];
        
        if (!password_verify($confirm_password, $user['password'])) {
            $error_msg = "Password is incorrect.";
        } else {
            // Delete user's posts, likes, comments, etc.
            $tables = ['posts', 'likes', 'comments', 'follows'];
            foreach ($tables as $table) {
                $sql = "DELETE FROM $table WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
            }
            
            // Delete user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                session_destroy();
                header("location: " . get_url("login.php"));
                exit;
            } else {
                $error_msg = "Error deleting account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TBerichten</title>
    <link rel="stylesheet" href="<?php echo get_asset_url('css/style.css'); ?>">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .settings-section {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .settings-section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">TBerichten</div>
        <div class="nav-links">
            <a href="<?php echo get_url('index.php'); ?>">Home</a>
            <a href="<?php echo get_url('profile.php'); ?>">Profile</a>
            <a href="<?php echo get_url('settings.php'); ?>" class="active">Settings</a>
            <a href="<?php echo get_url('logout.php'); ?>">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="settings-container">
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Profile Settings -->
            <div class="settings-section">
                <h2>Profile Settings</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea name="bio" id="bio" class="form-control"><?php echo h($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <!-- Account Settings -->
            <div class="settings-section">
                <h2>Account Settings</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?php echo h($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" id="new_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                    </div>
                    <button type="submit" name="update_account" class="btn btn-primary">Update Account</button>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="settings-section">
                <h2>Delete Account</h2>
                <p>Warning: This action cannot be undone. All your posts, likes, and comments will be permanently deleted.</p>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    <div class="form-group">
                        <label for="delete_confirm_password">Enter your password to confirm</label>
                        <input type="password" name="confirm_password" id="delete_confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="delete_account" class="btn btn-danger">Delete Account</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html> 