<?php
// Set headers
header("Content-Type: application/json");

// Include the configuration file
require_once 'config.php';

// Create connection using credentials from config.php
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "error" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve all users or a specific user
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM users WHERE id = $id";
        } else {
            $sql = "SELECT * FROM users";
        }
        
        $result = $conn->query($sql);
        $users = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode($users);
        break;

    case 'POST':
        // Insert a new user
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['name']) && isset($input['email'])) {
            $name = $input['name'];
            $email = $input['email'];
            $sql = "INSERT INTO users (name, email) VALUES ('$name', '$email')";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode([
                    "message" => "New user created successfully",
                    "id" => $conn->insert_id
                ]);
            } else {
                echo json_encode([
                    "error" => "Error: " . $sql . "<br>" . $conn->error
                ]);
            }
        } else {
            echo json_encode([
                "error" => "Invalid input"
            ]);
        }
        break;

    case 'PUT':
        // Update an existing user
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['id']) && (isset($input['name']) || isset($input['email']))) {
            $id = intval($input['id']);
            $name = isset($input['name']) ? $input['name'] : NULL;
            $email = isset($input['email']) ? $input['email'] : NULL;
            
            $sql = "UPDATE users SET ";
            if ($name) $sql .= "name = '$name' ";
            if ($email) $sql .= ($name ? ", " : "") . "email = '$email' ";
            $sql .= "WHERE id = $id";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode([
                    "message" => "User updated successfully"
                ]);
            } else {
                echo json_encode([
                    "error" => "Error updating record: " . $conn->error
                ]);
            }
        } else {
            echo json_encode([
                "error" => "Invalid input"
            ]);
        }
        break;

    case 'DELETE':
        // Delete a user
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "DELETE FROM users WHERE id = $id";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode([
                    "message" => "User deleted successfully with ID: " . $id
                ]);
            } else {
                echo json_encode([
                    "error" => "Error deleting record: " . $conn->error
                ]);
            }
        } else {
            echo json_encode([
                "error" => "No ID provided"
            ]);
        }
        break;

    default:
        // Handle unsupported methods
        http_response_code(405); // Method Not Allowed
        echo json_encode([
            "error" => "Method not allowed"
        ]);
        break;
}

// Close the MySQL connection
$conn->close();