# Backend Documentation - Lost & Found Portal

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Server Infrastructure](#server-infrastructure)
3. [Database Design](#database-design)
4. [API Endpoints](#api-endpoints)
5. [Authentication System](#authentication-system)
6. [Configuration Management](#configuration-management)
7. [Security Implementation](#security-implementation)
8. [Error Handling](#error-handling)
9. [Inter-Server Communication](#inter-server-communication)

---

## Architecture Overview

### Three-Tier Distributed Architecture

The system uses three servers with clear separation of concerns:

- **Frontend**: User interface, API client only
- **ItemsServer**: Item CRUD operations, direct database access
- **UserServer**: User authentication, database hosting, file storage

All inter-server communication happens via HTTP REST APIs using cURL.

## Server Infrastructure

### Frontend (User Interface)

**Responsibilities**: Render pages, handle sessions, make API calls

**Key Files**:
- `index.php`, `items.php`, `report_lost.php`, `user_login.php`
- `config.php` - Frontend configuration
- `api_client.php` - OOP API client
- `assets/` - CSS, JavaScript, images

**Configuration**:
```php
function connectDB() {
    die("ERROR: Frontend cannot connect directly to the database. Use API calls.");
}

define('ITEMSSERVER_URL', ITEMSSERVER_API_URL);
define('USERSERVER_URL', USERSERVER_API_URL);
```

### ItemsServer (Item Management)

**Responsibilities**: Handle item CRUD operations, direct database access

**Key Files**:
```
ItemsServer/
├── config.php
├── db_setup.php
└── api/
    ├── add_item.php
    ├── get_all_items.php
    ├── get_item.php
    ├── get_user_items.php
    ├── update_item.php
    ├── delete_item.php
    └── health.php
```

**Database Connection**:
```php
function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    return $conn;
}
```

### UserServer (User Management)

**Responsibilities**: User registration, authentication, admin management

**Key Files**:
```
UserServer/
├── config.php
└── api/
    ├── register_user.php
    ├── verify_user.php
    ├── get_all_users.php
    ├── get_user_items.php
    ├── toggle_admin.php
    └── health.php
```

**Authentication Logic**:
```php
// Password hashing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Password verification
if (!password_verify($password, $user['password'])) {
    sendJSONResponse(['error' => 'Invalid password'], 401);
}
```

## Database Design

### MySQL Database Schema

**Database Name**: `lostnfound` (configurable via deployment_config.php)

### Table: `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,      -- Hashed with PASSWORD_DEFAULT
    is_admin TINYINT(1) DEFAULT 0,       -- 0 = regular user, 1 = admin
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Column Explanations**:
- **id**: Auto-incrementing primary key
- **username**: Unique identifier for login (max 50 chars)
- **email**: Contact email, also unique (max 100 chars)
- **password**: Bcrypt hashed password (255 chars for future algorithms)
- **is_admin**: Boolean flag for admin privileges
- **created_at**: Account creation timestamp

**Why These Design Choices**:
- **VARCHAR(255) for password**: Supports longer hashes if algorithm changes
- **Indexes on username/email**: Fast lookups during login
- **InnoDB Engine**: Supports transactions and foreign keys
- **UTF8MB4 Charset**: Supports emojis and international characters

---

### Table: `items`

```sql
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact VARCHAR(100) NOT NULL,      -- Email or phone
    image VARCHAR(255) DEFAULT NULL,     -- Filename only, not path
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Column Explanations**:
- **id**: Auto-incrementing primary key
- **user_id**: Foreign key to users table
- **title**: Short item description (max 200 chars)
- **description**: Detailed item description (unlimited)
- **type**: ENUM ensures only 'lost' or 'found'
- **location**: Where item was lost/found
- **contact**: How to contact the user
- **image**: Filename of uploaded image (nullable)
- **created_at**: When item was reported
- **updated_at**: Last modification time

**Why These Design Choices**:
- **ENUM for type**: Prevents invalid values, efficient storage
- **Foreign Key with CASCADE**: Auto-deletes items when user deleted
- **Index on type**: Fast filtering by lost/found
- **Index on created_at**: Fast sorting by date
- **Separate updated_at**: Track modifications independently

---

### Database Initialization (`ServerA/db_setup.php`)

```php
// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
mysqli_query($conn, $sql);

// Select database
mysqli_select_db($conn, $db_name);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (...)";
mysqli_query($conn, $sql);

// Create items table
$sql = "CREATE TABLE IF NOT EXISTS items (...)";
mysqli_query($conn, $sql);

// Create default admin user
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, email, password, is_admin) 
        VALUES ('admin', 'admin@university.edu', '$default_password', 1)
        ON DUPLICATE KEY UPDATE username=username";
mysqli_query($conn, $sql);
```

**Why This Approach**:
1. **Idempotent**: Can run multiple times safely
2. **Default Admin**: Ensures system is usable immediately
3. **IF NOT EXISTS**: Prevents errors on re-run
4. **ON DUPLICATE KEY**: Doesn't overwrite existing admin

---

## API Endpoints

### ServerA API Endpoints (Item Management)

#### 1. Add Item
**Endpoint**: `POST /api/add_item.php`

**Request Parameters**:
```php
$user_id = $_POST['user_id'];
$title = $_POST['title'];
$description = $_POST['description'];
$type = $_POST['type'];              // 'lost' or 'found'
$location = $_POST['location'];
$contact = $_POST['contact'];
$image_filename = $_POST['image_filename'];  // Optional
```

**Implementation**:
```php
// Validation
if (empty($user_id) || empty($title) || empty($description) || 
    empty($type) || empty($location) || empty($contact)) {
    sendJSONResponse(['error' => 'Please fill all required fields'], 400);
}

if (!in_array($type, ['lost', 'found'])) {
    sendJSONResponse(['error' => 'Invalid item type'], 400);
}

// SQL Injection Prevention
$user_id = mysqli_real_escape_string($conn, $user_id);
$title = mysqli_real_escape_string($conn, $title);
$description = mysqli_real_escape_string($conn, $description);
// ... more escaping

// Insert query
$sql = "INSERT INTO items (user_id, title, description, type, location, contact, image, created_at) 
        VALUES ('$user_id', '$title', '$description', '$type', '$location', '$contact', " . 
        ($image_filename ? "'$image_filename'" : "NULL") . ", NOW())";

if (mysqli_query($conn, $sql)) {
    $item_id = mysqli_insert_id($conn);
    sendJSONResponse([
        'success' => true,
        'item_id' => $item_id,
        'message' => 'Item added successfully'
    ]);
}
```

**Response**:
```json
{
    "success": true,
    "item_id": 42,
    "message": "Item added successfully"
}
```

**Why This Implementation**:
1. **Comprehensive Validation**: Checks all required fields
2. **SQL Injection Protection**: mysqli_real_escape_string on all inputs
3. **Type Validation**: ENUM ensures only valid types
4. **Return Item ID**: Client can reference the new item
5. **Consistent Response Format**: Always returns JSON

---

#### 2. Get All Items
**Endpoint**: `GET /api/get_all_items.php`

**Query Parameters**:
```
?type=lost              # Filter by type (optional)
&search=backpack        # Search in title/description/location (optional)
```

**Implementation**:
```php
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build dynamic SQL query
$sql = "SELECT i.*, u.username FROM items i 
        LEFT JOIN users u ON i.user_id = u.id 
        WHERE 1=1";  // Always true condition for easy appending

if (!empty($type) && in_array($type, ['lost', 'found'])) {
    $sql .= " AND i.type = '" . mysqli_real_escape_string($conn, $type) . "'";
}

if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (i.title LIKE '%$search_escaped%' 
              OR i.description LIKE '%$search_escaped%' 
              OR i.location LIKE '%$search_escaped%')";
}

$sql .= " ORDER BY i.created_at DESC";

$result = mysqli_query($conn, $sql);
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

// Get statistics
$stats = [
    'total' => count(mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM items"))),
    'lost_count' => count(mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM items WHERE type='lost'"))),
    'found_count' => count(mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM items WHERE type='found'")))
];

sendJSONResponse([
    'success' => true,
    'items' => $items,
    'count' => count($items),
    'stats' => $stats
]);
```

**Response**:
```json
{
    "success": true,
    "items": [
        {
            "id": 1,
            "user_id": 5,
            "username": "john_doe",
            "title": "Black iPhone 13",
            "description": "Lost near library...",
            "type": "lost",
            "location": "Main Library",
            "contact": "john@university.edu",
            "image": "abc123.jpg",
            "created_at": "2024-01-15 14:30:00"
        }
    ],
    "count": 1,
    "stats": {
        "total": 25,
        "lost_count": 12,
        "found_count": 13
    }
}
```

**Why This Implementation**:
1. **LEFT JOIN**: Includes username for display (graceful if user deleted)
2. **Dynamic Filtering**: Build SQL based on parameters provided
3. **LIKE Operator**: Enables partial text search
4. **Multiple Field Search**: Searches title, description, and location
5. **Statistics Included**: Reduces need for separate API call
6. **Ordered by Date**: Most recent items first

---

#### 3. Get User Items
**Endpoint**: `GET /api/get_user_items.php?user_id=5`

**Implementation**:
```php
$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    sendJSONResponse(['error' => 'User ID is required'], 400);
}

$sql = "SELECT * FROM items WHERE user_id = '" . 
       mysqli_real_escape_string($conn, $user_id) . "' 
       ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);
$items = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate user-specific statistics
$stats = [
    'total' => count($items),
    'lost_count' => count(array_filter($items, fn($i) => $i['type'] === 'lost')),
    'found_count' => count(array_filter($items, fn($i) => $i['type'] === 'found'))
];

sendJSONResponse([
    'success' => true,
    'items' => $items,
    'stats' => $stats
]);
```

**Why This Implementation**:
1. **User-Specific**: Only shows items belonging to the user
2. **Ordered by Date**: Newest items first
3. **Statistics**: User sees their personal contribution
4. **Array Functions**: Modern PHP for statistics calculation

---

#### 4. Update Item
**Endpoint**: `POST /api/update_item.php`

**Request Parameters**:
```php
$id = $_POST['id'];
$user_id = $_POST['user_id'];       // For ownership verification
$title = $_POST['title'];
$description = $_POST['description'];
$type = $_POST['type'];
$location = $_POST['location'];
$contact = $_POST['contact'];
```

**Implementation**:
```php
// Verify ownership
$check_sql = "SELECT user_id FROM items WHERE id = '" . 
             mysqli_real_escape_string($conn, $id) . "'";
$check_result = mysqli_query($conn, $check_sql);
$item = mysqli_fetch_assoc($check_result);

if (!$item || $item['user_id'] != $user_id) {
    sendJSONResponse(['error' => 'Unauthorized or item not found'], 403);
}

// Update query
$sql = "UPDATE items SET 
        title = '" . mysqli_real_escape_string($conn, $title) . "',
        description = '" . mysqli_real_escape_string($conn, $description) . "',
        type = '" . mysqli_real_escape_string($conn, $type) . "',
        location = '" . mysqli_real_escape_string($conn, $location) . "',
        contact = '" . mysqli_real_escape_string($conn, $contact) . "'
        WHERE id = '" . mysqli_real_escape_string($conn, $id) . "'";

if (mysqli_query($conn, $sql)) {
    sendJSONResponse([
        'success' => true,
        'message' => 'Item updated successfully'
    ]);
}
```

**Why This Implementation**:
1. **Ownership Verification**: Prevents users from editing others' items
2. **Separate Security Check**: Query before update
3. **403 Forbidden**: Proper HTTP status for unauthorized
4. **updated_at Auto-Updated**: Database trigger handles timestamp

---

#### 5. Delete Item
**Endpoint**: `POST /api/delete_item.php`

**Request Parameters**:
```php
$id = $_POST['id'];
$user_id = $_POST['user_id'];       // For ownership check
$is_admin = $_POST['is_admin'] ?? 0; // Admin override
```

**Implementation**:
```php
if (!$is_admin) {
    // Regular user: verify ownership
    $check_sql = "SELECT user_id FROM items WHERE id = '" . 
                 mysqli_real_escape_string($conn, $id) . "'";
    $check_result = mysqli_query($conn, $check_sql);
    $item = mysqli_fetch_assoc($check_result);
    
    if (!$item || $item['user_id'] != $user_id) {
        sendJSONResponse(['error' => 'Unauthorized'], 403);
    }
}

// Delete query
$sql = "DELETE FROM items WHERE id = '" . 
       mysqli_real_escape_string($conn, $id) . "'";

if (mysqli_query($conn, $sql)) {
    sendJSONResponse([
        'success' => true,
        'message' => 'Item deleted successfully'
    ]);
}
```

**Why This Implementation**:
1. **Admin Override**: Admins can delete any item
2. **Ownership Check**: Regular users can only delete their items
3. **Permanent Deletion**: No soft delete (could be added)
4. **Cascade Delete**: Foreign key handles orphaned records

---

### ServerB API Endpoints (User Management)

#### 1. Register User
**Endpoint**: `POST /api/register_user.php`

**Request Parameters**:
```php
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];
```

**Implementation**:
```php
// Validation
if (empty($username) || empty($email) || empty($password)) {
    sendJSONResponse(['error' => 'Please fill all required fields'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(['error' => 'Invalid email format'], 400);
}

// Check if user exists
$check_sql = "SELECT id FROM users 
              WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' 
              OR email = '" . mysqli_real_escape_string($conn, $email) . "'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    sendJSONResponse(['error' => 'Username or email already exists'], 400);
}

// Hash password and insert
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$username_escaped = mysqli_real_escape_string($conn, $username);
$email_escaped = mysqli_real_escape_string($conn, $email);

$sql = "INSERT INTO users (username, email, password, is_admin, created_at) 
        VALUES ('$username_escaped', '$email_escaped', '$hashed_password', 0, NOW())";

if (mysqli_query($conn, $sql)) {
    $user_id = mysqli_insert_id($conn);
    sendJSONResponse([
        'success' => true,
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
        'message' => 'User registered successfully'
    ]);
}
```

**Response**:
```json
{
    "success": true,
    "user_id": 10,
    "username": "john_doe",
    "email": "john@university.edu",
    "message": "User registered successfully"
}
```

**Why This Implementation**:
1. **Email Validation**: filter_var ensures proper format
2. **Uniqueness Check**: Prevents duplicate usernames/emails
3. **Password Hashing**: PASSWORD_DEFAULT uses bcrypt (secure)
4. **Default Non-Admin**: is_admin defaults to 0
5. **Return User Data**: Client can immediately log user in

---

#### 2. Verify User (Login)
**Endpoint**: `POST /api/verify_user.php`

**Request Parameters**:
```php
$username = $_POST['username'];
$password = $_POST['password'];
```

**Implementation**:
```php
// Validation
if (empty($username) || empty($password)) {
    sendJSONResponse(['error' => 'Username and password are required'], 400);
}

// Get user by username
$sql = "SELECT id, username, email, password, is_admin FROM users 
        WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    sendJSONResponse(['error' => 'User not found'], 401);
}

$user = mysqli_fetch_assoc($result);

// Verify password
if (!password_verify($password, $user['password'])) {
    sendJSONResponse(['error' => 'Invalid password'], 401);
}

// Return user data (password excluded)
sendJSONResponse([
    'success' => true,
    'user_id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'is_admin' => (int)$user['is_admin'],
    'message' => 'User verified successfully'
]);
```

**Response**:
```json
{
    "success": true,
    "user_id": 10,
    "username": "john_doe",
    "email": "john@university.edu",
    "is_admin": 0,
    "message": "User verified successfully"
}
```

**Why This Implementation**:
1. **password_verify**: Secure comparison against hash
2. **401 Unauthorized**: Proper HTTP status for auth failure
3. **No Password in Response**: Security best practice
4. **User Data Returned**: Client can store in session
5. **Cast is_admin to Int**: Ensures consistent type

---

#### 3. Get All Users (Admin Only)
**Endpoint**: `GET /api/get_all_users.php`

**Implementation**:
```php
$sql = "SELECT id, username, email, is_admin, created_at FROM users 
        ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate statistics
$stats = [
    'total_users' => count($users),
    'admin_users' => count(array_filter($users, fn($u) => $u['is_admin'] == 1)),
    'regular_users' => count(array_filter($users, fn($u) => $u['is_admin'] == 0))
];

sendJSONResponse([
    'success' => true,
    'users' => $users,
    'stats' => $stats
]);
```

**Why This Implementation**:
1. **Exclude Passwords**: SELECT only safe fields
2. **Ordered by Date**: Newest users first
3. **Statistics Included**: Reduces API calls
4. **Admin Check in Frontend**: ServerC enforces admin-only access

---

#### 4. Toggle Admin Status
**Endpoint**: `POST /api/toggle_admin.php`

**Request Parameters**:
```php
$user_id = $_POST['user_id'];
$is_admin = $_POST['is_admin'];  // 0 or 1
```

**Implementation**:
```php
if (empty($user_id) || !isset($is_admin)) {
    sendJSONResponse(['error' => 'User ID and admin status are required'], 400);
}

// Validate is_admin is 0 or 1
if (!in_array($is_admin, [0, 1, '0', '1'])) {
    sendJSONResponse(['error' => 'Invalid admin status'], 400);
}

$sql = "UPDATE users SET is_admin = '" . 
       mysqli_real_escape_string($conn, $is_admin) . "' 
       WHERE id = '" . mysqli_real_escape_string($conn, $user_id) . "'";

if (mysqli_query($conn, $sql)) {
    sendJSONResponse([
        'success' => true,
        'message' => 'User status updated successfully'
    ]);
}
```

**Why This Implementation**:
1. **Simple Toggle**: Can promote or demote users
2. **Validation**: Ensures only 0 or 1 values
3. **No Self-Demotion Check**: Could be added for safety
4. **Immediate Effect**: User's next request reflects new status

---

## Authentication System

### Session-Based Authentication

**Session Initialization** (all servers):
```php
session_start();
```

**Login Flow** (`user_login.php`):
```php
// 1. User submits login form
$username = $_POST['username'];
$password = $_POST['password'];

// 2. Call ServerB API to verify credentials
$response = makeAPIRequest(SERVERB_URL . '/verify_user.php', [
    'username' => $username,
    'password' => $password
], 'POST', ['return_json' => true]);

// 3. If successful, store in session
if (is_array($response) && isset($response['success']) && $response['success']) {
    $_SESSION['user_id'] = $response['user_id'];
    $_SESSION['username'] = $response['username'];
    $_SESSION['user_email'] = $response['email'];
    $_SESSION['is_admin'] = $response['is_admin'] ?? 0;
    
    header('Location: user_dashboard.php');
    exit();
}
```

**Why Session-Based**:
1. **Server-Side Storage**: More secure than client-side tokens
2. **PHP Native**: No additional libraries needed
3. **Automatic Expiry**: Session expires when browser closes
4. **Cross-Request Persistence**: Works across all pages

---

### Authorization Helpers

**Check if User is Logged In**:
```php
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}
```

**Require User to be Logged In**:
```php
function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: user_login.php');
        exit();
    }
}
```

**Check if Current User is Admin**:
```php
function isCurrentUserAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}
```

**Get Current User Data**:
```php
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}
```

**Why These Helpers**:
1. **Consistent Checks**: Same logic across all pages
2. **Easy to Update**: Change one function to modify behavior
3. **Clear Intent**: Function names are self-documenting
4. **Null Coalescing**: Safe even if session not set

---

### Logout Implementation

```php
function logoutUser() {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Called in user_dashboard.php
if (isset($_GET['logout'])) {
    logoutUser();
}
```

**Why This Approach**:
1. **Complete Destruction**: session_destroy() removes all data
2. **Redirect to Home**: Clean UX after logout
3. **GET Parameter**: Simple, no form needed
4. **Confirmation**: Could add JavaScript confirm dialog

---

## Configuration Management

### Centralized Configuration System

All servers use a two-tier configuration:

1. **deployment_config.php** (Auto-generated by deploy.php)
2. **config.php** (Loads deployment config + server-specific logic)

---

### Deployment Configuration (`deployment_config.php`)

**Auto-generated by** `deploy.php` (deployment script):

```php
<?php
/**
 * Auto-generated deployment configuration
 * DO NOT EDIT MANUALLY - Changes will be overwritten
 * 
 * Generated: 2024-01-15 10:30:00
 * Environment: localhost
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lostnfound');
define('DB_USER', 'root');
define('DB_PASS', '');

// Server URLs
define('SERVERA_API_URL', 'http://localhost/Lostnfound/ServerA/api');
define('SERVERB_API_URL', 'http://localhost/Lostnfound/ServerB/api');
define('SERVERC_URL', 'http://localhost/Lostnfound/ServerC');

// Upload Configuration
define('UPLOADS_BASE_URL', 'http://localhost/Lostnfound/ServerB/uploads/');
?>
```

**Why Auto-Generated**:
1. **Environment-Specific**: Different configs for dev/staging/prod
2. **No Manual Editing**: Reduces human error
3. **Single Source**: deploy.php is authoritative
4. **Easy Deployment**: One script configures everything

---

### Server-Specific Configuration

**ServerC config.php**:
```php
require_once __DIR__ . '/deployment_config.php';

// API URLs
define('SERVERA_URL', SERVERA_API_URL);  
define('SERVERB_URL', SERVERB_API_URL);

// Upload paths
define('UPLOADS_PATH', __DIR__ . '/../ServerB/uploads/');
define('UPLOADS_URL', '../ServerB/uploads/');
define('UPLOADS_HTTP_URL', UPLOADS_BASE_URL);

// Prevent direct database access
function connectDB() {
    die("ERROR: ServerC cannot connect directly to the database.");
}
```

**ServerA/B config.php**:
```php
require_once __DIR__ . '/deployment_config.php';

$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    return $conn;
}
```

**Why This Structure**:
1. **Layered Configuration**: Deployment + server-specific
2. **Shared Variables**: All servers use same DB credentials
3. **Server Restrictions**: ServerC physically cannot access DB
4. **Override Capability**: Can override deployment config if needed

---

## Security Implementation

### 1. SQL Injection Prevention

**All user inputs are escaped**:
```php
$username = mysqli_real_escape_string($conn, $_POST['username']);
$email = mysqli_real_escape_string($conn, $_POST['email']);

$sql = "SELECT * FROM users WHERE username = '$username' AND email = '$email'";
```

**Why This Works**:
- **Escapes Special Characters**: Quotes, backslashes, etc.
- **Context-Aware**: Specific to MySQL
- **Database Native**: Built into mysqli extension

**Better Alternative (Prepared Statements)**:
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();
```

**Note**: Current implementation uses escaping; prepared statements would be an upgrade.

---

### 2. Password Security

**Hashing During Registration**:
```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```

**Verification During Login**:
```php
if (!password_verify($password, $user['password'])) {
    sendJSONResponse(['error' => 'Invalid password'], 401);
}
```

**Why This is Secure**:
1. **PASSWORD_DEFAULT**: Uses bcrypt (currently strongest)
2. **Automatic Salting**: Each hash includes unique salt
3. **Future-Proof**: Will upgrade to stronger algorithms automatically
4. **No Plaintext Storage**: Impossible to reverse the hash

---

### 3. CORS Headers

**All API endpoints set CORS headers**:
```php
function setCORSHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: false');
    header('Access-Control-Max-Age: 86400');
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
```

**Why These Headers**:
1. **Allow-Origin: ***: Permits cross-origin requests (adjust for production)
2. **Allow-Methods**: Specifies supported HTTP methods
3. **Allow-Headers**: Defines acceptable request headers
4. **Preflight Handling**: OPTIONS requests get 200 OK
5. **Max-Age**: Caches preflight for 24 hours

**Production Consideration**: Change `*` to specific domain for security.

---

### 4. File Upload Security

**File Type Validation**:
```php
$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$allowed = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array(strtolower($extension), $allowed)) {
    sendJSONResponse(['error' => 'Invalid file type'], 400);
}
```

**File Size Validation**:
```javascript
// Client-side (JavaScript)
if (image.size > 5 * 1024 * 1024) {
    alert('Image file size must be less than 5MB.');
    return false;
}
```

**Unique Filenames**:
```php
$image_filename = uniqid() . '.' . $extension;
```

**Why These Measures**:
1. **Extension Whitelist**: Only allows image formats
2. **Size Limit**: Prevents server storage abuse
3. **Unique Names**: Prevents overwriting, predictable URLs
4. **Client + Server Validation**: Defense in depth

---

### 5. Session Security

**Session Configuration** (should be in php.ini or init script):
```php
ini_set('session.cookie_httponly', 1);  // Prevents JavaScript access
ini_set('session.cookie_secure', 1);    // HTTPS only (if using SSL)
ini_set('session.use_strict_mode', 1);  // Prevents session fixation
```

**Why These Settings**:
1. **HttpOnly**: Mitigates XSS attacks stealing session
2. **Secure**: Prevents session hijacking over HTTP
3. **Strict Mode**: Rejects uninitialized session IDs

---

## Error Handling

### Consistent JSON Response Format

**Success Response**:
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation completed successfully"
}
```

**Error Response**:
```json
{
    "success": false,
    "error": "Description of what went wrong",
    "http_code": 400
}
```

**Helper Function**:
```php
function sendJSONResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data);
    exit();
}
```

**Why This Approach**:
1. **Consistent Format**: Frontend always expects same structure
2. **HTTP Status Codes**: Proper semantic responses
3. **Content-Type Header**: Ensures proper parsing
4. **Early Exit**: Prevents accidental output after response

---

### HTTP Status Codes Used

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST (item/user created) |
| 400 | Bad Request | Validation errors, missing fields |
| 401 | Unauthorized | Invalid credentials |
| 403 | Forbidden | User lacks permission |
| 404 | Not Found | Resource doesn't exist |
| 405 | Method Not Allowed | Wrong HTTP method |
| 500 | Internal Server Error | Database errors, exceptions |

**Why Proper Status Codes**:
1. **RESTful Standards**: Industry best practices
2. **Client Handling**: Frontend can branch on status
3. **Debugging**: Status immediately indicates error type
4. **API Consumers**: Third-party integrations understand errors

---

### Error Logging

**PHP Error Logging**:
```php
error_log("[APIRequest] Success: $method $url | HTTP $http_code");
error_log("[APIRequest] Attempt $attempt failed: $last_error");
```

**Why Logging**:
1. **Debugging**: Track down issues in production
2. **Monitoring**: Detect patterns of errors
3. **Audit Trail**: Who did what when
4. **Performance**: Identify slow requests

**Log Location**: `php_error.log` (configured in php.ini)

---

## Inter-Server Communication

### API Request Function (`makeAPIRequest`)

**Complete Implementation**:
```php
function makeAPIRequest($url, $data = [], $method = 'POST', $options = []) {
    $retry_count = $options['retry_count'] ?? 3;
    $retry_delay = $options['retry_delay'] ?? 1;
    $timeout = $options['timeout'] ?? 30;
    $connect_timeout = $options['connect_timeout'] ?? 10;
    $return_json = $options['return_json'] ?? false;
    
    $attempt = 0;
    $last_error = null;
    
    while ($attempt < $retry_count) {
        $attempt++;
        
        try {
            $ch = curl_init();
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } elseif ($method === 'GET') {
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
            }
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($curl_error) {
                throw new Exception("cURL error: $curl_error");
            }
            
            if ($http_code >= 200 && $http_code < 300) {
                if ($return_json) {
                    return json_decode($response, true);
                }
                return $response;
            }
            
            throw new Exception("HTTP $http_code");
            
        } catch (Exception $e) {
            $last_error = $e->getMessage();
            error_log("[APIRequest] Attempt $attempt/$retry_count failed: $last_error");
            
            if ($attempt < $retry_count) {
                sleep($retry_delay * $attempt);  // Exponential backoff
            }
        }
    }
    
    return $return_json ? ['success' => false, 'error' => $last_error] : "error|$last_error";
}
```

**Why This Implementation**:

1. **Retry Logic**: Automatically retries on failure (3 attempts)
2. **Exponential Backoff**: Waits longer between retries (1s, 2s, 3s)
3. **Timeout Configuration**: Prevents indefinite hanging
4. **JSON Parsing**: Optional automatic JSON decoding
5. **Error Handling**: Comprehensive exception catching
6. **HTTPS Support**: SSL verification can be disabled
7. **Logging**: Records every attempt for debugging
8. **Graceful Degradation**: Returns error structure on failure

---

### API Client Class (`APIClient.php`)

**Object-Oriented Alternative**:
```php
class APIClient {
    private $base_url;
    private $timeout = 30;
    private $max_retries = 3;
    
    public function __construct($base_url, $options = []) {
        $this->base_url = rtrim($base_url, '/');
        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
    }
    
    public function post($endpoint, $data = [], $return_json = false) {
        return $this->request('POST', $endpoint, $data, $return_json);
    }
    
    public function get($endpoint, $params = [], $return_json = false) {
        return $this->request('GET', $endpoint, $params, $return_json);
    }
    
    private function request($method, $endpoint, $data = [], $return_json = false) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        // ... implementation similar to makeAPIRequest
    }
}

// Usage
$serverA = new APIClient(SERVERA_URL);
$items = $serverA->get('/get_all_items.php', [], true);
```

**Why OOP Approach**:
1. **State Management**: Base URL stored in object
2. **Method Chaining**: Clean, fluent interface
3. **Testable**: Easy to mock in unit tests
4. **Reusable**: Create once, use many times
5. **Extendable**: Can add authentication, logging, etc.

---

## Why These Backend Design Decisions

### 1. Why Three Separate Servers?

**Reasons**:
1. **Separation of Concerns**: Each server has ONE job
2. **Independent Scaling**: Scale UI separately from backend
3. **Security**: Frontend can't bypass API layer
4. **Team Organization**: Different teams can own different servers
5. **Technology Flexibility**: Can rewrite frontend without touching backend

---

### 2. Why REST API Architecture?

**Reasons**:
1. **Stateless**: Each request is independent
2. **Cacheable**: HTTP caching works out of the box
3. **Universal**: Any client (web, mobile, desktop) can consume
4. **Debuggable**: Can test with curl, Postman
5. **Standardized**: Industry-standard patterns

---

### 3. Why PHP and MySQL?

**Reasons**:
1. **WAMP/XAMPP Compatible**: Easy local development
2. **Mature Ecosystem**: Decades of libraries, tools
3. **Shared Hosting Friendly**: Runs almost anywhere
4. **No Build Step**: Edit and refresh
5. **Strong DB Integration**: mysqli/PDO well-supported

---

### 4. Why Session-Based Auth (Not JWT)?

**Reasons**:
1. **Simpler**: No token management complexity
2. **Server-Controlled**: Can invalidate sessions immediately
3. **PHP Native**: No external libraries needed
4. **Secure by Default**: HttpOnly cookies
5. **Stateful OK**: Our servers are not microservices

---

### 5. Why No ORM?

**Reasons**:
1. **Transparency**: SQL queries are explicit
2. **Performance**: No abstraction overhead
3. **Learning**: Students understand SQL better
4. **Debugging**: Can see exact queries run
5. **Simplicity**: No configuration needed

---

## Future Backend Improvements

### 1. Prepared Statements
Replace `mysqli_real_escape_string` with prepared statements:
```php
$stmt = $conn->prepare("INSERT INTO items (user_id, title) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $title);
$stmt->execute();
```

### 2. API Rate Limiting
Prevent abuse with request throttling:
```php
function checkRateLimit($user_id, $limit = 100, $period = 3600) {
    // Check if user has exceeded $limit requests in $period seconds
}
```

### 3. API Versioning
Support multiple API versions:
```
/api/v1/get_all_items.php
/api/v2/get_all_items.php
```

### 4. Database Migrations
Version-controlled schema changes:
```php
// migrations/001_create_users_table.php
// migrations/002_add_phone_to_users.php
```

### 5. Logging Framework
Structured logging instead of error_log:
```php
$logger->info("User $user_id created item $item_id");
$logger->error("Failed to connect to database", ['host' => $db_host]);
```

### 6. Input Validation Library
Centralized validation rules:
```php
$validator = new Validator($_POST);
$validator->required('title')->minLength(3)->maxLength(200);
$validator->email('contact');
```

### 7. Background Jobs
Handle heavy tasks asynchronously:
```php
// Queue email notifications
Queue::push(new SendEmailJob($user_id, $item_id));
```

### 8. Caching Layer
Reduce database queries:
```php
$items = Cache::remember('all_items', 60, function() {
    return getAllItemsFromDB();
});
```

---

**End of Backend Documentation**
