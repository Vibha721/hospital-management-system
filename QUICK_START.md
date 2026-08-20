# Quick Start Guide

## ✅ Your PHP Server is Running!

The server is already running on **port 8000**.

## 🌐 Access Your Application

### Option 1: Test Connection
```
http://localhost:8000/test_connection.php
```
This will show you if the database connection is working.

### Option 2: Patient Management
```
http://localhost:8000/patient_management.php
```
This is your main patient management interface.

## 🔧 If Something's Not Working

### Check 1: Is the server running?
```bash
ps aux | grep "php.*8000"
```
If you see a process, the server is running.

### Check 2: Start the server (if not running)
```bash
cd "/Users/vibhagothe/Desktop/hospital:clinic management"
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
```

### Check 3: Database Connection
1. Open XAMPP Control Panel
2. Make sure **MySQL** is running (green)
3. Test connection: http://localhost:8000/test_connection.php

### Check 4: Database Setup
If the database doesn't exist, create it:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS hospital_db;"
```

## 📝 Common Issues

### "Connection failed"
- **Fix:** Start MySQL in XAMPP Control Panel

### "Table doesn't exist"
- **Fix:** Create the table:
```sql
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100)
);
```

### "Page not found"
- **Fix:** Make sure you're accessing via `http://localhost:8000/` not `file://`

## 🎯 Current Status

✅ PHP Server: Running on port 8000  
✅ Database: hospital_db  
✅ Table: patients (exists)  
✅ File: patient_management.php (working)

## 🚀 Next Steps

1. Open: http://localhost:8000/test_connection.php
2. If connection works, open: http://localhost:8000/patient_management.php
3. Add your first patient!






