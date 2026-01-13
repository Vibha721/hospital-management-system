<?php
/**
 * Lab Tests Management API
 * Handles AJAX requests from frontend for lab tests
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

// Create lab_tests table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS lab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(255) NOT NULL,
    test_category VARCHAR(100),
    patient_id INT,
    doctor_id INT,
    test_date DATE NOT NULL,
    test_cost DECIMAL(10, 2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'Pending',
    test_result TEXT,
    summary TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_test_name (test_name),
    INDEX idx_category (test_category),
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_test_date (test_date),
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

// Check if patients and doctors tables exist for JOINs
$patientsTableExists = false;
$doctorsTableExists = false;
$patientNameColumn = 'name';
$doctorNameColumn = 'name';
$doctorsHasId = false;

$checkPatients = $conn->query("SHOW TABLES LIKE 'patients'");
if ($checkPatients && $checkPatients->num_rows > 0) {
    $patientsTableExists = true;
    $patientCols = $conn->query("SHOW COLUMNS FROM patients LIKE 'name'");
    if (!$patientCols || $patientCols->num_rows == 0) {
        $patientCols = $conn->query("SHOW COLUMNS FROM patients LIKE 'full name'");
        if ($patientCols && $patientCols->num_rows > 0) {
            $patientNameColumn = 'full name';
        }
    }
}

$checkDoctors = $conn->query("SHOW TABLES LIKE 'doctors'");
if ($checkDoctors && $checkDoctors->num_rows > 0) {
    $doctorsTableExists = true;
    $doctorIdCols = $conn->query("SHOW COLUMNS FROM doctors LIKE 'id'");
    $doctorsHasId = $doctorIdCols && $doctorIdCols->num_rows > 0;
    
    $doctorCols = $conn->query("SHOW COLUMNS FROM doctors LIKE 'name'");
    if (!$doctorCols || $doctorCols->num_rows == 0) {
        $doctorCols = $conn->query("SHOW COLUMNS FROM doctors LIKE 'full name'");
        if ($doctorCols && $doctorCols->num_rows > 0) {
            $doctorNameColumn = 'full name';
        }
    }
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';

        // INSERT a lab test
        if ($action == 'insert') {
            $id              = (int)($_POST['id'] ?? 0);
            $test_name       = $conn->real_escape_string($_POST['test_name'] ?? '');
            $test_category   = $conn->real_escape_string($_POST['test_category'] ?? '');
            $patient_id      = (int)($_POST['patient_id'] ?? 0);
            $doctor_id       = (int)($_POST['doctor_id'] ?? 0);
            $test_date       = $conn->real_escape_string($_POST['test_date'] ?? '');
            $test_cost       = floatval($_POST['test_cost'] ?? 0);
            $status          = $conn->real_escape_string($_POST['status'] ?? 'Pending');
            $test_result     = $conn->real_escape_string($_POST['test_result'] ?? '');
            $summary         = $conn->real_escape_string($_POST['summary'] ?? '');
            $notes           = $conn->real_escape_string($_POST['notes'] ?? '');

            if (empty($test_name) || empty($test_date)) {
                $response['message'] = 'Test name and test date are required';
            } else {
                // For new tests, let MySQL auto-generate the ID
                if ($id > 0) {
                    $query = "INSERT INTO lab_tests (id, test_name, test_category, patient_id, doctor_id, test_date, test_cost, status, test_result, summary, notes)
                              VALUES ($id, '$test_name', '$test_category', $patient_id, $doctor_id, '$test_date', $test_cost, '$status', '$test_result', '$summary', '$notes')";
                } else {
                    $query = "INSERT INTO lab_tests (test_name, test_category, patient_id, doctor_id, test_date, test_cost, status, test_result, summary, notes)
                              VALUES ('$test_name', '$test_category', $patient_id, $doctor_id, '$test_date', $test_cost, '$status', '$test_result', '$summary', '$notes')";
                }

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Lab test '$test_name' inserted successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            }
        }

        // DELETE a lab test
        elseif ($action == 'delete') {
            $id = (int)($_POST['id_delete'] ?? 0);
            
            if ($id > 0) {
                $query = "DELETE FROM lab_tests WHERE id = $id";

                if ($conn->query($query)) {
                    if ($conn->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = "Lab test with ID $id deleted successfully.";
                    } else {
                        $response['message'] = "No lab test found with ID $id.";
                    }
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Invalid lab test ID';
            }
        }

        // GET all lab tests
        elseif ($action == 'display' || $action == 'get_all') {
            $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
            
            // Build query with conditional JOINs
            if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
                $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
                $query = "SELECT lt.*, p.$patientCol as patient_name, d.$doctorCol as doctor_name 
                          FROM lab_tests lt
                          LEFT JOIN patients p ON lt.patient_id = p.id
                          LEFT JOIN doctors d ON lt.doctor_id = d.id";
            } else {
                $query = "SELECT lt.*, 
                          CAST(lt.patient_id AS CHAR) as patient_name,
                          CAST(lt.doctor_id AS CHAR) as doctor_name
                          FROM lab_tests lt";
            }
            
            if (!empty($search)) {
                if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
                    $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                    $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
                    $query .= " WHERE lt.test_name LIKE '%$search%' OR 
                              lt.test_category LIKE '%$search%' OR 
                              p.$patientCol LIKE '%$search%' OR 
                              d.$doctorCol LIKE '%$search%' OR
                              lt.status LIKE '%$search%'";
                } else {
                    $query .= " WHERE lt.test_name LIKE '%$search%' OR 
                              lt.test_category LIKE '%$search%' OR 
                              lt.status LIKE '%$search%'";
                }
            }
            
            $query .= " ORDER BY lt.test_date DESC, lt.id DESC";
            
            $result = $conn->query($query);

            if ($result) {
                $lab_tests = [];
                while ($row = $result->fetch_assoc()) {
                    $lab_tests[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $lab_tests;
                $response['count'] = count($lab_tests);
                $response['message'] = 'Lab tests retrieved successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Error: ' . $conn->error;
                http_response_code(500);
            }
        }

        // GET single lab test
        elseif ($action == 'get_one') {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id > 0) {
                if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
                    $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                    $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
                    $query = "SELECT lt.*, p.$patientCol as patient_name, d.$doctorCol as doctor_name 
                             FROM lab_tests lt
                             LEFT JOIN patients p ON lt.patient_id = p.id
                             LEFT JOIN doctors d ON lt.doctor_id = d.id
                             WHERE lt.id = $id";
                } else {
                    $query = "SELECT lt.*, 
                             CAST(lt.patient_id AS CHAR) as patient_name,
                             CAST(lt.doctor_id AS CHAR) as doctor_name
                             FROM lab_tests lt
                             WHERE lt.id = $id";
                }
                
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                    $response['message'] = 'Lab test retrieved successfully';
                } else {
                    $response['message'] = 'Lab test not found';
                }
            } else {
                $response['message'] = 'Invalid lab test ID';
            }
        }

        // UPDATE a lab test
        elseif ($action == 'update') {
            $id              = (int)($_POST['id'] ?? 0);
            $test_name       = $conn->real_escape_string($_POST['test_name'] ?? '');
            $test_category   = $conn->real_escape_string($_POST['test_category'] ?? '');
            $patient_id      = (int)($_POST['patient_id'] ?? 0);
            $doctor_id       = (int)($_POST['doctor_id'] ?? 0);
            $test_date       = $conn->real_escape_string($_POST['test_date'] ?? '');
            $test_cost       = floatval($_POST['test_cost'] ?? 0);
            $status          = $conn->real_escape_string($_POST['status'] ?? 'Pending');
            $test_result     = $conn->real_escape_string($_POST['test_result'] ?? '');
            $summary         = $conn->real_escape_string($_POST['summary'] ?? '');
            $notes           = $conn->real_escape_string($_POST['notes'] ?? '');

            if ($id > 0 && !empty($test_name) && !empty($test_date)) {
                $query = "UPDATE lab_tests SET 
                         test_name = '$test_name',
                         test_category = '$test_category',
                         patient_id = $patient_id,
                         doctor_id = $doctor_id,
                         test_date = '$test_date',
                         test_cost = $test_cost,
                         status = '$status',
                         test_result = '$test_result',
                         summary = '$summary',
                         notes = '$notes'
                         WHERE id = $id";

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Lab test updated successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Test ID, name, and date are required';
            }
        }
    } else {
        // GET request - return all lab tests
        if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
            $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
            $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
            $query = "SELECT lt.*, p.$patientCol as patient_name, d.$doctorCol as doctor_name 
                      FROM lab_tests lt
                      LEFT JOIN patients p ON lt.patient_id = p.id
                      LEFT JOIN doctors d ON lt.doctor_id = d.id
                      ORDER BY lt.test_date DESC, lt.id DESC";
        } else {
            $query = "SELECT lt.*, 
                      CAST(lt.patient_id AS CHAR) as patient_name,
                      CAST(lt.doctor_id AS CHAR) as doctor_name
                      FROM lab_tests lt
                      ORDER BY lt.test_date DESC, lt.id DESC";
        }
        
        $result = $conn->query($query);

        if ($result) {
            $lab_tests = [];
            while ($row = $result->fetch_assoc()) {
                $lab_tests[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $lab_tests;
            $response['count'] = count($lab_tests);
            $response['message'] = 'Lab tests retrieved successfully';
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






