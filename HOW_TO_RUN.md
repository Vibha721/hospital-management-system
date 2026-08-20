# How to Run PHP Files

## ❌ What NOT to do:
```bash
php patient_management.php  # ❌ This won't work - PHP not in PATH
```

## ✅ Correct Ways to Run:

### Method 1: Use Web Browser (Recommended)
Since the PHP server is already running, just open in your browser:

**Patient Management:**
```
http://localhost:8000/patient_management.php
```

**Test Connection:**
```
http://localhost:8000/test_connection.php
```

### Method 2: Use the Helper Script
```bash
./open_app.sh
```
This will automatically open the app in your browser.

### Method 3: Use XAMPP's Apache Server
1. Start **Apache** in XAMPP Control Panel
2. Copy files to: `/Applications/XAMPP/xamppfiles/htdocs/`
3. Access: `http://localhost/patient_management.php`

### Method 4: Run PHP CLI (for testing only)
If you want to test PHP syntax (not recommended for web apps):
```bash
/Applications/XAMPP/xamppfiles/bin/php patient_management.php
```
Note: This will just output HTML text, not a working web page.

## 🔑 Key Points:

1. **PHP files are web applications** - they need a web server
2. **Your server is running** on `localhost:8000`
3. **Open in browser** - don't run from terminal
4. **Forms need POST requests** - which only work via web browser

## 🚀 Quick Start:

1. Make sure server is running:
   ```bash
   ps aux | grep "php.*8000"
   ```

2. Open in browser:
   ```
   http://localhost:8000/patient_management.php
   ```

3. That's it! The forms will work properly.

## 📝 Why "command not found: php"?

- PHP is not in your system PATH
- XAMPP's PHP is at: `/Applications/XAMPP/xamppfiles/bin/php`
- But you don't need to use CLI - use the web browser instead!






