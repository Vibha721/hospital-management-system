<?php
/**
 * Doctor Management API
 * Handles AJAX requests from frontend for doctors
 */

// Suppress warnings and errors to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'hospital_db');

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Check if doctors table exists and get its structure
$tableCheck = $conn->query("SHOW TABLES LIKE 'doctors'");
$tableExists = $tableCheck && $tableCheck->num_rows > 0;

if ($tableExists) {
    // Check table structure - verify it has the correct columns
    $columns = $conn->query("DESCRIBE doctors");
    $hasName = false;
    $hasFullName = false;
    $columnNames = [];
    while ($col = $columns->fetch_assoc()) {
        $columnNames[] = $col['Field'];
        if ($col['Field'] == 'name') $hasName = true;
        if ($col['Field'] == 'full name') $hasFullName = true;
    }
    
    // If table has old structure (full name instead of name), recreate it
    if ($hasFullName || !$hasName) {
        // Backup existing data if any
        $backup = $conn->query("SELECT * FROM doctors");
        $oldData = [];
        if ($backup) {
            while ($row = $backup->fetch_assoc()) {
                $oldData[] = $row;
            }
        }
        
        // Drop and recreate table with correct structure
        $conn->query("DROP TABLE IF EXISTS doctors");
        $createTable = "CREATE TABLE doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialization VARCHAR(100),
            phone VARCHAR(20),
            email VARCHAR(100),
            experience INT DEFAULT 0,
            qualification VARCHAR(200),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($createTable);
    }
} else {
    // Create doctors table if it doesn't exist
    $createTable = "CREATE TABLE doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        specialization VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        experience INT DEFAULT 0,
        qualification VARCHAR(200),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createTable);
}

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // INSERT a doctor
    if ($action == 'insert') {
        $name           = trim($conn->real_escape_string($_POST['name'] ?? ''));
        $specialization = trim($conn->real_escape_string($_POST['specialization'] ?? ''));
        $phone          = trim($conn->real_escape_string($_POST['phone'] ?? ''));
        $email          = trim($conn->real_escape_string($_POST['email'] ?? ''));
        $experience     = (int)($_POST['experience'] ?? 0);
        $qualification  = trim($conn->real_escape_string($_POST['qualification'] ?? ''));

        if (empty($name)) {
            $response['message'] = 'Name is required';
        } else {
            // Always use auto-increment for id, don't specify it
            $query = "INSERT INTO doctors (name, specialization, phone, email, experience, qualification)
                      VALUES ('$name', '$specialization', '$phone', '$email', $experience, '$qualification')";

            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = "Doctor '$name' inserted successfully.";
            } else {
                $response['message'] = 'Error: ' . $conn->error;
                // If table structure issue, provide helpful message
                if (strpos($conn->error, 'Unknown column') !== false) {
                    $response['message'] = 'Database table structure mismatch. Please recreate the doctors table.';
                }
            }
        }
    }

    // DELETE a doctor
    elseif ($action == 'delete') {
        $id = (int)($_POST['id_delete'] ?? -1);

        if ($id >= 0) {
            $query = "DELETE FROM doctors WHERE id = $id";

            if ($conn->query($query)) {
                if ($conn->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Doctor deleted successfully.";
                } else {
                    $response['message'] = "No doctor found.";
                }
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        } else {
            $response['message'] = 'Invalid doctor ID';
        }
    }

    // GET all doctors
    elseif ($action == 'display' || $action == 'get_all') {
        $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
        
        if (!empty($search)) {
            $query = "SELECT * FROM doctors WHERE 
                     name LIKE '%$search%' OR 
                     specialization LIKE '%$search%' OR 
                     phone LIKE '%$search%' OR 
                     email LIKE '%$search%' 
                     ORDER BY id DESC";
        } else {
            $query = "SELECT * FROM doctors ORDER BY id DESC";
        }
        
        $result = $conn->query($query);

        if ($result) {
            $doctors = [];
            while ($row = $result->fetch_assoc()) {
                // Ensure id is set (should be present now)
                if (!isset($row['id']) || $row['id'] === null) {
                    // If id is still null, it means the column wasn't added or query failed
                    // This shouldn't happen, but handle it gracefully
                    $row['id'] = null;
                }
                $doctors[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $doctors;
            $response['count'] = count($doctors);
            $response['message'] = 'Doctors retrieved successfully';
        } else {
            $response['message'] = 'Error: ' . $conn->error;
        }
    }

    // GET single doctor
    elseif ($action == 'get_one') {
        $id = (int)($_POST['id'] ?? -1);
        
        if ($id >= 0) {
            $query = "SELECT * FROM doctors WHERE id = $id LIMIT 1";
        } else {
            $response['message'] = 'Invalid doctor ID';
            $conn->close();
            echo json_encode($response);
            exit();
        }
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $doctor = $result->fetch_assoc();
            if (!isset($doctor['id'])) {
                $doctor['id'] = null;
            }
            $response['success'] = true;
            $response['data'] = $doctor;
            $response['message'] = 'Doctor retrieved successfully';
        } else {
            $response['message'] = 'Doctor not found';
        }
    }

    // UPDATE a doctor
    elseif ($action == 'update') {
        $id             = (int)($_POST['id'] ?? 0);
        $name           = $conn->real_escape_string($_POST['name'] ?? '');
        $specialization = $conn->real_escape_string($_POST['specialization'] ?? '');
        $phone          = $conn->real_escape_string($_POST['phone'] ?? '');
        $email          = $conn->real_escape_string($_POST['email'] ?? '');
        $experience     = (int)($_POST['experience'] ?? 0);
        $qualification  = $conn->real_escape_string($_POST['qualification'] ?? '');

        if ($id > 0 && !empty($name)) {
            $query = "UPDATE doctors SET 
                     name = '$name', 
                     specialization = '$specialization',
                     phone = '$phone', 
                     email = '$email',
                     experience = $experience,
                     qualification = '$qualification'
                     WHERE id = $id";

            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = "Doctor updated successfully.";
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        } else {
            $response['message'] = 'Name is required';
        }
    }
} else {
    // GET request - return all doctors (no search)
    $result = $conn->query("SELECT * FROM doctors ORDER BY id DESC");

    if ($result) {
        $doctors = [];
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $doctors;
        $response['count'] = count($doctors);
        $response['message'] = 'Doctors retrieved successfully';
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}

$conn->close();

// Ensure clean JSON output
if (ob_get_level()) {
    ob_clean();
}
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit();
?>

