<?php
/**
 * Appointment Management API
 * Handles AJAX requests from frontend for appointments
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

// Create appointments table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status VARCHAR(20) DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date)
)";
if (!$conn->query($createTable)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $conn->error
    ], JSON_PRETTY_PRINT);
    $conn->close();
    exit();
}

// Check if patients and doctors tables exist and get column names
$patientsTableExists = false;
$doctorsTableExists = false;
$patientNameColumn = 'name';
$doctorNameColumn = 'name';
$doctorsHasId = false;

$checkPatients = $conn->query("SHOW TABLES LIKE 'patients'");
if ($checkPatients && $checkPatients->num_rows > 0) {
    $patientsTableExists = true;
    // Check if patients table has 'name' column
    $patientCols = $conn->query("SHOW COLUMNS FROM patients LIKE 'name'");
    if (!$patientCols || $patientCols->num_rows == 0) {
        // Try other common column names
        $patientCols = $conn->query("SHOW COLUMNS FROM patients LIKE 'full name'");
        if ($patientCols && $patientCols->num_rows > 0) {
            $patientNameColumn = 'full name';
        }
    }
}

$checkDoctors = $conn->query("SHOW TABLES LIKE 'doctors'");
if ($checkDoctors && $checkDoctors->num_rows > 0) {
    $doctorsTableExists = true;
    // Check if doctors table has 'id' column
    $doctorIdCols = $conn->query("SHOW COLUMNS FROM doctors LIKE 'id'");
    $doctorsHasId = $doctorIdCols && $doctorIdCols->num_rows > 0;
    
    // If doctors table doesn't have an id column, add it
    if (!$doctorsHasId) {
        // Add id column to doctors table
        $addIdColumn = "ALTER TABLE doctors ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
        if ($conn->query($addIdColumn)) {
            $doctorsHasId = true;
        } else {
            // If adding column fails, try a different approach
            // Maybe the table has a different primary key structure
            error_log("Could not add id column to doctors table: " . $conn->error);
        }
    }
    
    // Check if doctors table has 'name' column
    $doctorCols = $conn->query("SHOW COLUMNS FROM doctors LIKE 'name'");
    if (!$doctorCols || $doctorCols->num_rows == 0) {
        // Try 'full name' column
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

        // INSERT an appointment
    if ($action == 'insert') {
        $id              = (int)($_POST['id'] ?? 0);
        $patient_id      = (int)($_POST['patient_id'] ?? 0);
        $doctor_id       = (int)($_POST['doctor_id'] ?? 0);
        $appointment_date = $conn->real_escape_string($_POST['appointment_date'] ?? '');
        $appointment_time = $conn->real_escape_string($_POST['appointment_time'] ?? '');
        $status          = $conn->real_escape_string($_POST['status'] ?? 'Scheduled');
        $notes           = $conn->real_escape_string($_POST['notes'] ?? '');

        if (empty($appointment_date) || empty($appointment_time) || $patient_id == 0 || $doctor_id == 0) {
            $response['message'] = 'Patient, Doctor, Date, and Time are required';
        } else {
            // For new appointments, let MySQL auto-generate the ID
            if ($id > 0) {
                $query = "INSERT INTO appointments (id, patient_id, doctor_id, appointment_date, appointment_time, status, notes)
                          VALUES ($id, $patient_id, $doctor_id, '$appointment_date', '$appointment_time', '$status', '$notes')";
            } else {
                $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, notes)
                          VALUES ($patient_id, $doctor_id, '$appointment_date', '$appointment_time', '$status', '$notes')";
            }

            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = "Appointment inserted successfully.";
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        }
    }

    // DELETE an appointment
    elseif ($action == 'delete') {
        $id = (int)($_POST['id_delete'] ?? 0);
        
        if ($id > 0) {
            $query = "DELETE FROM appointments WHERE id = $id";

            if ($conn->query($query)) {
                if ($conn->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Appointment with ID $id deleted successfully.";
                } else {
                    $response['message'] = "No appointment found with ID $id.";
                }
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        } else {
            $response['message'] = 'Invalid appointment ID';
        }
    }

    // GET all appointments
    elseif ($action == 'display' || $action == 'get_all') {
        $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
        
        // Build query with conditional JOINs
        if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
            $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
            $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
            $query = "SELECT a.*, p.$patientCol as patient_name, d.$doctorCol as doctor_name 
                      FROM appointments a
                      LEFT JOIN patients p ON a.patient_id = p.id
                      LEFT JOIN doctors d ON a.doctor_id = d.id";
        } else {
            // If tables don't exist or doctors table doesn't have id, just get appointments without joins
            $query = "SELECT a.*, 
                      CAST(a.patient_id AS CHAR) as patient_name,
                      CAST(a.doctor_id AS CHAR) as doctor_name
                      FROM appointments a";
        }
        
        if (!empty($search)) {
            if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
                $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
                $query .= " WHERE p.$patientCol LIKE '%$search%' OR 
                          d.$doctorCol LIKE '%$search%' OR 
                          a.appointment_date LIKE '%$search%' OR
                          a.status LIKE '%$search%'";
            } else {
                $query .= " WHERE a.appointment_date LIKE '%$search%' OR
                          a.status LIKE '%$search%'";
            }
        }
        
        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $result = $conn->query($query);

        if ($result) {
            $appointments = [];
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $appointments;
            $response['count'] = count($appointments);
            $response['message'] = 'Appointments retrieved successfully';
        } else {
            $response['success'] = false;
            $response['message'] = 'Error: ' . $conn->error;
            http_response_code(500);
        }
    }

    // GET single appointment
    elseif ($action == 'get_one') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
                $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
                $query = "SELECT a.*, p.$patientCol as patient_name, d.$doctorCol as doctor_name 
                         FROM appointments a
                         LEFT JOIN patients p ON a.patient_id = p.id
                         LEFT JOIN doctors d ON a.doctor_id = d.id
                         WHERE a.id = $id";
            } else {
                $query = "SELECT a.*, 
                         CAST(a.patient_id AS CHAR) as patient_name,
                         CAST(a.doctor_id AS CHAR) as doctor_name
                         FROM appointments a
                         WHERE a.id = $id";
            }
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $response['success'] = true;
                $response['data'] = $result->fetch_assoc();
                $response['message'] = 'Appointment retrieved successfully';
            } else {
                $response['message'] = 'Appointment not found';
            }
        } else {
            $response['message'] = 'Invalid appointment ID';
        }
    }

    // UPDATE an appointment
    elseif ($action == 'update') {
        $id              = (int)($_POST['id'] ?? 0);
        $patient_id      = (int)($_POST['patient_id'] ?? 0);
        $doctor_id       = (int)($_POST['doctor_id'] ?? 0);
        $appointment_date = $conn->real_escape_string($_POST['appointment_date'] ?? '');
        $appointment_time = $conn->real_escape_string($_POST['appointment_time'] ?? '');
        $status          = $conn->real_escape_string($_POST['status'] ?? 'Scheduled');
        $notes           = $conn->real_escape_string($_POST['notes'] ?? '');

        if ($id > 0 && !empty($appointment_date) && !empty($appointment_time) && $patient_id > 0 && $doctor_id > 0) {
            $query = "UPDATE appointments SET 
                     patient_id = $patient_id,
                     doctor_id = $doctor_id,
                     appointment_date = '$appointment_date',
                     appointment_time = '$appointment_time',
                     status = '$status',
                     notes = '$notes'
                     WHERE id = $id";

            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = "Appointment updated successfully.";
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        } else {
            $response['message'] = 'All required fields must be filled';
        }
    }

    // GET patients and doctors for dropdowns
    elseif ($action == 'get_options') {
        $patients = [];
        $doctors = [];
        
        if ($patientsTableExists) {
            $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
            $patientsResult = $conn->query("SELECT id, $patientCol as name FROM patients ORDER BY $patientCol");
            if ($patientsResult) {
                while ($row = $patientsResult->fetch_assoc()) {
                    $patients[] = $row;
                }
            }
        }
        
        if ($doctorsTableExists) {
            $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
            if ($doctorsHasId) {
                $doctorsResult = $conn->query("SELECT id, $doctorCol as name FROM doctors ORDER BY $doctorCol");
            } else {
                // If no id column, we can't use doctors in dropdowns properly
                // Return empty array or use a workaround
                $doctorsResult = false;
            }
            if ($doctorsResult) {
                while ($row = $doctorsResult->fetch_assoc()) {
                    $doctors[] = $row;
                }
            }
        }
        
        $response['success'] = true;
        $response['data'] = ['patients' => $patients, 'doctors' => $doctors];
    }
} else {
    // GET request - return all appointments
    if ($patientsTableExists && $doctorsTableExists && $doctorsHasId) {
        $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
        $doctorCol = $doctorNameColumn == 'full name' ? '`full name`' : $doctorNameColumn;
        $query = "SELECT a.*, p.$patientCol as patient_name, d.$doctorCol as doctor_name 
                  FROM appointments a
                  LEFT JOIN patients p ON a.patient_id = p.id
                  LEFT JOIN doctors d ON a.doctor_id = d.id
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    } else {
        $query = "SELECT a.*, 
                  CAST(a.patient_id AS CHAR) as patient_name,
                  CAST(a.doctor_id AS CHAR) as doctor_name
                  FROM appointments a
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    }
    
    $result = $conn->query($query);

    if ($result) {
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $appointments;
        $response['count'] = count($appointments);
        $response['message'] = 'Appointments retrieved successfully';
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

