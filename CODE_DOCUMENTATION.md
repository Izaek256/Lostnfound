# Lost and Found Portal - Complete Code Documentation

## Table of Contents
1. [Overview](#overview)
2. [Database Layer](#database-layer)
3. [Authentication System](#authentication-system)
4. [Core Pages](#core-pages)
5. [Item Management](#item-management)
6. [Admin System](#admin-system)
7. [Setup System](#setup-system)
8. [User Account Pages](#user-account-pages)
9. [Frontend Assets](#frontend-assets)
10. [Database Schema](#database-schema)

---

## Overview

This is a University Lost and Found Portal built with PHP and MySQL. The system allows users to report lost items, report found items, browse all items with search/filter capabilities, and includes an admin panel for management.

**Technology Stack:**
- **Backend:** PHP (procedural style with MySQLi)
- **Database:** MySQL 
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** Apache (XAMPP/WAMP)

**Key Features:**
- Guest and registered user support
- Image upload functionality
- Search and filter capabilities
- Admin management panel
- User deletion request system
- Responsive design

---

## Database Layer

### db.php
**File Reference:** [`db.php`](c:\wamp64\www\lostfound\db.php)

**Purpose:** Establishes MySQL database connection for the entire application.

**Configuration Variables:**
```php
$host = 'localhost';        // Database server address (localhost for local development)
$username = 'root';         // MySQL username (default XAMPP/WAMP username)
$password = 'kpet';         // MySQL password
$database = 'lostfound_db'; // Database name
```

**Connection Creation:**
```php
$conn = mysqli_connect($host, $username, $password, $database);
```
- Creates connection using MySQLi (MySQL Improved extension)
- Returns connection object stored in `$conn` variable
- This connection is used throughout the application

**Error Handling:**
```php
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
```
- Checks if connection failed (returns `false` on failure)
- `die()` stops script execution with error message
- `mysqli_connect_error()` provides detailed MySQL error

**Character Encoding:**
```php
mysqli_set_charset($conn, "utf8mb4");
```
- Sets UTF-8 (4-byte) character encoding
- Supports emojis and international characters
- Ensures proper data storage and retrieval

**Usage in Other Files:**
Every PHP file needing database access includes:
```php
require_once 'db.php';
```
- `require_once` includes file only once
- Prevents multiple connection attempts
- Makes `$conn` variable available

**Referenced By:** All PHP files that interact with database

---

## Authentication System

### user_config.php
**File Reference:** [`user_config.php`](c:\wamp64\www\lostfound\user_config.php)

**Purpose:** Manages user authentication, registration, login/logout, and session handling.

**Session Initialization:**
```php
session_start();
```
- Starts PHP session for user tracking
- Must be called before any output to browser
- Sessions persist data in `$_SESSION` superglobal array

#### Function: isUserLoggedIn()
**Returns:** Boolean - `true` if user logged in, `false` otherwise

```php
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}
```
- `isset()` checks if session variable exists
- Validates user ID is positive number
- Used throughout application to check authentication status

**Usage Example:**
```php
<?php if (isUserLoggedIn()): ?>
    <li><a href="user_dashboard.php">My Dashboard</a></li>
<?php else: ?>
    <li><a href="user_login.php">Login</a></li>
<?php endif; ?>
```

#### Function: getCurrentUserId()
**Returns:** Integer (user ID) or `null`

```php
function getCurrentUserId() {
    if (isUserLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return null;
}
```
- Returns logged-in user's ID
- Returns `null` for guest users
- Used to associate items with users in database

#### Function: getCurrentUsername()
**Returns:** String (username) or `null`

```php
function getCurrentUsername() {
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return null;
}
```
- Retrieves username from session
- Used for personalized greetings in UI

#### Function: getCurrentUserEmail()
**Returns:** String (email) or `null`

```php
function getCurrentUserEmail() {
    if (isset($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }
    return null;
}
```
- Gets user's email address from session
- Displayed in user dashboard

#### Function: registerUser($conn, $username, $email, $password)
**Purpose:** Creates new user account with validation and security

**Parameters:**
- `$conn` - MySQLi connection object
- `$username` - Desired username (string)
- `$email` - Email address (string)
- `$password` - Plain text password (string) - will be hashed

**Returns:** String - Error message or empty string on success

**Validation Steps:**

1. **Required Fields Check:**
```php
if (empty($username) || empty($email) || empty($password)) {
    return 'All fields are required';
}
```
- `empty()` checks for empty/null values
- Ensures all fields provided

2. **Email Format Validation:**
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'Invalid email format';
}
```
- `filter_var()` validates email format
- `FILTER_VALIDATE_EMAIL` checks for valid structure (user@domain.com)

3. **Password Length Check:**
```php
if (strlen($password) < 6) {
    return 'Password must be at least 6 characters';
}
```
- Enforces minimum 6 characters for security
- `strlen()` counts string length

4. **Duplicate Check:**
```php
$username = mysqli_real_escape_string($conn, $username);
$email = mysqli_real_escape_string($conn, $email);

$sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    return 'Username or email already exists';
}
```
- Prevents duplicate usernames/emails
- `mysqli_real_escape_string()` prevents SQL injection
- `mysqli_num_rows()` counts matching records

5. **Password Hashing:**
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
```
- `password_hash()` creates secure bcrypt hash
- `PASSWORD_DEFAULT` uses current best algorithm
- **Never stores plain text passwords** (security best practice)

6. **Database Insertion:**
```php
$sql = "INSERT INTO users (username, email, password) 
        VALUES ('$username', '$email', '$hashedPassword')";

if (mysqli_query($conn, $sql)) {
    return ''; // Empty string = success
} else {
    return 'Error creating account: ' . mysqli_error($conn);
}
```
- Inserts new user record
- Returns empty string on success
- Returns error message on failure

#### Function: loginUser($conn, $username, $password)
**Purpose:** Authenticates user credentials and creates session

**Parameters:**
- `$conn` - Database connection
- `$username` - Username entered
- `$password` - Password entered (plain text)

**Returns:** String - Error message or empty string on success

**Process:**

1. **Input Validation:**
```php
if (empty($username) || empty($password)) {
    return 'Please enter username and password';
}
```

2. **Database Lookup:**
```php
$username = mysqli_real_escape_string($conn, $username);

$sql = "SELECT id, username, email, password FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    return 'Invalid username or password';
}
```
- Retrieves user record
- Generic error message prevents username enumeration

3. **Password Verification:**
```php
$user = mysqli_fetch_assoc($result);

if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    return ''; // Success
} else {
    return 'Invalid username or password';
}
```
- `password_verify()` compares plain password with hash
- Secure comparison using timing-safe algorithm
- Sets session variables on success

#### Function: logoutUser()
**Purpose:** Ends user session and redirects to homepage

```php
function logoutUser() {
    session_destroy();
    header('Location: index.php');
    exit();
}
```
- `session_destroy()` removes all session data
- `header()` sends HTTP redirect
- `exit()` prevents further code execution

#### Function: requireUser()
**Purpose:** Protects pages requiring authentication

```php
function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: user_login.php');
        exit();
    }
}
```
- Redirects unauthenticated users to login
- Place at top of protected pages
- Used in: [`user_dashboard.php`](c:\wamp64\www\lostfound\user_dashboard.php), [`edit_item.php`](c:\wamp64\www\lostfound\edit_item.php)

**Referenced By:** All pages with user features

---

### admin_config.php
**File Reference:** [`admin_config.php`](c:\wamp64\www\lostfound\admin_config.php)

**Purpose:** Manages admin authentication (separate from regular users)

**Admin Credentials:**
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'isaacK@12345');
```
- Hard-coded credentials for simplicity
- `define()` creates global constants
- **Security Note:** Should use database with hashed passwords in production

#### Function: isAdminLoggedIn()
**Returns:** Boolean

```php
function isAdminLoggedIn() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
        return true;
    }
    return false;
}
```
- Checks separate admin session variable
- Independent from user authentication

#### Function: authenticateAdmin($username, $password)
**Returns:** Boolean - `true` if credentials valid

```php
function authenticateAdmin($username, $password) {
    if ($username == ADMIN_USERNAME && $password == ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}
```
- Direct comparison with constants
- Sets session on successful authentication
- Simple but functional for single admin

#### Function: logoutAdmin()
```php
function logoutAdmin() {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}
```
- Destroys entire session
- Redirects to admin login page

#### Function: requireAdmin()
```php
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}
```
- Protects admin-only pages
- Place at top of admin pages

**Referenced By:** [`admin_login.php`](c:\wamp64\www\lostfound\admin_login.php), [`admin_dashboard.php`](c:\wamp64\www\lostfound\admin_dashboard.php)

---

## Core Pages

### index.php
**File Reference:** [`index.php`](c:\wamp64\www\lostfound\index.php)

**Purpose:** Homepage - displays portal overview, statistics, and recent items

**Includes:**
```php
require_once 'db.php';
require_once 'user_config.php';
```

**Database Operations:**

1. **Fetch Recent Items:**
```php
$sql = "SELECT * FROM items ORDER BY created_at DESC LIMIT 6";
$result = mysqli_query($conn, $sql);
$recentItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
```
- `SELECT *` gets all columns
- `ORDER BY created_at DESC` sorts newest first
- `LIMIT 6` retrieves only 6 most recent
- `mysqli_fetch_all()` returns all rows as array
- `MYSQLI_ASSOC` uses column names as keys

2. **Get Statistics:**
```php
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items";
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);
```
- `COUNT(*)` counts all rows
- `SUM(CASE...)` conditional sum for counting
- Single query - efficient for performance
- Returns: `total`, `lost_count`, `found_count`

**Display Logic:**

**Navigation Menu:**
```php
<?php if (isUserLoggedIn()): ?>
    <li><a href="user_dashboard.php">My Dashboard</a></li>
    <li><a href="user_dashboard.php?logout=1">Logout</a></li>
<?php else: ?>
    <li><a href="user_login.php">Login</a></li>
    <li><a href="user_register.php">Register</a></li>
<?php endif; ?>
```
- Conditional navigation based on login status
- Alternative PHP syntax (`:` and `endif`)

**Statistics Display:**
```php
<h3><?php echo $stats['total']; ?></h3>
<p>Total Items</p>
```
- Displays calculated statistics
- Color-coded (blue/red/green)

**Recent Items Grid:**
```php
<?php if (count($recentItems) > 0): ?>
    <div class="items-grid">
        <?php foreach ($recentItems as $item): ?>
            <div class="item-card">
                <!-- Item details -->
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```
- `count()` checks if array has items
- `foreach` loops through each item
- Displays in grid layout

**Image Display with Safety Check:**
```php
<?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
         alt="<?php echo htmlspecialchars($item['title']); ?>">
<?php endif; ?>
```
- Checks if image field has value
- `file_exists()` verifies file is present
- `htmlspecialchars()` prevents XSS attacks
- Safe even if file deleted

**Date Formatting:**
```php
<?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
```
- `strtotime()` converts MySQL datetime to Unix timestamp
- `date()` formats timestamp to readable format
- Example output: "Jan 15, 2024 3:45 PM"

**Links To:** [`report_lost.php`](c:\wamp64\www\lostfound\report_lost.php), [`report_found.php`](c:\wamp64\www\lostfound\report_found.php), [`items.php`](c:\wamp64\www\lostfound\items.php), [`user_login.php`](c:\wamp64\www\lostfound\user_login.php), [`admin_login.php`](c:\wamp64\www\lostfound\admin_login.php)

---

### items.php
**File Reference:** [`items.php`](c:\wamp64\www\lostfound\items.php)

**Purpose:** Displays all items with search and filter functionality

**URL Parameters:**
```php
$filter = 'all';
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
}

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
```
- `$_GET` array contains URL parameters
- Example URL: `items.php?filter=lost&search=phone`
- Default values prevent undefined variable errors

**Dynamic SQL Query Building:**
```php
$sql = "SELECT * FROM items WHERE 1=1";

if ($filter != 'all') {
    $filter = mysqli_real_escape_string($conn, $filter);
    $sql .= " AND type = '$filter'";
}

if ($search != '') {
    $search = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (title LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%')";
}

$sql .= " ORDER BY created_at DESC";
```
- `WHERE 1=1` is always true - makes adding AND conditions easier
- `.=` appends to string variable
- `LIKE '%$search%'` matches partial text
- `%` wildcards match any characters before/after
- `OR` searches across multiple columns
- Final query dynamically built based on user input

**Search Form:**
```php
<form method="GET" style="display: flex; gap: 1rem;">
    <input type="text" 
           name="search" 
           value="<?php echo $search; ?>" 
           placeholder="🔍 Search items...">
    
    <select name="filter">
        <option value="all" <?php if($filter == 'all') echo 'selected'; ?>>All Items</option>
        <option value="lost" <?php if($filter == 'lost') echo 'selected'; ?>>Lost Items</option>
        <option value="found" <?php if($filter == 'found') echo 'selected'; ?>>Found Items</option>
    </select>
    
    <button type="submit">Apply Filters</button>
</form>
```
- `method="GET"` sends data in URL (allows bookmarking)
- `value="<?php echo $search; ?>"` maintains search term after submit
- `selected` attribute preserves dropdown selection
- Builds URL like: `items.php?search=phone&filter=lost`

**Statistics Display:**
```php
<div>Showing <?php echo count($items); ?> of <?php echo $stats['total']; ?> items</div>
```
- `count($items)` = filtered results
- `$stats['total']` = total in database

**Image Modal Functionality:**
```php
<img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
     onclick="openImageModal('uploads/<?php echo htmlspecialchars($item['image']); ?>', 
                              '<?php echo htmlspecialchars($item['title']); ?>')">
```
- Clickable images open enlarged view
- JavaScript function passes image path and caption

**Modal HTML:**
```php
<div id="imageModal" style="display: none; ..." onclick="closeImageModal()">
    <img id="modalImage">
    <p id="modalCaption"></p>
    <span onclick="closeImageModal()">&times;</span>
</div>
```
- Hidden by default (`display: none`)
- Full-screen overlay
- Click anywhere to close

**JavaScript Functions:**
```javascript
function openImageModal(imageSrc, title) {
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalCaption').textContent = title;
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
}
```

**Keyboard Support:**
```javascript
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
```
- ESC key closes modal
- Better accessibility

**No Results Handling:**
```php
<?php if (count($items) > 0): ?>
    <!-- Display items -->
<?php else: ?>
    <div>No items found</div>
    <?php if ($search != '' || $filter != 'all'): ?>
        <a href="items.php">View All Items</a>
    <?php else: ?>
        <a href="report_lost.php">Report Lost Item</a>
    <?php endif; ?>
<?php endif; ?>
```
- Different messages based on context
- Helpful actions for users

**Referenced By:** Navigation menus, homepage links

---

## Item Management

### report_lost.php
**File Reference:** [`report_lost.php`](c:\wamp64\www\lostfound\report_lost.php)

**Purpose:** Form for users to report lost items

**Form Submission Check:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
}
```
- `$_SERVER['REQUEST_METHOD']` contains HTTP method
- Only runs on form submission
- Prevents errors on page load

**Data Collection:**
```php
$title = $_POST['title'];
$description = $_POST['description'];
$location = $_POST['location'];
$contact = $_POST['contact'];
```
- `$_POST` superglobal contains form data
- Keys match input `name` attributes in HTML

**Basic Validation:**
```php
if (empty($title) || empty($description) || empty($location) || empty($contact)) {
    $message = 'Please fill in all fields';
} else {
    // Continue processing
}
```
- `empty()` checks for empty strings/null
- Client-side validation in [`script.js`](c:\wamp64\www\lostfound\script.js) for better UX

**Image Upload Process:**

1. **Check if File Uploaded:**
```php
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
```
- `$_FILES` superglobal contains file upload data
- `error == 0` means successful upload
- Error codes: 1=too large, 2=exceeds HTML limit, 4=no file, etc.

2. **Create Upload Directory:**
```php
$uploadsDir = 'uploads/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir);
}
```
- `is_dir()` checks if directory exists
- `mkdir()` creates directory with default permissions

3. **Validate File Type:**
```php
$imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

if (in_array($imageFileType, $allowedTypes)) {
```
- `pathinfo()` parses file path
- `PATHINFO_EXTENSION` extracts extension only
- `strtolower()` normalizes to lowercase
- `in_array()` checks if type allowed
- **Security:** Prevents uploading PHP files

4. **Generate Unique Filename:**
```php
$imageName = uniqid() . '.' . $imageFileType;
```
- `uniqid()` generates unique ID based on microtime
- Prevents filename conflicts
- Preserves original file extension
- Example: `65a7b2f1c3d4e.jpg`

5. **Move Uploaded File:**
```php
move_uploaded_file($_FILES['image']['tmp_name'], $uploadsDir . $imageName);
```
- `move_uploaded_file()` moves from temporary location
- `tmp_name` is PHP's temporary storage location
- Second parameter is permanent destination
- Returns true on success

**SQL Injection Prevention:**
```php
$title = mysqli_real_escape_string($conn, $title);
$description = mysqli_real_escape_string($conn, $description);
$location = mysqli_real_escape_string($conn, $location);
$contact = mysqli_real_escape_string($conn, $contact);
$imageName = mysqli_real_escape_string($conn, $imageName);
```
- `mysqli_real_escape_string()` escapes special characters
- Prevents SQL injection attacks
- Escapes: single quotes, double quotes, backslashes, NULL
- **Essential security measure**

**Database Insertion:**
```php
$sql = "INSERT INTO items (title, description, type, location, contact, image) 
        VALUES ('$title', '$description', 'lost', '$location', '$contact', '$imageName')";

if (mysqli_query($conn, $sql)) {
    $message = 'Lost item reported successfully!';
    $title = $description = $location = $contact = ''; // Clear variables
} else {
    $message = 'Error: ' . mysqli_error($conn);
}
```
- `type` hardcoded as `'lost'`
- `mysqli_query()` executes INSERT statement
- Clears form variables on success
- Shows MySQL error on failure

**Form HTML:**
```php
<form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
```
- `method="POST"` sends data in request body (not URL)
- `enctype="multipart/form-data"` **required** for file uploads
- `onsubmit="return validateForm()"` calls JavaScript validation
- Returns `false` prevents submission if validation fails

**Recent Lost Items Display:**
```php
$sql = "SELECT * FROM items WHERE type = 'lost' ORDER BY created_at DESC LIMIT 3";
$result = mysqli_query($conn, $sql);
$recentLostItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
```
- Shows 3 most recent lost items
- Helps users see if item already reported

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`user_config.php`](c:\wamp64\www\lostfound\user_config.php), [`script.js`](c:\wamp64\www\lostfound\script.js)

---

### report_found.php
**File Reference:** [`report_found.php`](c:\wamp64\www\lostfound\report_found.php)

**Purpose:** Form for users to report found items

**Key Differences from report_lost.php:**

1. **Item Type:**
```php
$sql = "INSERT INTO items (title, description, type, location, contact, image) 
        VALUES ('$title', '$description', 'found', '$location', '$contact', '$imageName')";
```
- Uses `'found'` instead of `'lost'`
- All other processing identical

2. **User Association:**
```php
$currentUserId = getCurrentUserId();
$userIdValue = $currentUserId ? "'$currentUserId'" : 'NULL';

$sql = "INSERT INTO items (user_id, title, description, type, location, contact, image) 
        VALUES ($userIdValue, '$title', '$description', 'found', '$location', '$contact', '$imageName')";
```
- Includes `user_id` column
- Links items to logged-in users
- Uses SQL `NULL` for guests (not string 'NULL')
- Ternary operator: `condition ? valueIfTrue : valueIfFalse`

3. **UI Color Scheme:**
- Green buttons (`btn-success`)
- Found-specific icons and messaging

4. **Privacy Guidelines:**
```html
<ul>
    <li>Don't share personal info found on items</li>
    <li>Avoid showing ID cards, phone numbers</li>
    <li>Cover or blur personal information in photos</li>
    <li>Let owner prove ownership through description</li>
</ul>
```
- Emphasizes privacy protection
- Important for found items

5. **Recent Found Items:**
```php
$sql = "SELECT * FROM items WHERE type = 'found' ORDER BY created_at DESC LIMIT 3";
```
- Shows found items instead of lost

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`user_config.php`](c:\wamp64\www\lostfound\user_config.php), [`script.js`](c:\wamp64\www\lostfound\script.js)

---

### edit_item.php
**File Reference:** [`edit_item.php`](c:\wamp64\www\lostfound\edit_item.php)

**Purpose:** Allows users to edit their posted items

**Access Control:**
```php
requireUser();
```
- Must be logged in to access
- Redirects to login if not authenticated

**Get Item ID from URL:**
```php
if (!isset($_GET['id'])) {
    header('Location: user_dashboard.php');
    exit();
}

$itemId = mysqli_real_escape_string($conn, $_GET['id']);
```
- Expects `edit_item.php?id=123`
- Redirects if no ID provided
- Escapes ID for security

**Ownership Verification:**
```php
$sql = "SELECT * FROM items WHERE id = '$itemId' AND user_id = '$userId'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    header('Location: user_dashboard.php');
    exit();
}

$item = mysqli_fetch_assoc($result);
```
- `AND user_id = '$userId'` ensures ownership
- Prevents users editing others' items
- Redirects if not owned or doesn't exist
- Stores item data for form pre-population

**Update Process:**

1. **Preserve Existing Image:**
```php
$imageName = $item['image']; // Default to current image
```

2. **Handle New Image Upload:**
```php
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    // Validate file type
    $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
    
    if (in_array($imageFileType, $allowedTypes)) {
        // Delete old image
        if ($item['image'] && file_exists($uploadsDir . $item['image'])) {
            unlink($uploadsDir . $item['image']);
        }
        
        // Upload new image
        $imageName = uniqid() . '.' . $imageFileType;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadsDir . $imageName);
    }
}
```
- Only uploads if new file selected
- `unlink()` deletes old image file
- Prevents orphaned files accumulating
- Generates new unique filename

3. **Database UPDATE:**
```php
$sql = "UPDATE items 
        SET title = '$title', 
            description = '$description', 
            location = '$location', 
            contact = '$contact', 
            image = '$imageName' 
        WHERE id = '$itemId' AND user_id = '$userId'";

if (mysqli_query($conn, $sql)) {
    $message = 'Item updated successfully!';
    $messageType = 'success';
    
    // Refresh item data
    $item['title'] = $title;
    $item['description'] = $description;
    // ...etc
}
```
- `UPDATE` modifies existing row
- `SET` specifies new values
- `WHERE` ensures correct item and owner
- Refreshes `$item` array to show updated data

**Form Pre-population:**
```php
<input type="text" 
       name="title" 
       value="<?php echo htmlspecialchars($item['title']); ?>" 
       required>
```
- `value` attribute shows current data
- `htmlspecialchars()` escapes HTML entities
- User sees existing data, can modify

**Current Image Display:**
```php
<?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
    <div class="form-group">
        <label>Current Image</label>
        <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
             alt="Current image" 
             style="max-width: 300px;">
    </div>
<?php endif; ?>
```
- Shows existing image preview
- User can see what they're replacing
- Optional new upload below

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`user_config.php`](c:\wamp64\www\lostfound\user_config.php)

**Called From:** [`user_dashboard.php`](c:\wamp64\www\lostfound\user_dashboard.php) edit links

---

## Admin System

### admin_login.php
**File Reference:** [`admin_login.php`](c:\wamp64\www\lostfound\admin_login.php)

**Purpose:** Login page for administrators

**Redirect if Already Logged In:**
```php
if (isAdminLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit();
}
```
- Prevents re-login
- Goes straight to dashboard

**Form Processing:**
```php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (authenticateAdmin($username, $password)) {
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
```
- Calls [`authenticateAdmin()`](#function-authenticateadminusername-password) from [`admin_config.php`](c:\wamp64\www\lostfound\admin_config.php)
- Redirects to dashboard on success
- Shows error message on failure

**UI Elements:**

1. **Security Warning:**
```html
<div class="security-notice">
    <strong>⚠️ Authorized Personnel Only</strong><br>
    This area is restricted to administrators only. All access attempts are logged.
</div>
```
- Visual deterrent
- Professional appearance

2. **Default Credentials Display:**
```html
<div>
    <h3>🔑 Default Credentials</h3>
    <p><strong>Username:</strong> admin</p>
    <p><strong>Password:</strong> isaacK@12345</p>
    <p>⚠️ Change these credentials in admin_config.php for production use</p>
</div>
```
- Development convenience
- **Remove in production environment**
- Security risk if left visible

3. **Auto-focus:**
```javascript
document.getElementById('username').focus();
```
- Cursor automatically in username field
- Better user experience
- Runs on page load

**Custom Styling:**
```css
.login-form {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    padding: 3rem 2rem;
    border-radius: 24px;
}
```
- Glassmorphism effect
- Red theme for admin area
- Visually distinct from user pages

**References:** [`admin_config.php`](c:\wamp64\www\lostfound\admin_config.php)

**Links To:** [`admin_dashboard.php`](c:\wamp64\www\lostfound\admin_dashboard.php)

---

### admin_dashboard.php
**File Reference:** [`admin_dashboard.php`](c:\wamp64\www\lostfound\admin_dashboard.php)

**Purpose:** Main admin control panel for managing the portal

**Access Control:**
```php
requireAdmin();
```
- Forces admin authentication
- Redirects to login if not admin
- First security check

**Logout Handling:**
```php
if (isset($_GET['logout'])) {
    logoutAdmin();
}
```
- URL: `admin_dashboard.php?logout=1`
- Calls [`logoutAdmin()`](#function-logoutadmin)

**Item Deletion (Admin Power):**
```php
if (isset($_POST['delete_item'])) {
    $itemId = mysqli_real_escape_string($conn, $_POST['item_id']);
    
    // Get image filename
    $sql = "SELECT image FROM items WHERE id = '$itemId'";
    $result = mysqli_query($conn, $sql);
    $item = mysqli_fetch_assoc($result);
    
    // Delete image file if exists
    if ($item['image'] && file_exists('uploads/' . $item['image'])) {
        unlink('uploads/' . $item['image']);
    }
    
    // Delete item from database
    $sql = "DELETE FROM items WHERE id = '$itemId'";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Item deleted successfully";
    } else {
        $error = "Error deleting item: " . mysqli_error($conn);
    }
}
```
- Admin can delete **any** item
- No ownership check required
- Deletes image file first
- Then removes database record
- `DELETE` SQL statement

**Deletion Request Approval:**
```php
if (isset($_POST['approve_deletion'])) {
    $requestId = mysqli_real_escape_string($conn, $_POST['request_id']);
    $itemId = mysqli_real_escape_string($conn, $_POST['item_id']);
    
    // Get and delete item image
    $sql = "SELECT image FROM items WHERE id = '$itemId'";
    $result = mysqli_query($conn, $sql);
    $item = mysqli_fetch_assoc($result);
    
    if ($item && $item['image'] && file_exists('uploads/' . $item['image'])) {
        unlink('uploads/' . $item['image']);
    }
    
    // Delete item
    $sql = "DELETE FROM items WHERE id = '$itemId'";
    if (mysqli_query($conn, $sql)) {
        $success = "Deletion request approved and item deleted";
    }
}
```
- Approves user deletion requests
- Request auto-deleted due to CASCADE constraint
- Maintains audit trail

**Deletion Request Rejection:**
```php
if (isset($_POST['reject_deletion'])) {
    $requestId = mysqli_real_escape_string($conn, $_POST['request_id']);
    
    $sql = "UPDATE deletion_requests SET status = 'rejected' WHERE id = '$requestId'";
    if (mysqli_query($conn, $sql)) {
        $success = "Deletion request rejected";
    }
}
```
- Marks request as rejected
- Item remains in database
- User can see rejection in dashboard

**User Deletion:**
```php
if (isset($_POST['delete_user'])) {
    $userId = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    $sql = "DELETE FROM users WHERE id = '$userId'";
    if (mysqli_query($conn, $sql)) {
        $success = "User deleted successfully";
    }
}
```
- Deletes user account
- CASCADE foreign key deletes user's items automatically
- CASCADE deletes user's deletion requests

**Get Pending Deletion Requests:**
```php
$sql = "SELECT dr.*, items.title, items.type, users.username 
        FROM deletion_requests dr
        JOIN items ON dr.item_id = items.id
        JOIN users ON dr.user_id = users.id
        WHERE dr.status = 'pending'
        ORDER BY dr.created_at DESC";
$result = mysqli_query($conn, $sql);
$deletionRequests = mysqli_fetch_all($result, MYSQLI_ASSOC);
```
- `JOIN` combines data from multiple tables
- `dr.*` gets all deletion_request columns
- Also gets item title, type, and username
- Only pending requests

**Get All Users with Statistics:**
```php
$sql = "SELECT users.*, COUNT(items.id) as item_count 
        FROM users 
        LEFT JOIN items ON users.id = items.user_id 
        GROUP BY users.id 
        ORDER BY users.created_at DESC";
$result = mysqli_query($conn, $sql);
$allUsers = mysqli_fetch_all($result, MYSQLI_ASSOC);
```
- `LEFT JOIN` includes users with zero items
- `COUNT(items.id)` counts items per user
- `GROUP BY users.id` groups results by user
- Shows how many items each user posted

**Portal Statistics:**
```php
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items";
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);
```
- Same query as homepage
- Shows admin overview

**Recent Items:**
```php
$sql = "SELECT * FROM items ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $sql);
$recentItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
```
- Last 10 items posted
- Admin overview of activity

**Delete Confirmation:**
```php
<form method="POST" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
    <button type="submit" name="delete_item">🗑️ Delete</button>
</form>
```
- JavaScript `confirm()` shows dialog box
- Returns `false` if user clicks "Cancel"
- Prevents accidental deletions

**Custom Admin Styling:**
```css
.admin-header {
    background: #dc3545;
    border-bottom: 3px solid #c82333;
}

.stat-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
```
- Red header distinguishes admin area
- Interactive hover effects
- Professional appearance

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`admin_config.php`](c:\wamp64\www\lostfound\admin_config.php)

---

## Setup System

### setup.php
**File Reference:** [`setup.php`](c:\wamp64\www\lostfound\setup.php)

**Purpose:** Database initialization and setup script

**Run Once:** Access via `http://localhost/lostfound/setup.php`

**What It Does:**
1. Creates database if doesn't exist
2. Creates users table
3. Creates items table
4. Creates deletion_requests table
5. Optionally inserts sample data
6. Creates uploads directory

**Database Connection (Without Database Selected):**
```php
$conn = mysqli_connect($host, $username, $password);
```
- Connects to MySQL server only
- Database doesn't exist yet
- Different from [`db.php`](c:\wamp64\www\lostfound\db.php) which selects database

**Create Database:**
```php
$sql = "CREATE DATABASE IF NOT EXISTS $database";

if (mysqli_query($conn, $sql)) {
    $messages[] = "✅ Database '$database' created successfully!";
}
```
- `IF NOT EXISTS` prevents errors if already exists
- Safe to run multiple times

**Select Database:**
```php
mysqli_select_db($conn, $database);
```
- Switches to newly created database
- All subsequent queries use this database

**Create Users Table:**
```php
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
```
- `AUTO_INCREMENT` generates IDs automatically
- `PRIMARY KEY` uniquely identifies each row
- `UNIQUE` prevents duplicate usernames/emails
- `VARCHAR(255)` accommodates bcrypt hashes
- `TIMESTAMP` auto-sets creation time

**Create Items Table:**
```php
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(100) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
```
- `user_id` can be NULL for guest posts
- `ENUM('lost', 'found')` restricts to two values
- `TEXT` allows long descriptions
- `FOREIGN KEY` links to users table
- `ON DELETE CASCADE` auto-deletes items when user deleted

**Create Deletion Requests Table:**
```php
$sql = "CREATE TABLE IF NOT EXISTS deletion_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
```
- Tracks user deletion requests
- `status` defaults to 'pending'
- CASCADE deletes when item or user deleted

**Sample Data Insertion:**
```php
if (isset($_POST['sample_data'])) {
    $sampleItems = [
        [
            'title' => 'Black iPhone 13',
            'description' => 'Black iPhone 13 with clear case...',
            'type' => 'lost',
            'location' => 'Main Library, 2nd Floor',
            'contact' => 'student1@university.edu'
        ],
        // ... more items
    ];
    
    foreach ($sampleItems as $item) {
        $title = mysqli_real_escape_string($conn, $item['title']);
        $description = mysqli_real_escape_string($conn, $item['description']);
        // ... escape all fields
        
        $sql = "INSERT INTO items (title, description, type, location, contact) 
                VALUES ('$title', '$description', '$type', '$location', '$contact')";
        
        mysqli_query($conn, $sql);
    }
}
```
- Array of predefined items
- Loops and inserts each
- Good for testing/demonstration
- Optional checkbox in form

**Create Uploads Directory:**
```php
$uploadsDir = 'uploads';
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        $messages[] = "✅ Uploads directory created successfully!";
    }
}
```
- `mkdir()` creates directory
- `0755` sets permissions (owner: rwx, others: r-x)
- `true` creates parent directories if needed

**Check if Database Exists:**
```php
$testConn = @mysqli_connect($host, $username, $password);

if ($testConn) {
    $result = mysqli_query($testConn, "SHOW DATABASES LIKE '$database'");
    
    if ($result && mysqli_num_rows($result) > 0) {
        mysqli_select_db($testConn, $database);
        $result = mysqli_query($testConn, "SHOW TABLES LIKE 'items'");
        
        if ($result && mysqli_num_rows($result) > 0) {
            $databaseExists = true;
        }
    }
    
    mysqli_close($testConn);
}
```
- `@` suppresses errors
- `SHOW DATABASES LIKE` checks database existence
- `SHOW TABLES LIKE` checks table existence
- Used to display appropriate message

**System Information Display:**
```php
<div>PHP Version: <?php echo PHP_VERSION; ?></div>
<div>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
<div>Upload Max Size: <?php echo ini_get('upload_max_filesize'); ?></div>
<div>Post Max Size: <?php echo ini_get('post_max_size'); ?></div>
```
- `PHP_VERSION` constant
- `$_SERVER['SERVER_SOFTWARE']` shows Apache/nginx version
- `ini_get()` retrieves PHP configuration values
- Helpful for troubleshooting

**References:** Self-contained, creates foundation for entire system

---

## User Account Pages

### user_register.php
**File Reference:** [`user_register.php`](c:\wamp64\www\lostfound\user_register.php)

**Purpose:** User registration page

**Redirect if Logged In:**
```php
if (isUserLoggedIn()) {
    header('Location: user_dashboard.php');
    exit();
}
```
- No need to register if already logged in

**Form Processing:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } else {
        $error = registerUser($conn, $username, $email, $password);
        
        if (empty($error)) {
            header('Location: user_login.php?registered=1');
            exit();
        } else {
            $message = $error;
            $messageType = 'error';
        }
    }
}
```
- Password confirmation check
- Calls [`registerUser()`](#function-registeruserconn-username-email-password)
- Redirects to login on success
- `?registered=1` triggers success message

**Password Confirmation Field:**
```php
<div class="form-group">
    <label for="confirm_password">Confirm Password *</label>
    <input type="password" 
           id="confirm_password" 
           name="confirm_password" 
           placeholder="Re-enter your password"
           required>
</div>
```
- Ensures user typed password correctly
- Client-side validation via `required`
- Server-side validation in PHP

**Form Persistence:**
```php
<input type="text" 
       name="username" 
       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
       required>
```
- Repopulates username/email on error
- `isset()` prevents undefined variable warning
- Ternary operator provides empty string fallback

**Link to Login:**
```php
<p>Already have an account? 
    <a href="user_login.php">Login here</a>
</p>
```
- Convenient navigation
- Good UX

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`user_config.php`](c:\wamp64\www\lostfound\user_config.php)

---

### user_login.php
**File Reference:** [`user_login.php`](c:\wamp64\www\lostfound\user_login.php)

**Purpose:** User login page

**Redirect if Logged In:**
```php
if (isUserLoggedIn()) {
    header('Location: user_dashboard.php');
    exit();
}
```

**Registration Success Message:**
```php
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = 'Account created successfully! Please login with your credentials.';
    $messageType = 'success';
}
```
- Displays success message from registration
- URL: `user_login.php?registered=1`
- Good user feedback

**Form Processing:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $error = loginUser($conn, $username, $password);
    
    if (empty($error)) {
        header('Location: user_dashboard.php');
        exit();
    } else {
        $message = $error;
        $messageType = 'error';
    }
}
```
- Calls [`loginUser()`](#function-loginuserconn-username-password)
- Redirects to dashboard on success
- Shows error on failure

**Simple Form:**
```html
<form method="POST">
    <input type="text" name="username" placeholder="Enter your username" required>
    <input type="password" name="password" placeholder="Enter your password" required>
    <button type="submit">Login</button>
</form>
```
- Minimal required fields
- Quick login process

**Link to Registration:**
```php
<p>Don't have an account? 
    <a href="user_register.php">Create one here</a>
</p>
```

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`user_config.php`](c:\wamp64\www\lostfound\user_config.php)

---

### user_dashboard.php
**File Reference:** [`user_dashboard.php`](c:\wamp64\www\lostfound\user_dashboard.php)

**Purpose:** Personal dashboard for logged-in users

**Access Control:**
```php
requireUser();
```
- Only logged-in users can access
- Redirects to login otherwise

**Logout:**
```php
if (isset($_GET['logout'])) {
    logoutUser();
}
```
- URL: `user_dashboard.php?logout=1`

**Get User Info:**
```php
$userId = getCurrentUserId();
$username = getCurrentUsername();
$userEmail = getCurrentUserEmail();
```
- Retrieved from session
- Used in UI personalization

**Item Deletion:**
```php
if (isset($_POST['delete_item'])) {
    $itemId = mysqli_real_escape_string($conn, $_POST['item_id']);
    
    // Verify ownership
    $sql = "SELECT id, image FROM items WHERE id = '$itemId' AND user_id = '$userId'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $item = mysqli_fetch_assoc($result);
        
        // Delete image file
        if ($item['image'] && file_exists('uploads/' . $item['image'])) {
            unlink('uploads/' . $item['image']);
        }
        
        // Delete from database
        $sql = "DELETE FROM items WHERE id = '$itemId' AND user_id = '$userId'";
        if (mysqli_query($conn, $sql)) {
            $message = 'Item deleted successfully!';
            $messageType = 'success';
        }
    }
}
```
- Ownership verification critical
- `AND user_id = '$userId'` prevents unauthorized deletion
- Deletes file then database record
- Shows success/error message

**User Statistics:**
```php
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
        SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
        FROM items WHERE user_id = '$userId'";
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);
```
- Statistics filtered to current user
- Shows personal activity

**Get User's Items:**
```php
$sql = "SELECT * FROM items WHERE user_id = '$userId' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$userItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
```
- Only items posted by current user
- Newest first

**Welcome Message:**
```php
<h2>👤 Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
<p>Email: <?php echo htmlspecialchars($userEmail); ?></p>
```
- Personalized greeting
- Displays user info

**Item Actions:**
```php
<a href="edit_item.php?id=<?php echo $item['id']; ?>">✏️ Edit</a>

<form method="POST" onsubmit="return confirm('Are you sure you want to delete this item?')">
    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
    <button type="submit" name="delete_item">🗑️ Delete</button>
</form>
```
- Edit link passes item ID
- Delete confirmation dialog
- Hidden input transmits item ID

**Empty State:**
```php
<?php if (count($userItems) > 0): ?>
    <!-- Display items -->
<?php else: ?>
    <p>You haven't posted any items yet.</p>
    <a href="report_lost.php">Report Lost Item</a>
    <a href="report_found.php">Report Found Item</a>
<?php endif; ?>
```
- Helpful message if no items
- Call-to-action buttons

**References:** [`db.php`](c:\wamp64\www\lostfound\db.php), [`user_config.php`](c:\wamp64\www\lostfound\user_config.php)

**Links To:** [`edit_item.php`](c:\wamp64\www\lostfound\edit_item.php)

---

## Frontend Assets

### style.css
**File Reference:** [`style.css`](c:\wamp64\www\lostfound\style.css)

**Purpose:** Complete stylesheet for the portal

**CSS Variables (Custom Properties):**
```css
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --success: #10b981;
    --error: #ef4444;
    --border: #e2e8f0;
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}
```
- Centralized color/value management
- Easy theme customization
- Used throughout CSS: `color: var(--primary);`

**Reset & Base:**
```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.7;
    color: var(--text-primary);
    background: var(--bg-secondary);
}
```
- `box-sizing: border-box` makes width calculations easier
- System font stack for native look
- Consistent line-height for readability

**Header:**
```css
header {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 100;
}
```
- `position: sticky` keeps header visible when scrolling
- `z-index: 100` ensures it stays above content
- Clean border separation

**Navigation:**
```css
nav a {
    color: var(--text-secondary);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

nav a:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

nav a.active {
    background: var(--primary);
    color: white;
}
```
- Smooth hover transitions
- Active state for current page
- Rounded corners for modern look

**Hero Section:**
```css
.hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 4rem 2rem;
    border-radius: 12px;
}
```
- Gradient background
- Generous padding
- Eye-catching banner

**Form Inputs:**
```css
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
```
- Removes default outline
- Custom focus ring
- Accessibility-friendly visual indicator

**Buttons:**
```css
.btn {
    background: var(--primary);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}
```
- Consistent button styling
- Hover lift effect (`translateY`)
- Smooth transitions

**Item Cards:**
```css
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.item-card {
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.item-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
```
- CSS Grid for responsive layout
- `auto-fill` creates flexible columns
- `minmax(280px, 1fr)` sets min/max widths
- Hover elevation effect

**Item Type Badges:**
```css
.item-type.lost {
    background: var(--error-light);
    color: var(--error);
}

.item-type.found {
    background: var(--success-light);
    color: var(--success);
}
```
- Color-coded for quick identification
- Red for lost, green for found

**Image Styling:**
```css
.item-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.item-image:hover {
    transform: scale(1.02);
}
```
- `object-fit: cover` maintains aspect ratio
- Fixed height prevents layout shift
- Clickable cursor
- Subtle zoom on hover

**Alert Messages:**
```css
.alert-success {
    background: var(--success-light);
    color: var(--success);
    border-color: var(--success);
}

.alert-error {
    background: var(--error-light);
    color: var(--error);
    border-color: var(--error);
}
```
- Color-coded feedback
- Clear visual distinction

**Responsive Design:**
```css
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
    }
    
    .items-grid {
        grid-template-columns: 1fr;
    }
    
    .hero h2 {
        font-size: 2.2rem;
    }
}
```
- Tablet breakpoint at 768px
- Stacks elements vertically
- Single column grid
- Smaller font sizes

**Mobile:**
```css
@media (max-width: 480px) {
    main {
        padding: 0 15px;
    }
    
    .hero {
        padding: 2.5rem 1rem;
    }
}
```
- Phone breakpoint at 480px
- Reduced padding
- Touch-friendly sizing

**Smooth Scrolling:**
```css
html {
    scroll-behavior: smooth;
}
```
- Smooth anchor link scrolling

**Custom Scrollbar:**
```css
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: var(--bg-secondary);
}

::-webkit-scrollbar-thumb {
    background: var(--border-dark);
    border-radius: 5px;
}
```
- Styled scrollbar (webkit browsers)
- Matches overall design

**Text Selection:**
```css
::selection {
    background: var(--primary);
    color: white;
}
```
- Branded selection color

**Fade-in Animation:**
```css
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.6s ease forwards;
}
```
- Subtle entrance animation
- Can be applied to elements

**Referenced By:** All HTML pages

---

### script.js
**File Reference:** [`script.js`](c:\wamp64\www\lostfound\script.js)

**Purpose:** Client-side form validation

**Main Function: validateForm()**
```javascript
function validateForm() {
    var title = document.getElementById('title');
    var description = document.getElementById('description');
    var location = document.getElementById('location');
    var contact = document.getElementById('contact');
    
    if (title && title.value == '') {
        alert('Please enter item title');
        return false;
    }
    
    if (description && description.value == '') {
        alert('Please enter description');
        return false;
    }
    
    if (location && location.value == '') {
        alert('Please enter location');
        return false;
    }
    
    if (contact && contact.value == '') {
        alert('Please enter contact email');
        return false;
    }
    
    // Simple email check
    if (contact && contact.value.indexOf('@') == -1) {
        alert('Please enter a valid email');
        return false;
    }
    
    return true;
}
```

**How It Works:**
1. `document.getElementById()` gets form elements
2. Checks if element exists (`if (title)`)
3. Checks if value is empty (`value == ''`)
4. `alert()` shows popup message
5. `return false` prevents form submission
6. `return true` allows form submission

**Email Validation:**
```javascript
if (contact.value.indexOf('@') == -1) {
```
- `indexOf('@')` searches for @ symbol
- Returns `-1` if not found
- Simple but effective basic check

**Usage in Forms:**
```html
<form onsubmit="return validateForm();">
```
- `onsubmit` event fires when form submitted
- `return` keyword passes result to form
- `false` cancels submission
- `true` allows submission

**Why Client-Side Validation:**
- Immediate feedback (no page reload)
- Better user experience
- Reduces server load
- Still need server-side validation (security)

**Limitations:**
- Can be bypassed (JavaScript disabled)
- Not a security measure
- HTML5 `required` attribute provides fallback

**Enhancement Opportunities:**
- Better email regex validation
- Real-time validation (on input)
- Field-specific error messages
- Visual error indicators

**Referenced By:** [`report_lost.php`](c:\wamp64\www\lostfound\report_lost.php), [`report_found.php`](c:\wamp64\www\lostfound\report_found.php)

---

## Database Schema

### Database: lostfound_db

#### Table: users
**Purpose:** Stores registered user accounts

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Columns:**
- `id` - Unique identifier, auto-increments
- `username` - Unique username, max 50 characters
- `email` - Unique email, max 100 characters
- `password` - Bcrypt hash (60 chars, but 255 for future algorithms)
- `created_at` - Registration timestamp

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `username`
- UNIQUE on `email`

**Used By:** [`user_config.php`](c:\wamp64\www\lostfound\user_config.php) functions

---

#### Table: items
**Purpose:** Stores all lost and found items

```sql
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(100) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Columns:**
- `id` - Unique identifier
- `user_id` - Links to users table (NULL for guest posts)
- `title` - Item name, max 100 characters
- `description` - Detailed description (TEXT = 65,535 chars)
- `type` - Either 'lost' or 'found' (ENUM restricts values)
- `location` - Where lost/found, max 100 characters
- `contact` - Email address, max 100 characters
- `image` - Filename only (not full path), max 255 characters
- `created_at` - When posted

**Relationships:**
- `FOREIGN KEY` to users table
- `ON DELETE CASCADE` - deletes items when user deleted

**Indexes:**
- PRIMARY KEY on `id`
- Foreign key index on `user_id`

**Used By:** All pages that display or create items

---

#### Table: deletion_requests
**Purpose:** Tracks user requests to delete items (requires admin approval)

```sql
CREATE TABLE deletion_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Columns:**
- `id` - Unique identifier
- `item_id` - Item to be deleted
- `user_id` - User requesting deletion
- `status` - Current status (pending/approved/rejected)
- `created_at` - When requested

**Relationships:**
- Links to both items and users tables
- CASCADE delete when item or user deleted

**Workflow:**
1. User requests deletion (inserts row with 'pending' status)
2. Admin reviews in dashboard
3. Admin approves → deletes item (CASCADE removes request)
4. Admin rejects → updates status to 'rejected'

**Used By:** [`user_dashboard.php`](c:\wamp64\www\lostfound\user_dashboard.php), [`admin_dashboard.php`](c:\wamp64\www\lostfound\admin_dashboard.php)

---

## File Structure Summary

```
lostfound/
├── db.php                  # Database connection
├── user_config.php         # User authentication functions
├── admin_config.php        # Admin authentication functions
├── index.php               # Homepage
├── items.php               # Browse all items
├── report_lost.php         # Report lost item form
├── report_found.php        # Report found item form
├── user_login.php          # User login page
├── user_register.php       # User registration page
├── user_dashboard.php      # User personal dashboard
├── edit_item.php           # Edit item form
├── admin_login.php         # Admin login page
├── admin_dashboard.php     # Admin control panel
├── setup.php               # Database setup script
├── style.css               # Stylesheet
├── script.js               # JavaScript validation
├── README.md               # Project documentation
└── uploads/                # Image upload directory (created by setup)
```

---

## Common Patterns & Practices

### Security Measures

**1. SQL Injection Prevention:**
```php
$username = mysqli_real_escape_string($conn, $username);
```
- Used before every database query
- Escapes dangerous characters

**2. XSS Prevention:**
```php
<?php echo htmlspecialchars($item['title']); ?>
```
- Escapes HTML entities in output
- Prevents script injection

**3. Password Hashing:**
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
if (password_verify($password, $user['password'])) {
```
- Uses bcrypt algorithm
- Never stores plain passwords

**4. File Upload Validation:**
```php
$allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
if (in_array($imageFileType, $allowedTypes)) {
```
- Whitelist approach
- Prevents malicious file uploads

**5. Authentication Checks:**
```php
requireUser();  // Protects user pages
requireAdmin(); // Protects admin pages
```
- Centralized access control

### Database Patterns

**1. Prepared Statements Alternative:**
```php
$username = mysqli_real_escape_string($conn, $username);
$sql = "SELECT * FROM users WHERE username = '$username'";
```
- While this project uses escaping, prepared statements are preferred:
```php
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
```

**2. Conditional Counting:**
```php
SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count
```
- Efficient single-query statistics

**3. Joins for Related Data:**
```php
SELECT dr.*, items.title, users.username 
FROM deletion_requests dr
JOIN items ON dr.item_id = items.id
JOIN users ON dr.user_id = users.id
```
- Reduces multiple queries

### PHP Patterns

**1. Include Once:**
```php
require_once 'db.php';
```
- `require` stops on error
- `once` prevents duplicate inclusion

**2. Alternative Syntax:**
```php
<?php if ($condition): ?>
    HTML content
<?php endif; ?>
```
- Cleaner for templates

**3. Ternary Operator:**
```php
$value = $condition ? $trueValue : $falseValue;
```
- Concise conditional assignment

**4. Short Echo:**
```php
<?php echo $variable; ?>
<!-- or -->
<?= $variable ?>  
```
- `<?=` is shorthand for echo

### Error Handling

**1. Database Errors:**
```php
if (!mysqli_query($conn, $sql)) {
    $error = "Error: " . mysqli_error($conn);
}
```
- Captures and displays MySQL errors

**2. File Operations:**
```php
if (file_exists('uploads/' . $filename)) {
    unlink('uploads/' . $filename);
}
```
- Checks before operations

**3. User Feedback:**
```php
$message = 'Success message';
$messageType = 'success'; // or 'error'
```
- Consistent messaging pattern

---

## Usage Examples

### Adding a New Feature

**Example: Add "Mark as Resolved" Feature**

1. **Database:** Add column to items table
```sql
ALTER TABLE items ADD COLUMN resolved BOOLEAN DEFAULT FALSE;
```

2. **PHP Logic:** In user_dashboard.php
```php
if (isset($_POST['mark_resolved'])) {
    $itemId = mysqli_real_escape_string($conn, $_POST['item_id']);
    $sql = "UPDATE items SET resolved = TRUE WHERE id = '$itemId' AND user_id = '$userId'";
    mysqli_query($conn, $sql);
}
```

3. **HTML Form:**
```php
<form method="POST">
    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
    <button type="submit" name="mark_resolved">Mark Resolved</button>
</form>
```

4. **Display:** Filter resolved items
```php
$sql = "SELECT * FROM items WHERE resolved = FALSE ORDER BY created_at DESC";
```

### Customization Tips

**Change Colors:**
```css
/* In style.css */
:root {
    --primary: #your-color;
    --success: #your-success-color;
}
```

**Add New Item Field:**
1. Add to database: `ALTER TABLE items ADD COLUMN category VARCHAR(50);`
2. Add to form: `<input type="text" name="category">`
3. Add to INSERT: Include category in SQL
4. Add to display: Show in item cards

**Email Notifications:**
```php
// After item insertion
$to = 'admin@university.edu';
$subject = 'New Lost Item Reported';
$message = "Item: $title\nDescription: $description";
mail($to, $subject, $message);
```

---

## Troubleshooting

### Common Issues

**1. "Connection failed" Error:**
- Check database credentials in db.php
- Ensure MySQL server is running
- Verify database exists

**2. Images Not Uploading:**
- Check uploads directory exists and has write permissions
- Verify `upload_max_filesize` in php.ini
- Check `post_max_size` in php.ini

**3. "Session already started" Warning:**
- Only one `session_start()` per page
- Included files may be calling it multiple times

**4. SQL Errors:**
- Run setup.php to create tables
- Check for typos in column names
- Verify foreign key relationships

**5. Blank Pages:**
- Enable error display in PHP:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## Best Practices Implemented

1. **Separation of Concerns:** Database connection separate from logic
2. **DRY Principle:** Reusable functions in config files
3. **Security First:** Input validation, output escaping, password hashing
4. **User Experience:** Clear messages, confirmations, helpful errors
5. **Responsive Design:** Mobile-friendly layouts
6. **Accessibility:** Semantic HTML, keyboard support
7. **Performance:** Efficient queries, minimal database calls
8. **Maintainability:** Clear code structure, comments, consistent naming

---

## Conclusion

This Lost and Found Portal demonstrates a complete, functional web application using core web technologies. The codebase is structured for readability and maintainability, with security measures and user experience considerations throughout.

**Key Takeaways:**
- **PHP** handles server-side logic, database operations, and authentication
- **MySQL** stores all data with relational integrity
- **MySQLi** provides secure database interface
- **CSS** creates professional, responsive design
- **JavaScript** enhances user experience with validation
- **Security** is prioritized through escaping, hashing, and validation
- **User roles** (guest/user/admin) provide appropriate access levels

**For Production Deployment:**
1. Change admin credentials
2. Use environment variables for database config
3. Implement HTTPS
4. Add CSRF protection
5. Use prepared statements
6. Add logging and monitoring
7. Implement rate limiting
8. Regular backups
9. Error logging to files (not display)
10. Input validation library

---

*Documentation generated for Lost and Found Portal*  
*All code references are absolute paths for easy navigation*
