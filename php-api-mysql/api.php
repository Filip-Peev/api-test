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
        // Check for a specific action to add dummy users
        if (isset($_GET['action']) && $_GET['action'] === 'addDummyUsers') {
            // Define an array of dummy user data
            $dummyUsers = [
                ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com'],
                ['name' => 'Peter Jones', 'email' => 'peter.jones@example.com'],
                ['name' => 'Mary Brown', 'email' => 'mary.brown@example.com'],
                ['name' => 'David Wilson', 'email' => 'david.wilson@example.com']
            ];

            $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $email);

            $successCount = 0;
            foreach ($dummyUsers as $user) {
                $name = $user['name'];
                $email = $user['email'];
                if ($stmt->execute()) {
                    $successCount++;
                }
            }
            $stmt->close();

            if ($successCount > 0) {
                echo json_encode([
                    "message" => "$successCount dummy users added successfully."
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Failed to add dummy users."
                ]);
            }
        } else {
            // Original logic to insert a single new user
            $input = json_decode(file_get_contents('php://input'), true);

            if (isset($input['name']) && isset($input['email'])) {
                // Using prepared statements to prevent SQL injection
                $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                $stmt->bind_param("ss", $input['name'], $input['email']);

                if ($stmt->execute()) {
                    echo json_encode([
                        "message" => "New user created successfully",
                        "id" => $conn->insert_id
                    ]);
                } else {
                    http_response_code(500); // Internal Server Error
                    echo json_encode([
                        "error" => "Error: " . $stmt->error
                    ]);
                }
                $stmt->close();
            } else {
                http_response_code(400); // Bad Request
                echo json_encode([
                    "error" => "Invalid input"
                ]);
            }
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
        // Delete a user or all users
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "DELETE FROM users WHERE id = $id";

            if ($conn->query($sql) === TRUE) {
                if ($conn->affected_rows > 0) {
                    echo json_encode([
                        "message" => "User deleted successfully with ID: $id"
                    ]);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode([
                        "error" => "No user found with ID: $id"
                    ]);
                }
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    "error" => "Error deleting user: " . $conn->error
                ]);
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'deleteAll') {
            $sql = "TRUNCATE TABLE users";
            if ($conn->query($sql) === TRUE) {
                echo json_encode([
                    "message" => "All users deleted successfully."
                ]);
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    "error" => "Error deleting all users: " . $conn->error
                ]);
            }
        } else {
            http_response_code(400); // Bad Request
            echo json_encode([
                "error" => "No ID or valid action provided for deletion"
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
