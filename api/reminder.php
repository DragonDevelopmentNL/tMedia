<?php
session_start();
require_once "../config/database.php";
require_once "../config/config.php";

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$reminder_id = $_GET['reminder_id'] ?? null;

switch($action) {
    case 'list':
        // Get user's active reminders
        $sql = "SELECT r.*, p.content as post_content 
                FROM reminders r 
                JOIN posts p ON r.post_id = p.id 
                WHERE r.user_id = ? AND r.is_completed = 0 
                ORDER BY r.reminder_time ASC";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $reminders = [];
            while($row = mysqli_fetch_assoc($result)) {
                $reminders[] = [
                    'id' => $row['id'],
                    'message' => $row['message'],
                    'reminder_time' => $row['reminder_time'],
                    'post_content' => $row['post_content']
                ];
            }
            echo json_encode(['reminders' => $reminders]);
        }
        break;

    case 'complete':
        if(!$reminder_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Reminder ID is required']);
            exit;
        }

        // Mark reminder as completed
        $sql = "UPDATE reminders SET is_completed = 1 WHERE id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $reminder_id, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to complete reminder']);
            }
        }
        break;

    case 'delete':
        if(!$reminder_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Reminder ID is required']);
            exit;
        }

        // Delete reminder
        $sql = "DELETE FROM reminders WHERE id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $reminder_id, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete reminder']);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
} 