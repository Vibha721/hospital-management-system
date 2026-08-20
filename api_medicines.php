<?php
/**
 * Medicine Inventory Management API
 * Handles AJAX requests from frontend for medicines
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

// Create medicines table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    category VARCHAR(100),
    manufacturer VARCHAR(255),
    unit_price DECIMAL(10, 2) DEFAULT 0.00,
    stock_quantity INT DEFAULT 0,
    expiry_date DATE,
    batch_number VARCHAR(100),
    supplier_name VARCHAR(255),
    supplier_contact VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (medicine_name),
    INDEX idx_category (category),
    INDEX idx_expiry (expiry_date),
    INDEX idx_batch (batch_number)
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

        // INSERT a medicine
        if ($action == 'insert') {
            $id              = (int)($_POST['id'] ?? 0);
            $medicine_name   = $conn->real_escape_string($_POST['medicine_name'] ?? '');
            $generic_name    = $conn->real_escape_string($_POST['generic_name'] ?? '');
            $category        = $conn->real_escape_string($_POST['category'] ?? '');
            $manufacturer    = $conn->real_escape_string($_POST['manufacturer'] ?? '');
            $unit_price      = floatval($_POST['unit_price'] ?? 0);
            $stock_quantity  = (int)($_POST['stock_quantity'] ?? 0);
            $expiry_date     = $conn->real_escape_string($_POST['expiry_date'] ?? '');
            $batch_number    = $conn->real_escape_string($_POST['batch_number'] ?? '');
            $supplier_name   = $conn->real_escape_string($_POST['supplier_name'] ?? '');
            $supplier_contact = $conn->real_escape_string($_POST['supplier_contact'] ?? '');
            $description     = $conn->real_escape_string($_POST['description'] ?? '');

            if (empty($medicine_name)) {
                $response['message'] = 'Medicine name is required';
            } else {
                // For new medicines, let MySQL auto-generate the ID
                if ($id > 0) {
                    $query = "INSERT INTO medicines (id, medicine_name, generic_name, category, manufacturer, unit_price, stock_quantity, expiry_date, batch_number, supplier_name, supplier_contact, description)
                              VALUES ($id, '$medicine_name', '$generic_name', '$category', '$manufacturer', $unit_price, $stock_quantity, " . 
                              ($expiry_date ? "'$expiry_date'" : "NULL") . ", '$batch_number', '$supplier_name', '$supplier_contact', '$description')";
                } else {
                    $query = "INSERT INTO medicines (medicine_name, generic_name, category, manufacturer, unit_price, stock_quantity, expiry_date, batch_number, supplier_name, supplier_contact, description)
                              VALUES ('$medicine_name', '$generic_name', '$category', '$manufacturer', $unit_price, $stock_quantity, " . 
                              ($expiry_date ? "'$expiry_date'" : "NULL") . ", '$batch_number', '$supplier_name', '$supplier_contact', '$description')";
                }

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Medicine '$medicine_name' inserted successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            }
        }

        // DELETE a medicine
        elseif ($action == 'delete') {
            $id = (int)($_POST['id_delete'] ?? 0);
            
            if ($id > 0) {
                $query = "DELETE FROM medicines WHERE id = $id";

                if ($conn->query($query)) {
                    if ($conn->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = "Medicine with ID $id deleted successfully.";
                    } else {
                        $response['message'] = "No medicine found with ID $id.";
                    }
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Invalid medicine ID';
            }
        }

        // GET all medicines
        elseif ($action == 'display' || $action == 'get_all') {
            $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
            
            $query = "SELECT * FROM medicines";
            
            if (!empty($search)) {
                $query .= " WHERE medicine_name LIKE '%$search%' OR 
                          generic_name LIKE '%$search%' OR 
                          category LIKE '%$search%' OR
                          manufacturer LIKE '%$search%' OR
                          batch_number LIKE '%$search%' OR
                          supplier_name LIKE '%$search%'";
            }
            
            $query .= " ORDER BY medicine_name ASC";
            
            $result = $conn->query($query);

            if ($result) {
                $medicines = [];
                while ($row = $result->fetch_assoc()) {
                    $medicines[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $medicines;
                $response['count'] = count($medicines);
                $response['message'] = 'Medicines retrieved successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Error: ' . $conn->error;
                http_response_code(500);
            }
        }

        // GET single medicine
        elseif ($action == 'get_one') {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id > 0) {
                $query = "SELECT * FROM medicines WHERE id = $id";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                    $response['message'] = 'Medicine retrieved successfully';
                } else {
                    $response['message'] = 'Medicine not found';
                }
            } else {
                $response['message'] = 'Invalid medicine ID';
            }
        }

        // UPDATE a medicine
        elseif ($action == 'update') {
            $id              = (int)($_POST['id'] ?? 0);
            $medicine_name   = $conn->real_escape_string($_POST['medicine_name'] ?? '');
            $generic_name    = $conn->real_escape_string($_POST['generic_name'] ?? '');
            $category        = $conn->real_escape_string($_POST['category'] ?? '');
            $manufacturer    = $conn->real_escape_string($_POST['manufacturer'] ?? '');
            $unit_price      = floatval($_POST['unit_price'] ?? 0);
            $stock_quantity  = (int)($_POST['stock_quantity'] ?? 0);
            $expiry_date     = $conn->real_escape_string($_POST['expiry_date'] ?? '');
            $batch_number    = $conn->real_escape_string($_POST['batch_number'] ?? '');
            $supplier_name   = $conn->real_escape_string($_POST['supplier_name'] ?? '');
            $supplier_contact = $conn->real_escape_string($_POST['supplier_contact'] ?? '');
            $description     = $conn->real_escape_string($_POST['description'] ?? '');

            if ($id > 0 && !empty($medicine_name)) {
                $expiryDateValue = $expiry_date ? "'$expiry_date'" : "NULL";
                $query = "UPDATE medicines SET 
                         medicine_name = '$medicine_name',
                         generic_name = '$generic_name',
                         category = '$category',
                         manufacturer = '$manufacturer',
                         unit_price = $unit_price,
                         stock_quantity = $stock_quantity,
                         expiry_date = $expiryDateValue,
                         batch_number = '$batch_number',
                         supplier_name = '$supplier_name',
                         supplier_contact = '$supplier_contact',
                         description = '$description'
                         WHERE id = $id";

                if ($conn->query($query)) {
                    $response['success'] = true;
                    $response['message'] = "Medicine updated successfully.";
                } else {
                    $response['message'] = 'Error: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Medicine ID and name are required';
            }
        }
    } else {
        // GET request - return all medicines
        $query = "SELECT * FROM medicines ORDER BY medicine_name ASC";
        $result = $conn->query($query);

        if ($result) {
            $medicines = [];
            while ($row = $result->fetch_assoc()) {
                $medicines[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $medicines;
            $response['count'] = count($medicines);
            $response['message'] = 'Medicines retrieved successfully';
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






