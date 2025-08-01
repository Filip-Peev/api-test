<?php
header("Content-Type: application/json");

require_once 'config.php';

$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "error" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
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
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['name']) && isset($input['email'])) {
            $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $input['name'], $input['email']);

            if ($stmt->execute()) {
                echo json_encode([
                    "message" => "New user created successfully",
                    "id" => $conn->insert_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Error: " . $stmt->error
                ]);
            }
            $stmt->close();
        } else {
            http_response_code(400);
            echo json_encode([
                "error" => "Invalid input"
            ]);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['id']) && (isset($input['name']) || isset($input['email']))) {
            $id = intval($input['id']);
            $name = isset($input['name']) ? $input['name'] : NULL;
            $email = isset($input['email']) ? $input['email'] : NULL;

            $fields = [];
            $params = [];
            $types = '';

            if ($name !== NULL) {
                $fields[] = "name = ?";
                $params[] = $name;
                $types .= 's';
            }

            if ($email !== NULL) {
                $fields[] = "email = ?";
                $params[] = $email;
                $types .= 's';
            }

            if (!empty($fields)) {
                $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
                $params[] = $id;
                $types .= 'i';

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    echo json_encode([
                        "message" => "User updated successfully"
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        "error" => "Error updating user: " . $stmt->error
                    ]);
                }

                $stmt->close();
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "error" => "Invalid input"
            ]);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "DELETE FROM users WHERE id = $id";

            if ($conn->query($sql) === TRUE) {
                if ($conn->affected_rows > 0) {
                    echo json_encode([
                        "message" => "User deleted successfully with ID: $id"
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        "error" => "No user found with ID: $id"
                    ]);
                }
            } else {
                http_response_code(500);
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
                http_response_code(500);
                echo json_encode([
                    "error" => "Error deleting all users: " . $conn->error
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "error" => "No ID or valid action provided for deletion"
            ]);
        }
        break;

    default:

        http_response_code(405);
        echo json_encode([
            "error" => "Method not allowed"
        ]);
        break;
}

$conn->close();
