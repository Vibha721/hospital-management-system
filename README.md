# HealthCare Pro - Hospital Management System

A single-page hospital/clinic management app covering patients, doctors, appointments, billing, medicine inventory, lab tests, and staff.

## Tech Stack

- **Frontend**: HTML5, CSS3, vanilla JavaScript (single-page app in `index.html`)
- **Backend**: PHP (mysqli) — `auth.php`, `api.php`, `api_doctors.php`, `api_appointments.php`, `api_billing.php`, `api_medicines.php`, `api_lab_tests.php`, `api_staff.php`
- **Database**: MySQL (`hospital_db`)
- **Server**: Apache + MySQL via XAMPP

## Running the Project (XAMPP on Windows)

1. **Install XAMPP** from https://www.apachefriends.org/ (installs to `C:\xampp`).

2. **Copy project files** into `C:\xampp\htdocs\hospital\`.

3. **Start Apache and MySQL** — via the XAMPP Control Panel, or from a terminal:
   ```bash
   C:\xampp\apache\bin\httpd.exe &
   C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone &
   ```

4. **Create the database**:
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS hospital_db;"
   ```
   Most tables (`users`, `doctors`, `appointments`, `bills`, `medicines`, `lab_tests`, `staff`) auto-create on first use. The `patients` table does not, so create it manually:
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root hospital_db -e "CREATE TABLE IF NOT EXISTS patients (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, age INT NOT NULL, phone VARCHAR(20), email VARCHAR(100));"
   ```

5. **Open the app**: http://localhost/hospital/login.php
   Default login: `admin` / `admin123` (or sign up via `signup.php`)

## Database Config

Default connection used in every API file (`new mysqli('localhost', 'root', '', 'hospital_db')`):
- Host: `localhost`, User: `root`, Password: *(empty)*, DB: `hospital_db`

To inspect the database directly, use phpMyAdmin: http://localhost/phpmyadmin/
