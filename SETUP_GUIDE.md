# Lost & Found Portal - Setup Guide

## Quick Setup Options

### Option 1: Multi-Server Setup (Recommended for Development)

1. **Start the servers using the batch file:**
   ```
   Double-click: start_servers.bat
   ```

2. **Access the application:**
   - Frontend: http://localhost:8082
   - User Management API: http://localhost:8080
   - Item Management API: http://localhost:8081

### Option 2: Single WAMP Server Setup (Easier)

1. **Ensure WAMP is running**
   - Start WAMP64
   - Make sure Apache and MySQL are green

2. **Import the database:**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Import: `lostfound_db.sql`

3. **Use single server config:**
   - Rename `ServerC/config.php` to `ServerC/config_multi.php`
   - Rename `ServerC/config_single_server.php` to `ServerC/config.php`

4. **Access the application:**
   - Frontend: http://localhost/Lostnfound/ServerC/

## Testing the APIs

1. **Run the API test:**
   - Multi-server: http://localhost:8082/api_test.php
   - Single server: http://localhost/Lostnfound/api_test.php

## Default Accounts

- **Admin:** username: `admin`, password: `admin123`
- **User:** username: `testuser`, password: `user123`

## Troubleshooting

### API Calls Failing

1. **Check if servers are running:**
   - Multi-server: Check if all three command windows are open
   - Single server: Check if WAMP Apache is green

2. **Check database connection:**
   - Verify MySQL is running
   - Check database credentials in config files
   - Ensure `lostfound_db` database exists

3. **Check CORS issues:**
   - APIs include CORS headers
   - Session cookies should be shared

4. **Check file permissions:**
   - Ensure PHP can read/write to session directory
   - Check upload directory permissions

### Common Issues

- **"Cannot connect to server"**: Server not running on expected port
- **"Database connection failed"**: MySQL not running or wrong credentials
- **"Invalid API response"**: Check PHP error logs for syntax errors
- **Session issues**: Clear browser cookies and try again

## File Structure

```
Lostnfound/
├── ServerA/          # User Management (Port 8080)
│   ├── api/
│   │   ├── register_user.php
│   │   ├── verify_user.php
│   │   └── session_status.php
│   └── config.php
├── ServerB/          # Item Management (Port 8081)
│   ├── api/
│   └── config.php
├── ServerC/          # Frontend (Port 8082)
│   ├── config.php
│   ├── user_login.php
│   ├── user_register.php
│   └── ...
├── start_servers.bat # Multi-server startup
├── api_test.php     # API testing tool
└── lostfound_db.sql # Database schema
```
