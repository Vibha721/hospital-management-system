# HealthCare Pro - Hospital Management System

A comprehensive single-page application (SPA) for managing hospital/clinic operations including patients, doctors, appointments, billing, medicine inventory, lab tests, and staff management.

## 🚀 Features

### Core Modules

1. **Dashboard**
   - Real-time statistics (Total Patients, Doctors, Today's Appointments, Monthly Revenue)
   - Recent appointments display
   - Quick action buttons for common tasks

2. **Patient Management**
   - Add, edit, delete patient records
   - Search patients by name, phone, or email
   - Patient information: Name, Age, Phone, Email

3. **Doctor Management**
   - Manage doctor profiles
   - Track specialization, qualifications, experience
   - Contact information management

4. **Appointment Management**
   - Schedule and manage appointments
   - Link appointments to patients and doctors
   - Track appointment status (Scheduled, Completed, Cancelled)
   - Date and time management

5. **Billing System**
   - Generate bills with auto-generated bill numbers
   - Multiple charge types (Consultation, Medicine, Lab, Other)
   - Additional items support
   - Discount calculation
   - Payment method tracking (Cash, Credit Card, Debit Card, UPI, Bank Transfer, Cheque)
   - Real-time total calculation

6. **Medicine Inventory**
   - Track medicine stock
   - Manage suppliers and batch numbers
   - Expiry date tracking
   - Low stock warnings
   - Price and quantity management

7. **Lab Tests**
   - Manage laboratory tests
   - Link tests to patients and doctors
   - Track test results and status
   - Test cost management

8. **Staff Management**
   - Employee records management
   - Department and role tracking
   - Shift management
   - Salary tracking
   - Employee status (Active, Inactive, On Leave, Terminated)

## 🛠️ Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Server**: PHP Built-in Server / XAMPP Apache

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (PHP built-in server or XAMPP)
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 🔧 Installation

### Option 1: Using XAMPP (Recommended)

1. **Install XAMPP**
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start Apache and MySQL from XAMPP Control Panel

2. **Setup Database**
   ```sql
   CREATE DATABASE hospital_db;
   ```
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `hospital_db`
   - The application will automatically create tables when you first use each module

3. **Copy Project Files**
   - Copy all project files to `C:\xampp\htdocs\hospital-management\` (Windows) or `/Applications/XAMPP/xamppfiles/htdocs/hospital-management/` (Mac)

4. **Access Application**
- Open browser and navigate to: `http://localhost/hospital-management/login.php`

### Option 2: Using PHP Built-in Server

1. **Navigate to Project Directory**
   ```bash
   cd "/Users/vibhagothe/Desktop/hospital:clinic management"
   ```

2. **Start PHP Server**
   ```bash
   ./start_server.sh
   ```
   Or manually:
   ```bash
   /Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
   ```

3. **Access Application**
- Open browser and navigate to: `http://localhost:8000/login.php`

### 🔐 Authentication

- Every session now starts at `login.php`
- Default administrator credentials: `admin / admin123`
- Credentials are stored in the `users` table (auto-created)
- After login you are redirected to `index.php`
- Click **Log out** in the top-right badge to end the session

## 📁 Project Structure

```
hospital:clinic management/
├── index.html                 # Main frontend application (SPA)
├── api.php                    # Patient management API
├── api_doctors.php            # Doctor management API
├── api_appointments.php       # Appointment management API
├── api_billing.php            # Billing management API
├── api_medicines.php          # Medicine inventory API
├── api_lab_tests.php          # Lab tests API
├── api_staff.php              # Staff management API
├── patient_management.php     # Standalone patient management (testing)
├── doctor_management.php      # Standalone doctor management (testing)
├── appointment_management.php  # Standalone appointment management (testing)
├── start_server.sh            # Script to start PHP built-in server
├── open_app.sh                # Script to open application in browser
└── README.md                  # This file
```

## 🗄️ Database Schema

The application automatically creates the following tables:

### Patients Table
- `id` (INT, Primary Key)
- `name` (VARCHAR)
- `age` (INT)
- `phone` (VARCHAR)
- `email` (VARCHAR)

### Doctors Table
- `id` (INT, Primary Key, Auto-generated)
- `name` or `full name` (VARCHAR)
- `specialization` (VARCHAR)
- `phone` (VARCHAR)
- `email` (VARCHAR)
- `experience` (INT)
- `qualification` (VARCHAR)

### Appointments Table
- `id` (INT, Primary Key)
- `patient_id` (INT)
- `doctor_id` (INT)
- `appointment_date` (DATE)
- `appointment_time` (TIME)
- `status` (VARCHAR)
- `notes` (TEXT)

### Bills Table
- `id` (INT, Primary Key)
- `bill_number` (VARCHAR, Unique)
- `bill_date` (DATE)
- `patient_id` (INT)
- `consultation_fee` (DECIMAL)
- `medicine_charges` (DECIMAL)
- `lab_charges` (DECIMAL)
- `other_charges` (DECIMAL)
- `additional_items` (TEXT, JSON)
- `discount` (DECIMAL)
- `total_amount` (DECIMAL)
- `amount_paid` (DECIMAL)
- `payment_method` (VARCHAR)
- `status` (VARCHAR)
- `notes` (TEXT)

### Medicines Table
- `id` (INT, Primary Key)
- `medicine_name` (VARCHAR)
- `generic_name` (VARCHAR)
- `category` (VARCHAR)
- `manufacturer` (VARCHAR)
- `unit_price` (DECIMAL)
- `stock_quantity` (INT)
- `expiry_date` (DATE)
- `batch_number` (VARCHAR)
- `supplier_name` (VARCHAR)
- `supplier_contact` (VARCHAR)
- `description` (TEXT)

### Lab Tests Table
- `id` (INT, Primary Key)
- `test_name` (VARCHAR)
- `test_category` (VARCHAR)
- `patient_id` (INT)
- `doctor_id` (INT)
- `test_date` (DATE)
- `test_cost` (DECIMAL)
- `status` (VARCHAR)
- `test_result` (TEXT)
- `summary` (TEXT)
- `notes` (TEXT)

### Staff Table
- `id` (INT, Primary Key)
- `full_name` (VARCHAR)
- `role` (VARCHAR)
- `employee_id` (VARCHAR, Unique)
- `phone` (VARCHAR)
- `email` (VARCHAR)
- `department` (VARCHAR)
- `date_of_joining` (DATE)
- `monthly_salary` (DECIMAL)
- `shift` (VARCHAR)
- `status` (VARCHAR)
- `address` (TEXT)

## 🔌 API Endpoints

All APIs use POST requests with FormData and support the following actions:

### Patient API (`api.php`)
- `action=insert` - Create new patient
- `action=update` - Update existing patient
- `action=delete` - Delete patient
- `action=get_all` - Get all patients
- `action=get_one` - Get single patient

### Doctor API (`api_doctors.php`)
- `action=insert` - Create new doctor
- `action=update` - Update existing doctor
- `action=delete` - Delete doctor
- `action=get_all` - Get all doctors
- `action=get_one` - Get single doctor

### Appointment API (`api_appointments.php`)
- `action=insert` - Create new appointment
- `action=update` - Update existing appointment
- `action=delete` - Delete appointment
- `action=get_all` - Get all appointments
- `action=get_one` - Get single appointment
- `action=get_options` - Get patients and doctors for dropdowns

### Billing API (`api_billing.php`)
- `action=insert` - Create new bill
- `action=update` - Update existing bill
- `action=delete` - Delete bill
- `action=get_all` - Get all bills
- `action=get_one` - Get single bill

### Medicine API (`api_medicines.php`)
- `action=insert` - Create new medicine
- `action=update` - Update existing medicine
- `action=delete` - Delete medicine
- `action=get_all` - Get all medicines
- `action=get_one` - Get single medicine

### Lab Test API (`api_lab_tests.php`)
- `action=insert` - Create new lab test
- `action=update` - Update existing lab test
- `action=delete` - Delete lab test
- `action=get_all` - Get all lab tests
- `action=get_one` - Get single lab test

### Staff API (`api_staff.php`)
- `action=insert` - Create new staff member
- `action=update` - Update existing staff member
- `action=delete` - Delete staff member
- `action=get_all` - Get all staff
- `action=get_one` - Get single staff member

## 🎯 Usage

### Starting the Application

**Using XAMPP:**
1. Start Apache and MySQL from XAMPP Control Panel
2. Open `http://localhost/hospital-management/index.html`

**Using PHP Built-in Server:**
```bash
./start_server.sh
# Then open http://localhost:8000/index.html
```

### Navigation

- Click on menu items in the left sidebar to navigate between modules
- Dashboard is the default page when the application loads
- Each module has its own search functionality

### Adding Records

1. Click the "+ Add New..." button in the top right
2. Fill in the required fields (marked with *)
3. Click "Save" to create the record

### Editing Records

1. Click the "Edit" button next to any record in the table
2. Modify the fields in the modal
3. Click "Save" to update

### Deleting Records

1. Click the "Delete" button next to any record
2. Confirm the deletion in the popup

### Searching

- Use the search bar at the top of each module
- Search is real-time with debouncing (300ms delay)
- Search works across multiple fields

## 🔐 Database Configuration

Default database configuration:
- **Host**: localhost
- **Username**: root
- **Password**: (empty)
- **Database**: hospital_db

To change these settings, edit the `mysqli` connection in each API file:
```php
$conn = new mysqli('localhost', 'root', '', 'hospital_db');
```

## 📝 Notes

- All tables are created automatically when you first access each module
- The application handles different table structures (e.g., doctors table with or without `id` column)
- Bill numbers are auto-generated using timestamp format: `BILL-{timestamp}`
- Total amounts in billing are calculated automatically
- Dates are formatted for display but stored in MySQL DATE format
- The application uses a single-page architecture with client-side routing

## 🐛 Troubleshooting

### "Failed to fetch" Error
- Ensure PHP server is running
- Check that you're accessing via `http://` not `file://`
- Verify database connection settings

### Database Connection Error
- Ensure MySQL is running
- Check database name is `hospital_db`
- Verify username and password in API files

### Tables Not Created
- Check MySQL user has CREATE TABLE permissions
- Verify database exists
- Check PHP error logs

### Dropdowns Empty
- Ensure related records exist (e.g., patients for appointments)
- Check browser console for JavaScript errors
- Verify API endpoints are accessible

## 📄 License

This project is open source and available for educational purposes.

## 👨‍💻 Development

### Adding New Modules

1. Create API file: `api_{module_name}.php`
2. Add page section in `index.html`
3. Add modal form
4. Add JavaScript functions for CRUD operations
5. Update navigation function

### Customization

- Modify CSS in `<style>` section of `index.html`
- Update API endpoints in JavaScript functions
- Customize database schema in API files

## 📞 Support

For issues or questions:
1. Check the browser console (F12) for JavaScript errors
2. Check PHP error logs
3. Verify database connection
4. Ensure all API files are accessible

---

**Version**: 1.0.0  
**Last Updated**: November 2025

# hospital-management-system
# hospital-management-system
