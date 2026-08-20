<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'hospital_db');

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

initializeUsersTable($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? 'status';
$response = ['success' => false, 'message' => 'Invalid action'];

switch ($action) {
    case 'register':
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role      = trim($_POST['role'] ?? 'staff');

        if ($username === '' || $password === '' || $full_name === '') {
            $response['message'] = 'Full name, username and password are required';
            break;
        }

        if (strlen($username) < 3) {
            $response['message'] = 'Username must be at least 3 characters';
            break;
        }

        if (strlen($password) < 6) {
            $response['message'] = 'Password must be at least 6 characters';
            break;
        }

        // Check if username already exists
        $check = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->bind_param('s', $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $response['message'] = 'Username is already taken';
            $check->close();
            break;
        }
        $check->close();

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $insert = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
        $insert->bind_param('ssss', $username, $password_hash, $full_name, $role);

        if ($insert->execute()) {
            $response = [
                'success' => true,
                'message' => 'Account created successfully. You can now log in.'
            ];
        } else {
            $response['message'] = 'Failed to create account';
        }
        $insert->close();
        break;

    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $response['message'] = 'Username and password are required';
            break;
        }

        $stmt = $conn->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $response['message'] = 'Invalid username or password';
            break;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
        break;

    case 'logout':
        session_unset();
        session_destroy();
        $response = [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
        break;

    case 'status':
    default:
        if (isset($_SESSION['user_id'])) {
            $response = [
                'success' => true,
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? '',
                    'full_name' => $_SESSION['full_name'] ?? '',
                    'role' => $_SESSION['role'] ?? ''
                ]
            ];
        } else {
            $response = [
                'success' => true,
                'authenticated' => false
            ];
        }
        break;
}

$conn->close();
echo json_encode($response);

function initializeUsersTable(mysqli $conn): void
{
    $createTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($createTable);

    $checkAdmin = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $adminUsername = 'admin';
    $checkAdmin->bind_param('s', $adminUsername);
    $checkAdmin->execute();
    $checkAdmin->store_result();

    if ($checkAdmin->num_rows === 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $insert = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
        $fullName = 'System Administrator';
        $role = 'admin';
        $insert->bind_param('ssss', $adminUsername, $defaultPassword, $fullName, $role);
        $insert->execute();
        $insert->close();
    }

    $checkAdmin->close();
}

