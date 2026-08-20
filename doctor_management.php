<?php
/**
 * Doctor Management System
 * Similar to patient_management.php but for doctors
 */

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'hospital_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create doctors table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    experience INT,
    qualification VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createTable);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // INSERT a doctor
    if ($action == 'insert') {
        $id             = (int)($_POST['id'] ?? 0);
        $name           = $conn->real_escape_string($_POST['name'] ?? '');
        $specialization = $conn->real_escape_string($_POST['specialization'] ?? '');
        $phone          = $conn->real_escape_string($_POST['phone'] ?? '');
        $email          = $conn->real_escape_string($_POST['email'] ?? '');
        $experience     = (int)($_POST['experience'] ?? 0);
        $qualification  = $conn->real_escape_string($_POST['qualification'] ?? '');

        $query = "INSERT INTO doctors (id, name, specialization, phone, email, experience, qualification)
                  VALUES ($id, '$name', '$specialization', '$phone', '$email', $experience, '$qualification')";

        if ($conn->query($query)) {
            echo "<p style='color:green;'>Doctor '$name' inserted successfully.</p>";
        } else {
            echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
        }
    }

    // DELETE a doctor
    elseif ($action == 'delete') {
        if (!empty($_POST['id_delete'])) {
            $id = (int)$_POST['id_delete'];
            $query = "DELETE FROM doctors WHERE id = $id";

            if ($conn->query($query)) {
                if ($conn->affected_rows > 0) {
                    echo "<p style='color:green;'>Doctor with ID $id deleted successfully.</p>";
                } else {
                    echo "<p style='color:orange;'>No doctor found with ID $id.</p>";
                }
            } else {
                echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
            }
        }
    }

    // DISPLAY all doctors
    elseif ($action == 'display') {
        $result = $conn->query("SELECT * FROM doctors");

        if ($result && $result->num_rows > 0) {
            echo "<h3>Doctor Records</h3>";
            echo "<table border='1' cellpadding='5'>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Experience (Years)</th>
                        <th>Qualification</th>
                    </tr>";

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['specialization']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['experience']}</td>
                        <td>{$row['qualification']}</td>
                      </tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No doctor records found.</p>";
        }
    }
}

$conn->close();
?>
