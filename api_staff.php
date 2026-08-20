<?php
/**
 * Staff Management API
 * Handles AJAX requests from frontend for staff
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error
    ], JSON_PRETTY_PRINT);
    exit();
}

// Create staff table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(100),
    employee_id VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(255),
    department VARCHAR(100),
    date_of_joining DATE,
    monthly_salary DECIMAL(10, 2) DEFAULT 0.00,
    shift VARCHAR(50),
    status VARCHAR(50) DEFAULT 'Active',
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (full_name),
    INDEX idx_employee_id (employee_id),
    INDEX idx_department (department),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conn->query($createTable)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $conn->error
    ], JSON_PRETTY_PRINT);
    $conn->close();
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';

        // INSERT a staff member
        if ($action == 'insert') {
            $id              = (int)($_POST['id'] ?? 0);
            $full_name       = $conn->real_escape_string($_POST['full_name'] ?? '');
            $role            = $conn->real_escape_string($_POST['role'] ?? '');
            $employee_id     = $conn->real_escape_string($_POST['employee_id'] ?? '');
            $phone           = $conn->real_escape_string($_POST['phone'] ?? '');
            $email           = $conn->real_escape_string($_POST['email'] ?? '');
            $department      = $conn->real_escape_string($_POST['department'] ?? '');
            $date_of_joining = $conn->real_escape_string($_POST['date_of_joining'] ?? '');
            $monthly_salary  = floatval($_POST['monthly_salary'] ?? 0);
            $shift           = $conn->real_escape_string($_POST['shift'] ?? '');
            $status          = $conn->real_escape_string($_POST['status'] ?? 'Active');
            $address         = $conn->real_escape_string($_POST['address'] ?? '');

            if (empty($full_name)) {
                $response['message'] = 'Full name is required';
            } else {
                // For new staff, let MySQL auto-generate the ID
                if ($id > 0) {
                    $query = "INSERT INTO staff (id, full_name, role, employee_id, phone, email, department, date_of_joining, monthly_salary, shift, status, address)
                              VALUES ($id, '$full_name', '$role', '$employee_id', '$phone', '$email', '$department', " . 
                              ($date_of_joining ? "'$date_of_joining'" : "NULL") . ", $monthly_salary, '$shift', '$status', '$address')";
                } else {
                    $query = "INSERT INTO staff (full_name, role, employee_id, phone, email, department, date_of_joining, monthly_salary, shift, status, address)
                              VALUES ('$full_name', '$role', '$employee_id', '$phone', '$email', '$department', " . 
                              ($date_of_joining ? "'$date_of_joining'" : "NULL") . ", $monthly_salary, '$shift', '$status', '$address')";
                }

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Staff member '$full_name' inserted successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            }
        }

        // DELETE a staff member
        elseif ($action == 'delete') {
            $id = (int)($_POST['id_delete'] ?? 0);
            
            if ($id > 0) {
                $query = "DELETE FROM staff WHERE id = $id";

                if ($conn->query($query)) {
                    if ($conn->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = "Staff member with ID $id deleted successfully.";
                    } else {
                        $response['message'] = "No staff member found with ID $id.";
                    }
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Invalid staff ID';
            }
        }

        // GET all staff
        elseif ($action == 'display' || $action == 'get_all') {
            $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
            
            $query = "SELECT * FROM staff";
            
            if (!empty($search)) {
                $query .= " WHERE full_name LIKE '%$search%' OR 
                          role LIKE '%$search%' OR 
                          employee_id LIKE '%$search%' OR
                          department LIKE '%$search%' OR
                          phone LIKE '%$search%' OR
                          email LIKE '%$search%'";
            }
            
            $query .= " ORDER BY full_name ASC";
            
            $result = $conn->query($query);

            if ($result) {
                $staff = [];
                while ($row = $result->fetch_assoc()) {
                    $staff[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $staff;
                $response['count'] = count($staff);
                $response['message'] = 'Staff retrieved successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Error: ' . $conn->error;
                http_response_code(500);
            }
        }

        // GET single staff member
        elseif ($action == 'get_one') {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id > 0) {
                $query = "SELECT * FROM staff WHERE id = $id";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                    $response['message'] = 'Staff member retrieved successfully';
                } else {
                    $response['message'] = 'Staff member not found';
                }
            } else {
                $response['message'] = 'Invalid staff ID';
            }
        }

        // UPDATE a staff member
        elseif ($action == 'update') {
            $id              = (int)($_POST['id'] ?? 0);
            $full_name       = $conn->real_escape_string($_POST['full_name'] ?? '');
            $role            = $conn->real_escape_string($_POST['role'] ?? '');
            $employee_id     = $conn->real_escape_string($_POST['employee_id'] ?? '');
            $phone           = $conn->real_escape_string($_POST['phone'] ?? '');
            $email           = $conn->real_escape_string($_POST['email'] ?? '');
            $department      = $conn->real_escape_string($_POST['department'] ?? '');
            $date_of_joining = $conn->real_escape_string($_POST['date_of_joining'] ?? '');
            $monthly_salary  = floatval($_POST['monthly_salary'] ?? 0);
            $shift           = $conn->real_escape_string($_POST['shift'] ?? '');
            $status          = $conn->real_escape_string($_POST['status'] ?? 'Active');
            $address         = $conn->real_escape_string($_POST['address'] ?? '');

            if ($id > 0 && !empty($full_name)) {
                $dateOfJoiningValue = $date_of_joining ? "'$date_of_joining'" : "NULL";
                $query = "UPDATE staff SET 
                         full_name = '$full_name',
                         role = '$role',
                         employee_id = '$employee_id',
                         phone = '$phone',
                         email = '$email',
                         department = '$department',
                         date_of_joining = $dateOfJoiningValue,
                         monthly_salary = $monthly_salary,
                         shift = '$shift',
                         status = '$status',
                         address = '$address'
                         WHERE id = $id";

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Staff member updated successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Staff ID and full name are required';
            }
        }
    } else {
        // GET request - return all staff
        $query = "SELECT * FROM staff ORDER BY full_name ASC";
        $result = $conn->query($query);

        if ($result) {
            $staff = [];
            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $staff;
            $response['count'] = count($staff);
            $response['message'] = 'Staff retrieved successfully';
        } else {
            $response['success'] = false;
            $response['message'] = 'Error: ' . $conn->error;
            http_response_code(500);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => null
    ];
}

// Set proper HTTP status code
if (!$response['success'] && http_response_code() == 200) {
    http_response_code(500);
}

$conn->close();
echo json_encode($response, JSON_PRETTY_PRINT);
?>






