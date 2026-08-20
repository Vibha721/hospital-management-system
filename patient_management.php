<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Patient Management System</title>
</head>
<body>
	<h2>Patient Management System</h2>

	<!-- INSERT FORM -->
	<form method="POST">
		<h3>Add a Patient</h3>
		<label>ID:</label> <input type="number" name="id" required><br>
		<label>Name:</label> <input type="text" name="name" required><br>
		<label>Age:</label> <input type="number" name="age" required><br>
		<label>Phone:</label> <input type="text" name="phone" required><br>
		<label>Email:</label> <input type="email" name="email" required><br>
		<button type="submit" name="action" value="insert">Insert</button>
	</form>

	<!-- DELETE FORM -->
	<form method="POST">
		<h3>Delete a Patient</h3>
		<label>ID:</label> <input type="number" name="id_delete" required><br>
		<button type="submit" name="action" value="delete">Delete</button>
	</form>

	<!-- DISPLAY FORM -->
	<form method="POST">
		<h3>View All Patients</h3>
		<button type="submit" name="action" value="display">Display All</button>
	</form>

	<?php
	// Connect to database
	$conn = new mysqli('localhost', 'root', '', 'hospital_db');

	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$action = $_POST['action'];

		// INSERT a patient
		if ($action == 'insert') {
			$id     = (int)$_POST['id'];
			$name   = $conn->real_escape_string($_POST['name']);
			$age    = (int)$_POST['age'];
			$phone  = $conn->real_escape_string($_POST['phone']);
			$email  = $conn->real_escape_string($_POST['email']);

			$query = "INSERT INTO patients (id, name, age, phone, email)
					  VALUES ($id, '$name', $age, '$phone', '$email')";

			if ($conn->query($query)) {
				echo "<p style='color:green;'>Patient '$name' inserted successfully.</p>";
			} else {
				echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
			}
		}

		// DELETE a patient
		elseif ($action == 'delete') {
			if (!empty($_POST['id_delete'])) {
				$id = (int)$_POST['id_delete'];
				$query = "DELETE FROM patients WHERE id = $id";

				if ($conn->query($query)) {
					if ($conn->affected_rows > 0) {
						echo "<p style='color:green;'>Patient with ID $id deleted successfully.</p>";
					} else {
						echo "<p style='color:orange;'>No patient found with ID $id.</p>";
					}
				} else {
					echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
				}
			}
		}

		// DISPLAY all patients
		elseif ($action == 'display') {
			$result = $conn->query("SELECT * FROM patients");

			if ($result && $result->num_rows > 0) {
				echo "<h3>Patient Records</h3>";
				echo "<table border='1' cellpadding='5'>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Age</th>
							<th>Phone</th>
							<th>Email</th>
						</tr>";

				while ($row = $result->fetch_assoc()) {
					echo "<tr>
							<td>{$row['id']}</td>
							<td>{$row['name']}</td>
							<td>{$row['age']}</td>
							<td>{$row['phone']}</td>
							<td>{$row['email']}</td>
						  </tr>";
				}

				echo "</table>";
			} else {
				echo "<p>No patient records found.</p>";
			}
		}
	}

	$conn->close();
	?>
</body>
</html>
