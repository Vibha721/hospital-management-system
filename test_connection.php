<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { background: #d1fae5; padding: 15px; border-radius: 5px; color: #065f46; margin: 10px 0; }
        .error { background: #fee2e2; padding: 15px; border-radius: 5px; color: #991b1b; margin: 10px 0; }
        .info { background: #dbeafe; padding: 15px; border-radius: 5px; color: #1e40af; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection Test</h1>
    
    <?php
    // Test database connection
    $conn = new mysqli('localhost', 'root', '', 'hospital_db');
    
    if ($conn->connect_error) {
        echo '<div class="error">❌ Connection failed: ' . $conn->connect_error . '</div>';
        echo '<div class="info"><strong>Fix:</strong><br>1. Make sure MySQL is running in XAMPP<br>2. Check database credentials</div>';
    } else {
        echo '<div class="success">✅ Database connection successful!</div>';
        
        // Check if patients table exists
        $result = $conn->query("SHOW TABLES LIKE 'patients'");
        if ($result && $result->num_rows > 0) {
            echo '<div class="success">✅ Table "patients" exists</div>';
            
            // Get table structure
            echo '<h2>Table Structure:</h2>';
            $columns = $conn->query("DESCRIBE patients");
            echo '<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>';
            while ($row = $columns->fetch_assoc()) {
                echo '<tr><td>' . $row['Field'] . '</td><td>' . $row['Type'] . '</td><td>' . $row['Null'] . '</td><td>' . $row['Key'] . '</td></tr>';
            }
            echo '</table>';
            
            // Get patient count
            $count = $conn->query("SELECT COUNT(*) as total FROM patients");
            $total = $count->fetch_assoc()['total'];
            echo '<div class="info">📊 Total Patients: <strong>' . $total . '</strong></div>';
            
            // Display all patients
            if ($total > 0) {
                echo '<h2>All Patients:</h2>';
                $patients = $conn->query("SELECT * FROM patients");
                echo '<table><tr><th>ID</th><th>Name</th><th>Age</th><th>Phone</th><th>Email</th></tr>';
                while ($row = $patients->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td>' . $row['name'] . '</td>';
                    echo '<td>' . $row['age'] . '</td>';
                    echo '<td>' . $row['phone'] . '</td>';
                    echo '<td>' . $row['email'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="info">ℹ️ No patients in database yet.</div>';
            }
        } else {
            echo '<div class="error">❌ Table "patients" does not exist</div>';
            echo '<div class="info"><strong>Fix:</strong> Run: <code>mysql -u root -p hospital_db < schema.sql</code></div>';
        }
        
        $conn->close();
    }
    ?>
    
    <hr>
    <h2>Quick Links:</h2>
    <p>
        <a href="patient_management.php" style="padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;">
            📝 Go to Patient Management
        </a>
    </p>
    
    <h2>Server Info:</h2>
    <div class="info">
        <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
        <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server'; ?><br>
        <strong>Current URL:</strong> <?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
    </div>
</body>
</html>






