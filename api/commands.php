<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/config.php";

class CommandHandler {
    private $conn;
    private $user_id;
    private $post_id;

    public function __construct($conn, $user_id, $post_id = 0) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->post_id = $post_id;
    }

    public function processCommand($content) {
        $parts = explode(' ', $content);
        $command = strtolower($parts[0]);
        
        switch($command) {
            case '/help':
                return $this->showHelp();
            case '/weather':
                return $this->getWeather($parts);
            case '/poll':
                return $this->createPoll($parts);
            case '/remind':
                return $this->setReminder($parts);
            case '/gif':
                return $this->searchGif($parts);
            case '/translate':
                return $this->translateText($parts);
            default:
                return null;
        }
    }

    private function showHelp() {
        return "Available commands:\n" .
               "/help - Show this help message\n" .
               "/weather [city] - Get weather information\n" .
               "/poll \"question\" option1 option2 ... - Create a poll\n" .
               "/remind [time] [message] - Set a reminder\n" .
               "/gif [query] - Search for a GIF\n" .
               "/translate [text] - Translate text";
    }

    private function getWeather($parts) {
        if(count($parts) < 2) {
            return "Please specify a city. Example: /weather Amsterdam";
        }
        $city = $parts[1];
        return "Weather information for {$city}:\nTemperature: 20°C\nCondition: Sunny";
    }

    private function createPoll($parts) {
        if(count($parts) < 3) {
            return "Please provide a question and at least one option. Example: /poll \"What's your favorite color?\" Red Blue Green";
        }
        
        $question = trim($parts[1], '"');
        $options = array_slice($parts, 2);
        
        $sql = "INSERT INTO polls (user_id, question) VALUES (?, ?)";
        if($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $this->user_id, $question);
            mysqli_stmt_execute($stmt);
            $poll_id = mysqli_insert_id($this->conn);
            
            foreach($options as $option) {
                $sql = "INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)";
                if($stmt = mysqli_prepare($this->conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "is", $poll_id, $option);
                    mysqli_stmt_execute($stmt);
                }
            }
            
            return "Poll created: {$question}\nOptions: " . implode(", ", $options);
        }
        
        return "Failed to create poll. Please try again.";
    }

    private function setReminder($parts) {
        if(count($parts) < 3) {
            return "Please specify time and message. Example: /remind 2h Buy groceries";
        }
        
        $time = $parts[1];
        $message = implode(' ', array_slice($parts, 2));
        
        $sql = "INSERT INTO reminders (user_id, post_id, message, reminder_time) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ?))";
        if($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiss", $this->user_id, $this->post_id, $message, $time);
            if(mysqli_stmt_execute($stmt)) {
                return "Reminder set for {$time} from now: {$message}";
            }
        }
        
        return "Failed to set reminder. Please try again.";
    }

    private function searchGif($parts) {
        if(count($parts) < 2) {
            return "Please specify a search term. Example: /gif cat";
        }
        $query = implode(' ', array_slice($parts, 1));
        return "GIF search results for: {$query}\n[GIF placeholder]";
    }

    private function translateText($parts) {
        if(count($parts) < 2) {
            return "Please specify text to translate. Example: /translate Hello world";
        }
        $text = implode(' ', array_slice($parts, 1));
        return "Translation of: {$text}\n[Translation placeholder]";
    }
} 