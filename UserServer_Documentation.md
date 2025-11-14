# UserServer Documentation - User Logic & Database Server

## 🎯 Server Role & Responsibility

**UserServer** is the **User Logic & Database Server** in the Lost & Found distributed system. It serves as the **central hub** for user authentication, database hosting, and acts as a proxy for item-related operations.

**Directory**: `UserServer/`  
**IP Address**: `172.24.194.6`  
**Base URL**: `http://172.24.194.6/Lostnfound/UserServer`  
**API Base URL**: `http://172.24.194.6/Lostnfound/UserServer/api`

### Core Responsibilities
✅ Host the centralized MySQL database (`lostfound`)  
✅ Handle all user authentication (login/registration)  
✅ Manage user accounts and admin privileges  
✅ Proxy item requests to ItemsServer via API calls  
✅ Provide user management APIs for admin operations  
✅ Database administration and maintenance  

### What UserServer Does NOT Handle
❌ Direct item CRUD operations (delegated to ItemsServer)  
❌ Frontend rendering (handled by Frontend)  
❌ Client-side JavaScript execution  
❌ Session management for UI (Frontend handles this)  
❌ File uploads for item images (ItemsServer handles this)  

---

## 🌐 Network Configuration

### Server Details
- **IP Address**: `172.24.194.6`
- **Base URL**: `http://172.24.194.6/Lostnfound/UserServer`
- **API Base URL**: `http://172.24.194.6/Lostnfound/UserServer/api`
- **Role**: User Logic & Database Server
- **Operating System**: Typically Linux/Windows with XAMPP

### Database Hosting
- **Host**: `localhost` or `172.24.194.6` (local database)
- **Database**: `lostfound`
- **User**: `root`
- **Access**: Direct local access + remote access for ItemsServer
- **Tables**: `users`, `items`

### Database Hosting
- **Host**: `localhost` or `172.24.194.6` (local database)
- **Database**: `lostfound`
- **User**: `root`
- **Access**: Direct local access + remote access for ItemsServer
- **Tables**: `users`, `items`
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/register_user.php` | POST | Create new user account |
| `/api/verify_user.php` | POST | Authenticate user (login) |
| `/api/get_all_users.php` | GET | Get all users (admin only) |
| `/api/get_user_items.php` | GET | Proxy to ItemsServer for user items |
| `/api/toggle_admin.php` | POST | Toggle user admin status |
| `/api/health.php` | GET | Server health check |

---

## 📁 File Structure

```
UserServer/
├── api/
│   ├── register_user.php     # User registration endpoint (POST)
│   ├── verify_user.php       # User authentication endpoint (POST)
│   ├── get_all_users.php     # Retrieve all users (GET, admin only)
│   ├── get_user_items.php    # Proxy to ItemsServer for user's items (GET)
│   ├── toggle_admin.php      # Toggle admin status (POST, admin only)
│   └── health.php            # Health check endpoint (GET)
├── config.php                 # Server configuration & functions
└── deployment_config.php      # Auto-generated deployment settings
```

---

## 🔧 Core Configuration (`config.php`)

### Purpose
The `config.php` file provides:
- Database connection functions
- Session management
- **Advanced API communication functions** (`makeAPIRequest()`)
- CORS header handling
- User authentication helpers
- JSON response utilities

### Key Functions

#### 1. Database Connection
```php
function connectDB()
```
- **Purpose**: Connect to the **local** database hosted on this server
- **Returns**: mysqli connection object
- **Host**: `localhost` or `172.24.194.6`
- **Database**: `lostfound`
- **Error Handling**: Dies with error message if connection fails

**Implementation:**
```php
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
return $conn;
```

#### 2. Enhanced API Request Function

**`makeAPIRequest($url, $data, $method, $options)`**

This is the **most critical function** in UserServer, enabling robust inter-server communication.

**Parameters:**
- `$url` (string): Target API endpoint URL
- `$data` (array): Request data (POST body or GET query params)
- `$method` (string): HTTP method ('POST', 'GET', 'DELETE', 'PUT')
- `$options` (array): Configuration options

**Options Array:**
| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `retry_count` | int | 3 | Number of retry attempts on failure |
| `retry_delay` | int | 1 | Seconds to wait between retries |
| `timeout` | int | 30 | Request timeout in seconds |
| `connect_timeout` | int | 10 | Connection timeout in seconds |
| `return_json` | bool | false | Auto-parse JSON response |
| `verify_ssl` | bool | false | Verify SSL certificates |
| `send_json` | bool | false | Send data as JSON instead of form-data |
| `force_json` | bool | false | Force JSON parsing even without content-type |

**Features:**
✅ **Automatic retry logic** with exponential backoff  
✅ **Timeout management** for connections and requests  
✅ **JSON auto-parsing** with error handling  
✅ **Comprehensive error logging**  
✅ **HTTP status code validation**  
✅ **SSL support** (configurable)  
✅ **User-Agent identification** (`LostFound-UserServer/2.0`)  
✅ **Smart error handling** (don't retry 4xx errors)  

**Usage Example:**
```php
// Call ItemsServer to get user's items
$response = makeAPIRequest(
    ITEMSSERVER_API_URL . '/get_user_items.php',
    ['user_id' => $user_id],
    'GET',
    ['return_json' => true, 'force_json' => true]
);

if (is_array($response) && isset($response['success'])) {
    // Success - process items
    $items = $response['items'];
} else {
    // Error - handle failure
    error_log('API Error: ' . json_encode($response));
}
```

**Return Values:**
- **On success**: Raw response string or parsed JSON array (if `return_json` enabled)
- **On failure**: 
  - `"error|[error message]"` (if `return_json` is false)
  - `['success' => false, 'error' => '...']` (if `return_json` is true)

**Error Handling Flow:**
```
Attempt 1 → Fail → Wait 1 second → Attempt 2 → Fail → Wait 2 seconds → Attempt 3
    ↓
If all fail: Return error with detailed message
If 4xx error: Stop retrying (client error won't fix itself)
If 5xx error: Retry (server might recover)
```

#### 3. Session & User Functions
```php
function isUserLoggedIn()         // Check if user has active session
function getCurrentUserId()       // Get logged-in user's ID
function getCurrentUsername()     // Get logged-in user's username
function getCurrentUserEmail()    // Get logged-in user's email
function isCurrentUserAdmin()     // Check if user is admin
function requireUser()            // Redirect if not logged in
function logoutUser()             // Destroy session and redirect
```

**Note**: These are primarily used by Frontend (UI). UserServer APIs work independently.

#### 4. API Helper Functions

**`sendJSONResponse($data, $status_code = 200)`**
- Sends JSON response with proper headers
- Sets HTTP status code
- Adds CORS headers
- Exits script after sending

**`setCORSHeaders()`**
- Sets Cross-Origin Resource Sharing headers
- Handles OPTIONS preflight requests
- Enables Frontend to call UserServer APIs

---

## 🔌 API Endpoints Documentation

### 1. Register User - `POST /api/register_user.php`

**Purpose**: Create a new user account in the database.

**Request Method**: POST

**Required Parameters:**
| Parameter | Type | Description | Validation |
|-----------|------|-------------|------------|
| `username` | string | Unique username | Required, must be unique |
| `email` | string | User's email address | Required, must be valid email format |
| `password` | string | User's password | Required, min 6 characters recommended |

**Request Example:**
```http
POST /api/register_user.php
Content-Type: application/x-www-form-urlencoded

username=john_doe
&email=john@university.edu
&password=SecurePass123
```

**Success Response (200):**
```json
{
  "success": true,
  "user_id": 5,
  "username": "john_doe",
  "email": "john@university.edu",
  "message": "User registered successfully"
}
```

**Error Responses:**
```json
// Missing fields (400)
{
  "error": "Please fill all required fields"
}

// Invalid email format (400)
{
  "error": "Invalid email format"
}

// Username/email already exists (400)
{
  "error": "Username or email already exists"
}

// Database error (500)
{
  "error": "Failed to register user: [MySQL error]"
}
```

**Security Implementation:**
1. **Email validation**: Uses `filter_var()` with `FILTER_VALIDATE_EMAIL`
2. **Password hashing**: Uses `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
3. **SQL injection prevention**: All inputs escaped with `mysqli_real_escape_string()`
4. **Duplicate check**: Verifies username and email don't already exist
5. **Default privileges**: New users get `is_admin = 0` by default

**Database Queries:**
```sql
-- Check if user exists
SELECT id FROM users 
WHERE username = '$username' OR email = '$email'

-- Insert new user
INSERT INTO users (username, email, password, is_admin, created_at) 
VALUES ('$username', '$email', '$hashed_password', 0, NOW())
```

**Password Security:**
```php
// Hash password using bcrypt (default algorithm)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Example hashed output:
// $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

---

### 2. Verify User - `POST /api/verify_user.php`

**Purpose**: Authenticate a user (login) and return user details.

**Request Method**: POST

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `username` | string | User's username |
| `password` | string | User's password (plain text) |

**Request Example:**
```http
POST /api/verify_user.php
Content-Type: application/x-www-form-urlencoded

username=john_doe
&password=SecurePass123
```

**Success Response (200):**
```json
{
  "success": true,
  "user_id": 5,
  "username": "john_doe",
  "email": "john@university.edu",
  "is_admin": 0,
  "message": "User verified successfully"
}
```

**Error Responses:**
```json
// Missing credentials (400)
{
  "error": "Username and password are required"
}

// User not found (401)
{
  "error": "User not found"
}

// Wrong password (401)
{
  "error": "Invalid password"
}

// Database error (500)
{
  "error": "Database query failed"
}
```

**Authentication Flow:**
```
1. Receive username and password
2. Query database for user by username
3. If user not found → Return error (401)
4. If user found → Verify password using password_verify()
5. If password incorrect → Return error (401)
6. If password correct → Return user data (200)
```

**Database Query:**
```sql
SELECT id, username, email, password, is_admin 
FROM users 
WHERE username = '$username'
```

**Password Verification:**
```php
// Verify plain text password against hashed password
if (password_verify($password, $user['password'])) {
    // Password is correct
} else {
    // Password is incorrect
}
```

**Security Notes:**
- Password sent as plain text (HTTPS recommended in production)
- Password never returned in response
- Failed login attempts not logged (could implement rate limiting)
- Uses constant-time comparison via `password_verify()`

---

### 3. Get All Users - `GET /api/get_all_users.php`

**Purpose**: Retrieve all registered users (admin dashboard feature).

**Request Method**: GET

**Authentication**: None enforced at API level (client should verify admin status)

**Request Example:**
```http
GET /api/get_all_users.php
```

**Success Response (200):**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@university.edu",
      "is_admin": 1,
      "created_at": "2024-01-01 10:00:00"
    },
    {
      "id": 2,
      "username": "john_doe",
      "email": "john@university.edu",
      "is_admin": 0,
      "created_at": "2024-11-10 14:30:00"
    }
  ],
  "stats": {
    "total_users": 25,
    "admin_users": 3,
    "regular_users": 22
  }
}
```

**Error Response:**
```json
// Database error (500)
{
  "error": "Database query failed"
}
```

**Features:**
- **Sorted by date**: Newest users first
- **Statistics**: Counts total, admin, and regular users
- **Password excluded**: Password hash not returned for security
- **Array processing**: Uses `array_filter()` to count admins

**Database Query:**
```sql
SELECT id, username, email, is_admin, created_at 
FROM users 
ORDER BY created_at DESC
```

**Statistics Calculation:**
```php
$total_users = count($users);
$admin_users = count(array_filter($users, function($user) { 
    return $user['is_admin'] == 1; 
}));
$regular_users = $total_users - $admin_users;
```

---

### 4. Get User Items (Proxy) - `GET /api/get_user_items.php`

**Purpose**: Proxy requests to ItemsServer to get items posted by a specific user.

**Request Method**: GET

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | ID of the user |

**Request Example:**
```http
GET /api/get_user_items.php?user_id=5
```

**Success Response (200):**
```json
{
  "success": true,
  "items": [
    {
      "id": 123,
      "user_id": 5,
      "username": "john_doe",
      "title": "Black iPhone 13",
      "description": "...",
      "type": "lost",
      "location": "Main Library",
      "contact": "john@university.edu",
      "image": "abc123.jpg",
      "created_at": "2024-11-11 10:30:00"
    }
  ],
  "stats": {
    "total": 3,
    "lost_count": 2,
    "found_count": 1
  }
}
```

**Error Responses:**
```json
// Missing user_id (400)
{
  "error": "user_id parameter is required"
}

// Invalid user_id (400)
{
  "error": "Invalid user_id"
}

// ItemsServer API error (500)
{
  "error": "Unexpected response from ItemsServer"
}
```

**Proxy Implementation:**
This endpoint demonstrates UserServer's **proxy pattern** for item operations:

```php
// Build ItemsServer URL
$itemsserver_url = ITEMSSERVER_API_URL . '/get_user_items.php';

// Make API call to ItemsServer
$response = makeAPIRequest(
    $itemsserver_url,
    ['user_id' => $user_id],
    'GET',
    ['return_json' => true, 'force_json' => true]
);

// Forward ItemsServer's response to client
if (is_array($response) && isset($response['success'])) {
    sendJSONResponse($response);
} else {
    sendJSONResponse(['error' => 'Unexpected response from ItemsServer'], 500);
}
```

**Why Use a Proxy?**
- **Centralized access control**: UserServer can add authentication checks
- **Request logging**: All item requests go through UserServer
- **Consistent API**: Clients only need to know UserServer's URL
- **Future extensibility**: Easy to add caching, rate limiting, etc.

---

### 5. Toggle Admin Status - `POST /api/toggle_admin.php`

**Purpose**: Promote a user to admin or demote an admin to regular user.

**Request Method**: POST

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | ID of the user to modify |
| `is_admin` | integer | New admin status (0 or 1) |

**Request Example:**
```http
POST /api/toggle_admin.php
Content-Type: application/x-www-form-urlencoded

user_id=5
&is_admin=1
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "User status updated successfully",
  "user_id": 5,
  "is_admin": 1,
  "status_text": "promoted to admin"
}
```

**Error Responses:**
```json
// Missing or invalid parameters (400)
{
  "error": "Valid user_id parameter is required"
}
{
  "error": "is_admin parameter must be 0 or 1"
}

// User not found (404)
{
  "error": "User not found"
}

// Database error (500)
{
  "error": "Failed to update user status"
}
```

**Database Queries:**
```sql
-- Check if user exists
SELECT id FROM users WHERE id = '$user_id'

-- Update admin status
UPDATE users SET is_admin = '$is_admin' WHERE id = '$user_id'
```

**Status Text Logic:**
```php
$status_text = $is_admin == 1 ? 'promoted to admin' : 'removed from admin';
```

**Security Considerations:**
- No authentication check at API level (client should verify)
- Could add check to prevent last admin from being demoted
- Should log admin privilege changes for audit trail

---

### 6. Health Check - `GET /api/health.php`

**Purpose**: Monitor server status, database connection, and file storage.

**Request Method**: GET

**Request Example:**
```http
GET /api/health.php
```

**Success Response (200):**
```json
{
  "server": "UserServer",
  "status": "online",
  "database": "connected",
  "timestamp": "2024-11-11 14:30:45",
  "services": {
    "users_database": "150 users stored",
    "register_user_api": "active",
    "verify_user_api": "active",
    "get_all_users_api": "active"
  }
}
```

**Health Checks Performed:**
1. **Database Connection**: Attempts to connect and query users table
2. **API Endpoints**: Checks for presence of user management API files

**Implementation:**
```php
// Direct database connection (bypasses session_start)
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check users table
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $health['services']['users_database'] = $row['count'] . ' users stored';
}

// Check API endpoints
$health['services']['register_user_api'] = file_exists(__DIR__ . '/register_user.php') ? 'active' : 'missing';
$health['services']['verify_user_api'] = file_exists(__DIR__ . '/verify_user.php') ? 'active' : 'missing';
```

---

## 🗄️ Database Schema (Users Table)

UserServer manages the `users` table:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Column Details
- **`id`**: Auto-incrementing primary key
- **`username`**: Unique username (max 50 chars)
- **`email`**: Unique email address (max 100 chars)
- **`password`**: Bcrypt hashed password (60 chars, stored as VARCHAR(255) for future algorithms)
- **`is_admin`**: Admin flag (0=regular user, 1=admin)
- **`created_at`**: Automatic timestamp on account creation

### Indexes
- **PRIMARY KEY**: `id`
- **UNIQUE INDEX**: `username`
- **UNIQUE INDEX**: `email`

---

## 🔒 Security Implementation

### Password Security
**Hashing Algorithm:**
```php
$hashed = password_hash($password, PASSWORD_DEFAULT);
// Uses bcrypt with cost factor 10
// Example: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

**Verification:**
```php
if (password_verify($plaintext_password, $hashed_password)) {
    // Correct password
}
```

**Why Bcrypt?**
- **Slow by design**: Resistant to brute-force attacks
- **Salted automatically**: No need to manage salts separately
- **Future-proof**: Can upgrade to newer algorithms without changing code

### SQL Injection Prevention
```php
// All inputs escaped before database insertion
$username = mysqli_real_escape_string($conn, $username);
$email = mysqli_real_escape_string($conn, $email);
```

### Input Validation
- **Email format**: `filter_var($email, FILTER_VALIDATE_EMAIL)`
- **Numeric validation**: `is_numeric($user_id)`
- **Required fields**: Empty checks before processing
- **Type validation**: Ensure `is_admin` is 0 or 1

### CORS Security
```
CORS stands for Cross-Origin Resource Sharing.

It’s a security feature built into web browsers that controls how web pages can request resources from a different domain (origin).

Common use cases include:
- Web applications hosted on different domains
- APIs accessed by multiple clients
- Microservices architecture

By default, web browsers block requests from different origins for security reasons. CORS headers allow controlled access from trusted domains while preventing unauthorized requests.


<details>
<summary>🎯 Why CORS Is Needed</summary>

🔐 Security	|Prevents cross-site scripting (XSS) and clickjacking attacks by restricting resource sharing

🌍 Controlled sharing	|Allows trusted websites or mobile apps to communicate with your API

⚙️ API access management | Lets developers define which domains can use their backend resources

🧩 Frontend-backend separation |	Enables frontend apps (e.g., React, Angular, or mobile) to safely talk to backend servers (e.g., Django, Node.js, PHP)
```

```php
header('Access-Control-Allow-Origin: *');  // Should be restricted in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
```

**Production Recommendation:**
```php
// Restrict to known origins
header('Access-Control-Allow-Origin: http://172.24.14.184');
```

---

## 🔗 Integration with Other Servers

### Frontend → UserServer (User Operations)

**Registration Flow:**
```
User fills registration form on Frontend
    ↓
Frontend calls UserServer: POST /register_user.php
    ↓
UserServer validates email format
    ↓
UserServer checks for existing username/email
    ↓
UserServer hashes password
    ↓
UserServer inserts into database
    ↓
UserServer returns user data + success
    ↓
Frontend creates session and logs user in
```

**Login Flow:**
```
User enters credentials on Frontend
    ↓
Frontend calls UserServer: POST /verify_user.php
    ↓
UserServer queries user by username
    ↓
UserServer verifies password using password_verify()
    ↓
UserServer returns user data (including is_admin flag)
    ↓
Frontend stores user data in session
    ↓
Frontend redirects to dashboard
```

### UserServer → ItemsServer (Item Proxy)

**Get User Items Flow:**
```
Admin views user dashboard on Frontend
    ↓
Frontend calls UserServer: GET /get_user_items.php?user_id=5
    ↓
UserServer proxies to ItemsServer: GET /get_user_items.php?user_id=5
    ↓
ItemsServer queries items table
    ↓
ItemsServer returns items + statistics
    ↓
UserServer forwards response to Frontend
    ↓
Frontend displays items to user
```

**Why Proxy Through UserServer?**
1. **Centralized authentication**: Can add auth checks before proxying
2. **Consistent client interface**: Frontend only talks to UserServer for user-related operations
3. **Request logging**: All user requests logged in one place
4. **Future caching**: Easy to add Redis caching layer

---

## 🚀 Deployment Configuration

UserServer uses auto-generated deployment configuration from `deployment_config.php`.

### Key Configuration Constants
```php
define('DEPLOYMENT_MODE', 'staging');
define('CURRENT_SERVER', 'UserServer');
define('ITEMSSERVER_IP', '172.24.194.6');
define('USERSERVER_IP', '172.24.194.6');
define('FRONTEND_IP', '172.24.14.184');
define('DB_HOST', '172.24.194.6');  // Local database
define('DB_NAME', 'lostfound');
define('DB_USER', 'root');
define('DB_PASS', 'Isaac@1234');
define('ITEMSSERVER_API_URL', 'http://172.24.194.6/Lostnfound/ItemsServer/api');
```

### Server Role
```php
define('SERVER_ROLES', [
    'ItemsServer' => 'Item Logic Server (with uploads)',
    'UserServer' => 'User Logic & Database Server', 
    'Frontend' => 'User Interface Client'
]);
```

---

## 📊 Performance Considerations

### Database Optimization
- **Indexed columns**: `username` and `email` have unique indexes
- **Connection reuse**: Single connection per request
- **Connection pooling**: Not implemented (could add with persistent connections)

### API Request Optimization
- **Retry logic**: Prevents immediate failure on temporary network issues
- **Timeout management**: Prevents indefinite waiting
- **Exponential backoff**: Reduces server load during high traffic
- **HTTP serving**: Apache/Nginx serves static files efficiently
- **No image processing**: Files stored as-is (could add resizing)

---

## 🧪 Testing UserServer APIs

### Using cURL

**Test User Registration:**
```bash
curl -X POST http://172.24.194.6/Lostnfound/UserServer/api/register_user.php \
  -d "username=testuser" \
  -d "email=test@university.edu" \
  -d "password=testpass123"
```

**Test User Login:**
```bash
curl -X POST http://172.24.194.6/Lostnfound/UserServer/api/verify_user.php \
  -d "username=testuser" \
  -d "password=testpass123"
```

**Test Get All Users:**
```bash
curl http://172.24.194.6/Lostnfound/UserServer/api/get_all_users.php
```

**Test Get User Items (Proxy):**
```bash
curl http://172.24.194.6/Lostnfound/UserServer/api/get_user_items.php?user_id=1
```

**Test Toggle Admin:**
```bash
curl -X POST http://172.24.194.6/Lostnfound/UserServer/api/toggle_admin.php \
  -d "user_id=5" \
  -d "is_admin=1"
```

**Test Health Check:**
```bash
curl http://172.24.194.6/Lostnfound/UserServer/api/health.php
```

---

## 🐛 Common Issues & Solutions

### Issue: "Database connection failed"
**Cause**: MySQL service not running  
**Solution**: 
```bash
# On Windows (XAMPP)
Start XAMPP Control Panel → Start MySQL

# On Linux
sudo systemctl start mysql
```

### Issue: "Username or email already exists"
**Cause**: Attempting to register with existing credentials  
**Solution**: Use different username/email or delete existing user

### Issue: "Unexpected response from ItemsServer"
**Cause**: ItemsServer is down or unreachable  
**Solution**: Check ItemsServer health endpoint, verify network connectivity

### Issue: "Failed to update user status"
**Cause**: User ID doesn't exist in database  
**Solution**: Verify user_id is correct, check database for user existence

---

## 📝 Best Practices

### When Using makeAPIRequest()
✅ Always set `return_json => true` for API calls  
✅ Use `force_json => true` if response might not have JSON content-type  
✅ Handle both success and error cases  
✅ Log errors for debugging  
✅ Set appropriate timeouts for different operations  

### When Handling User Data
✅ Always hash passwords with `password_hash()`  
✅ Never log or display passwords  
✅ Validate email format before insertion  
✅ Check for existing users before registration  
✅ Use prepared statements (future enhancement)  

### When Managing Files
✅ Validate file types before accepting uploads  
✅ Limit file sizes via php.ini or application logic  
✅ Use unique filenames to prevent collisions  
✅ Set proper directory permissions (755)  
✅ Clean up orphaned files periodically  

---

**UserServer serves as the central hub for user authentication, database management, and inter-server communication in the Lost & Found system.**
