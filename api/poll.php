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
$poll_id = $_GET['poll_id'] ?? null;

switch($action) {
    case 'vote':
        if(!isset($_POST['option_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Option ID is required']);
            exit;
        }

        $option_id = $_POST['option_id'];
        
        // Check if user already voted
        $sql = "SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $poll_id, $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'You have already voted on this poll']);
                exit;
            }
        }

        // Add vote
        $sql = "INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iii", $poll_id, $option_id, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                // Update option vote count
                $sql = "UPDATE poll_options SET votes = votes + 1 WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $option_id);
                    mysqli_stmt_execute($stmt);
                }
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to record vote']);
            }
        }
        break;

    case 'results':
        if(!$poll_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Poll ID is required']);
            exit;
        }

        // Get poll results
        $sql = "SELECT po.id, po.option_text, po.votes, 
                (SELECT COUNT(*) FROM poll_votes WHERE poll_id = ?) as total_votes
                FROM poll_options po
                WHERE po.poll_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $poll_id, $poll_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $options = [];
            while($row = mysqli_fetch_assoc($result)) {
                $percentage = $row['total_votes'] > 0 ? 
                    round(($row['votes'] / $row['total_votes']) * 100) : 0;
                $options[] = [
                    'id' => $row['id'],
                    'text' => $row['option_text'],
                    'votes' => $row['votes'],
                    'percentage' => $percentage
                ];
            }
            echo json_encode(['options' => $options]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
} 