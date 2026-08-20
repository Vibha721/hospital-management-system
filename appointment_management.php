<?php

// CONNECT TO DATABASE
$conn = new mysqli("localhost", "root", "", "hospital_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// RUN THE SELECT QUERY
$sql = "SELECT a.id, p.name AS patient_name, 
               d.name AS doctor_name,
               a.appointment_date, a.appointment_time, a.status, a.notes
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $conn->query($sql);
if (!$result) { die("Query failed: " . $conn->error); }

?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Records</title>
</head>
<body>

<h2>Appointments List</h2>

<?php
if ($result->num_rows > 0) {

    echo "<table border='1' cellpadding='6'>
            <tr>
                <th>ID</th>
                <th>Patient Name</th>
                <th>Doctor Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>";

    while ($row = $result->fetch_assoc()) {

        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['patient_name']}</td>
                <td>{$row['doctor_name']}</td>
                <td>{$row['appointment_date']}</td>
                <td>{$row['appointment_time']}</td>
                <td>{$row['status']}</td>
                <td>{$row['notes']}</td>
              </tr>";
    }

    echo "</table>";

} else {
    echo "<h3>No appointment records found.</h3>";
}

$conn->close();
?>

</body>
</html>
