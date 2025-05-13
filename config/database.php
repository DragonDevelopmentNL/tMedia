<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'media');

// Attempt to connect to MySQL database
try {
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if($conn === false) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    // Set charset to ensure proper encoding
    if(!mysqli_set_charset($conn, "utf8mb4")) {
        throw new Exception("Error setting charset: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Function to safely close database connection
function close_db_connection() {
    global $conn;
    if($conn && !mysqli_connect_errno()) {
        mysqli_close($conn);
        $conn = null;
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('close_db_connection');

// Function to execute query safely
function execute_query($sql, $types = "", $params = []) {
    global $conn;
    
    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . mysqli_error($conn));
        }
        
        if (!empty($types) && !empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Query execution failed: " . mysqli_stmt_error($stmt));
        }
        
        return $stmt;
    } catch (Exception $e) {
        die("Query Error: " . $e->getMessage());
    }
}
?> 