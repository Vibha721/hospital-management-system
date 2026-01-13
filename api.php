<?php
/**
 * Patient Management API
 * Handles AJAX requests from frontend
 */

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
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error
    ]);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // INSERT a patient
    if ($action == 'insert') {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = $conn->real_escape_string($_POST['name'] ?? '');
        $age    = (int)($_POST['age'] ?? 0);
        $phone  = $conn->real_escape_string($_POST['phone'] ?? '');
        $email  = $conn->real_escape_string($_POST['email'] ?? '');

        if (empty($name) || empty($phone) || empty($email)) {
            $response['message'] = 'All fields are required';
        } else {
            $query = "INSERT INTO patients (id, name, age, phone, email)
                      VALUES ($id, '$name', $age, '$phone', '$email')";

            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = "Patient '$name' inserted successfully.";
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        }
    }

    // DELETE a patient
    elseif ($action == 'delete') {
        $id = (int)($_POST['id_delete'] ?? -1);
        
        // Allow deleting records even if ID is 0 (for legacy rows)
        if ($id >= 0) {
            $query = "DELETE FROM patients WHERE id = $id";

            if ($conn->query($query)) {
                if ($conn->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Patient with ID $id deleted successfully.";
                } else {
                    $response['message'] = "No patient found with ID $id.";
                }
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        } else {
            $response['message'] = 'Invalid patient ID';
        }
    }

    // GET all patients
    elseif ($action == 'display' || $action == 'get_all') {
        $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
        
        if (!empty($search)) {
            $query = "SELECT * FROM patients WHERE 
                     name LIKE '%$search%' OR 
                     phone LIKE '%$search%' OR 
                     email LIKE '%$search%' 
                     ORDER BY id DESC";
        } else {
            $query = "SELECT * FROM patients ORDER BY id DESC";
        }
        
        $result = $conn->query($query);

        if ($result) {
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $patients;
            $response['count'] = count($patients);
            $response['message'] = 'Patients retrieved successfully';
        } else {
            $response['message'] = 'Error: ' . $conn->error;
        }
    }

    // GET single patient
    elseif ($action == 'get_one') {
        $id = (int)($_POST['id'] ?? -1);
        
        // Allow fetching patient even when ID is 0
        if ($id >= 0) {
            $query = "SELECT * FROM patients WHERE id = $id";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $response['success'] = true;
                $response['data'] = $result->fetch_assoc();
                $response['message'] = 'Patient retrieved successfully';
            } else {
                $response['message'] = 'Patient not found';
            }
        } else {
            $response['message'] = 'Invalid patient ID';
        }
    }

    // UPDATE a patient
    elseif ($action == 'update') {
        $id     = (int)($_POST['id'] ?? -1);
        $name   = $conn->real_escape_string($_POST['name'] ?? '');
        $age    = (int)($_POST['age'] ?? 0);
        $phone  = $conn->real_escape_string($_POST['phone'] ?? '');
        $email  = $conn->real_escape_string($_POST['email'] ?? '');

        // Allow updating patient even when ID is 0
        if ($id >= 0 && !empty($name) && !empty($phone) && !empty($email)) {
            $query = "UPDATE patients SET 
                     name = '$name', 
                     age = $age, 
                     phone = '$phone', 
                     email = '$email' 
                     WHERE id = $id";

            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = "Patient updated successfully.";
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        } else {
            $response['message'] = 'All fields are required';
        }
    }
} else {
    // GET request - return all patients
    $query = "SELECT * FROM patients ORDER BY id DESC";
    $result = $conn->query($query);

    if ($result) {
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $patients;
        $response['count'] = count($patients);
        $response['message'] = 'Patients retrieved successfully';
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}

$conn->close();
echo json_encode($response);
?>






