<?php
/**
 * Billing Management API
 * Handles AJAX requests from frontend for bills
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

// Create bills table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(100) UNIQUE NOT NULL,
    bill_date DATE NOT NULL,
    patient_id INT,
    consultation_fee DECIMAL(10, 2) DEFAULT 0.00,
    medicine_charges DECIMAL(10, 2) DEFAULT 0.00,
    lab_charges DECIMAL(10, 2) DEFAULT 0.00,
    other_charges DECIMAL(10, 2) DEFAULT 0.00,
    additional_items TEXT,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    status VARCHAR(50) DEFAULT 'Paid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bill_number (bill_number),
    INDEX idx_patient (patient_id),
    INDEX idx_bill_date (bill_date)
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

// Check if patients table exists for JOINs
$patientsTableExists = false;
$patientNameColumn = 'name';

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

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';

        // INSERT a bill
        if ($action == 'insert') {
            $id              = (int)($_POST['id'] ?? 0);
            $bill_number     = $conn->real_escape_string($_POST['bill_number'] ?? '');
            $bill_date       = $conn->real_escape_string($_POST['bill_date'] ?? '');
            $patient_id      = (int)($_POST['patient_id'] ?? 0);
            $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);
            $medicine_charges = floatval($_POST['medicine_charges'] ?? 0);
            $lab_charges     = floatval($_POST['lab_charges'] ?? 0);
            $other_charges   = floatval($_POST['other_charges'] ?? 0);
            $additional_items = $conn->real_escape_string($_POST['additional_items'] ?? '');
            $discount        = floatval($_POST['discount'] ?? 0);
            $total_amount    = floatval($_POST['total_amount'] ?? 0);
            $amount_paid     = floatval($_POST['amount_paid'] ?? 0);
            $payment_method  = $conn->real_escape_string($_POST['payment_method'] ?? 'Cash');
            $status          = $conn->real_escape_string($_POST['status'] ?? 'Paid');
            $notes           = $conn->real_escape_string($_POST['notes'] ?? '');

            if (empty($bill_number) || empty($bill_date)) {
                $response['message'] = 'Bill number and date are required';
            } else {
                // For new bills, let MySQL auto-generate the ID
                if ($id > 0) {
                    $query = "INSERT INTO bills (id, bill_number, bill_date, patient_id, consultation_fee, medicine_charges, lab_charges, other_charges, additional_items, discount, total_amount, amount_paid, payment_method, status, notes)
                              VALUES ($id, '$bill_number', '$bill_date', $patient_id, $consultation_fee, $medicine_charges, $lab_charges, $other_charges, '$additional_items', $discount, $total_amount, $amount_paid, '$payment_method', '$status', '$notes')";
                } else {
                    $query = "INSERT INTO bills (bill_number, bill_date, patient_id, consultation_fee, medicine_charges, lab_charges, other_charges, additional_items, discount, total_amount, amount_paid, payment_method, status, notes)
                              VALUES ('$bill_number', '$bill_date', $patient_id, $consultation_fee, $medicine_charges, $lab_charges, $other_charges, '$additional_items', $discount, $total_amount, $amount_paid, '$payment_method', '$status', '$notes')";
                }

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Bill '$bill_number' created successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            }
        }

        // DELETE a bill
        elseif ($action == 'delete') {
            $id = (int)($_POST['id_delete'] ?? 0);
            
            if ($id > 0) {
                $query = "DELETE FROM bills WHERE id = $id";

                if ($conn->query($query)) {
                    if ($conn->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = "Bill with ID $id deleted successfully.";
                    } else {
                        $response['message'] = "No bill found with ID $id.";
                    }
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Invalid bill ID';
            }
        }

        // GET all bills
        elseif ($action == 'display' || $action == 'get_all') {
            $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
            
            // Build query with conditional JOINs
            if ($patientsTableExists) {
                $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                $query = "SELECT b.*, p.$patientCol as patient_name 
                          FROM bills b
                          LEFT JOIN patients p ON b.patient_id = p.id";
            } else {
                $query = "SELECT b.*, 
                          CAST(b.patient_id AS CHAR) as patient_name
                          FROM bills b";
            }
            
            if (!empty($search)) {
                if ($patientsTableExists) {
                    $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                    $query .= " WHERE b.bill_number LIKE '%$search%' OR 
                              p.$patientCol LIKE '%$search%' OR 
                              b.bill_date LIKE '%$search%' OR
                              b.status LIKE '%$search%'";
                } else {
                    $query .= " WHERE b.bill_number LIKE '%$search%' OR 
                              b.bill_date LIKE '%$search%' OR
                              b.status LIKE '%$search%'";
                }
            }
            
            $query .= " ORDER BY b.bill_date DESC, b.id DESC";
            
            $result = $conn->query($query);

            if ($result) {
                $bills = [];
                while ($row = $result->fetch_assoc()) {
                    $bills[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $bills;
                $response['count'] = count($bills);
                $response['message'] = 'Bills retrieved successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Error: ' . $conn->error;
                http_response_code(500);
            }
        }

        // GET single bill
        elseif ($action == 'get_one') {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id > 0) {
                if ($patientsTableExists) {
                    $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
                    $query = "SELECT b.*, p.$patientCol as patient_name 
                             FROM bills b
                             LEFT JOIN patients p ON b.patient_id = p.id
                             WHERE b.id = $id";
                } else {
                    $query = "SELECT b.*, 
                             CAST(b.patient_id AS CHAR) as patient_name
                             FROM bills b
                             WHERE b.id = $id";
                }
                
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                    $response['message'] = 'Bill retrieved successfully';
                } else {
                    $response['message'] = 'Bill not found';
                }
            } else {
                $response['message'] = 'Invalid bill ID';
            }
        }

        // UPDATE a bill
        elseif ($action == 'update') {
            $id              = (int)($_POST['id'] ?? 0);
            $bill_number     = $conn->real_escape_string($_POST['bill_number'] ?? '');
            $bill_date       = $conn->real_escape_string($_POST['bill_date'] ?? '');
            $patient_id      = (int)($_POST['patient_id'] ?? 0);
            $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);
            $medicine_charges = floatval($_POST['medicine_charges'] ?? 0);
            $lab_charges     = floatval($_POST['lab_charges'] ?? 0);
            $other_charges   = floatval($_POST['other_charges'] ?? 0);
            $additional_items = $conn->real_escape_string($_POST['additional_items'] ?? '');
            $discount        = floatval($_POST['discount'] ?? 0);
            $total_amount    = floatval($_POST['total_amount'] ?? 0);
            $amount_paid     = floatval($_POST['amount_paid'] ?? 0);
            $payment_method  = $conn->real_escape_string($_POST['payment_method'] ?? 'Cash');
            $status          = $conn->real_escape_string($_POST['status'] ?? 'Paid');
            $notes           = $conn->real_escape_string($_POST['notes'] ?? '');

            if ($id > 0 && !empty($bill_number) && !empty($bill_date)) {
                $query = "UPDATE bills SET 
                         bill_number = '$bill_number',
                         bill_date = '$bill_date',
                         patient_id = $patient_id,
                         consultation_fee = $consultation_fee,
                         medicine_charges = $medicine_charges,
                         lab_charges = $lab_charges,
                         other_charges = $other_charges,
                         additional_items = '$additional_items',
                         discount = $discount,
                         total_amount = $total_amount,
                         amount_paid = $amount_paid,
                         payment_method = '$payment_method',
                         status = '$status',
                         notes = '$notes'
                         WHERE id = $id";

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Bill updated successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Bill ID, number, and date are required';
            }
        }
    } else {
        // GET request - return all bills
        if ($patientsTableExists) {
            $patientCol = $patientNameColumn == 'full name' ? '`full name`' : $patientNameColumn;
            $query = "SELECT b.*, p.$patientCol as patient_name 
                      FROM bills b
                      LEFT JOIN patients p ON b.patient_id = p.id
                      ORDER BY b.bill_date DESC, b.id DESC";
        } else {
            $query = "SELECT b.*, 
                      CAST(b.patient_id AS CHAR) as patient_name
                      FROM bills b
                      ORDER BY b.bill_date DESC, b.id DESC";
        }
        
        $result = $conn->query($query);

        if ($result) {
            $bills = [];
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $bills;
            $response['count'] = count($bills);
            $response['message'] = 'Bills retrieved successfully';
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






