# Using XAMPP with Hospital Management System

## Quick Start Options

### Option 1: Use XAMPP's Apache Server (Easiest)

1. **Start XAMPP Control Panel**
2. **Start Apache** from XAMPP Control Panel
3. **Copy your project** to XAMPP htdocs:
   ```bash
   cp -r "/Users/vibhagothe/Desktop/hospital:clinic management" /Applications/XAMPP/xamppfiles/htdocs/
   ```
4. **Access via browser:**
   ```
   http://localhost/hospital:clinic management/frontend/index.html
   ```

### Option 2: Use the Startup Script

Run the provided script:
```bash
cd "/Users/vibhagothe/Desktop/hospital:clinic management"
./start_server.sh
```

Then access: `http://localhost:8000/frontend/index.html`

### Option 3: Use XAMPP PHP Directly

Use the full path to XAMPP's PHP:
```bash
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
```

### Option 4: Add PHP to PATH (Permanent)

Add this to your `~/.zshrc` file:
```bash
export PATH="/Applications/XAMPP/xamppfiles/bin:$PATH"
```

Then reload:
```bash
source ~/.zshrc
```

Now you can use `php` command directly.

## Database Setup

1. **Start MySQL** from XAMPP Control Panel
2. **Create database:**
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -u root -p < backend/sql/schema.sql
   ```
   (Default XAMPP MySQL password is usually empty, just press Enter)

3. **Or use phpMyAdmin:**
   - Go to: http://localhost/phpmyadmin
   - Import `backend/sql/schema.sql`

## Update Database Config

Edit `backend/config/database.php`:
```php
private $username = "root";
private $password = "";  // XAMPP default is empty
```

## Troubleshooting

- **PHP not found:** Use full path: `/Applications/XAMPP/xamppfiles/bin/php`
- **Port 8000 in use:** Change port: `php -S localhost:8080`
- **Database connection fails:** Make sure MySQL is running in XAMPP






