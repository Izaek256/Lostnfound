# ServerC Documentation - User Interface Client

## 🎯 Server Role & Responsibility

**ServerC** is the **User Interface Client** in the Lost & Found distributed system. It serves as the **presentation layer**, rendering all HTML pages, handling user interactions, and communicating with backend servers via API calls.

### Core Responsibilities
✅ Render all frontend pages (HTML/CSS/JavaScript)  
✅ Handle user interactions and form submissions  
✅ Make API calls to ServerA (items) and ServerB (users)  
✅ Manage user sessions and authentication state  
✅ Upload files to ServerB's storage directory  
✅ Display dynamic data from backend APIs  
✅ Provide responsive mobile-friendly UI  

### What ServerC Does NOT Do
❌ **Connect directly to the database** (all data via APIs)  
❌ Hash passwords (ServerB handles this)  
❌ Store user data permanently (session-based only)  
❌ Process SQL queries  
❌ Implement business logic (delegates to ServerA/B)  

### Critical Design Constraint
**ServerC CANNOT access the database directly.** This is enforced in `config.php`:
```php
function connectDB() {
    die("ERROR: ServerC cannot connect directly to the database.\n
         ServerC must use ServerA APIs for all database operations.\n
         This ensures ServerA is the single point of database access.");
}
```

---

## 🌐 Network Configuration

### Server Details
- **IP Address**: `172.24.14.184`
- **Base URL**: `http://172.24.14.184/Lostnfound/ServerC`
- **Operating System**: Windows 23H2 with WAMP
- **Web Server**: Apache/2.4.62
- **PHP Version**: 8.3.14
- **Role**: User Interface Client (Frontend)

### API Communication
- **ServerA URL**: `http://172.24.194.6/Lostnfound/ServerA/api` (item operations)
- **ServerB URL**: `http://172.24.194.6/Lostnfound/ServerB/api` (user operations)
- **Communication Method**: HTTP REST API via cURL
- **Database Access**: None (API only)

### File Upload Strategy
ServerC uploads files to ServerB's directory via **filesystem access**:
```php
$upload_dir = '../ServerB/uploads/';  // Network mount or shared directory
move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename);
```

This assumes ServerB's uploads directory is accessible via:
- **Network share/mount** (if servers on different machines)
- **Local filesystem** (if servers share the same filesystem)
- **HTTP upload** (future enhancement via ServerB API)

---

## 📁 File Structure

```
ServerC/
├── api/
│   └── health.php               # Health check endpoint
├── assets/
│   ├── style.css                # Main stylesheet (2083 lines)
│   ├── script.js                # Client-side JavaScript
│   ├── logo.webp                # Application logo
│   └── favicon.svg              # Browser favicon
├── index.php                    # Homepage (stats, recent items)
├── items.php                    # Browse all items (search/filter)
├── report_lost.php              # Report lost item form
├── report_found.php             # Report found item form
├── edit_item.php                # Edit existing item
├── user_login.php               # User login page
├── user_register.php            # User registration page
├── user_dashboard.php           # User's personal dashboard
├── admin_dashboard.php          # Admin control panel
├── config.php                   # Client configuration (390 lines)
├── api_client.php               # OOP API client class (274 lines)
└── deployment_config.php        # Auto-generated deployment config
```

---

## 🔧 Core Configuration (`config.php`)

### Purpose
The `config.php` file provides:
- **Disabled database functions** (security enforcement)
- **Advanced API request function** (`makeAPIRequest()`)
- **Image path helpers** (URL generation)
- **Session management functions**
- **Server connectivity testing**
- **CORS handling** (for health endpoints)

### Key Functions

#### 1. Database Access Prevention

```php
function connectDB() {
    die("ERROR: ServerC cannot connect directly to the database...");
}

function connectServerA() {
    die("ERROR: ServerC cannot connect directly to the database.");
}

function connectServerB() {
    die("ERROR: ServerC cannot connect directly to the database.");
}
```

**Purpose**: Enforce architectural separation. If any code tries to connect to the database, the script immediately terminates with a clear error message.

#### 2. Enhanced API Request Function

**`makeAPIRequest($url, $data, $method, $options)`**

This is the **most critical function** in ServerC, enabling all backend communication.

**Parameters:**
- `$url` (string): Target API endpoint URL
- `$data` (array): Request data (POST body or GET params)
- `$method` (string): HTTP method ('GET', 'POST', 'PUT', 'DELETE')
- `$options` (array): Configuration options

**Options Array:**
| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `retry_count` | int | 3 | Retry attempts on failure |
| `retry_delay` | int | 1 | Seconds between retries |
| `timeout` | int | 30 | Request timeout (seconds) |
| `connect_timeout` | int | 10 | Connection timeout (seconds) |
| `return_json` | bool | false | Auto-parse JSON response |
| `verify_ssl` | bool | false | Verify SSL certificates |
| `send_json` | bool | false | Send as JSON instead of form-data |
| `force_json` | bool | false | Force JSON parsing |

**Advanced Features:**
✅ **Automatic retry logic** with linear backoff (1s, 2s, 3s)  
✅ **Smart error handling** (don't retry 4xx errors)  
✅ **Comprehensive logging** with timing metrics  
✅ **Response validation** (HTTP codes, empty responses)  
✅ **Content encoding support** (gzip, deflate, br)  
✅ **IPv4 enforcement** for better compatibility  
✅ **User-Agent identification** (`LostFound-ServerC/2.0`)  

**Usage Example:**
```php
// Get all items from ServerA
$response = makeAPIRequest(
    SERVERA_URL . '/get_all_items.php',
    ['type' => 'lost', 'search' => 'iPhone'],
    'GET',
    ['return_json' => true, 'force_json' => true]
);

if (is_array($response) && isset($response['success'])) {
    $items = $response['items'];
} else {
    $error = $response['error'] ?? 'Unknown error';
}
```

**Error Handling:**
```php
// Returns error in format:
// If return_json = false: "error|[message]"
// If return_json = true:  ['success' => false, 'error' => '...']
```

**Performance Logging:**
```
[APIRequest] Success: GET /get_all_items.php | HTTP 200 | 145.23ms
```

#### 3. Image Path Helper Functions

**`getImageUrl($filename)`**
- Returns browser-accessible URL for image display
- Checks if local mount exists, falls back to HTTP URL
- Handles empty filenames gracefully

```php
function getImageUrl($filename) {
    if (empty($filename)) return '';
    
    // Check if local mount exists
    $localPath = UPLOADS_PATH . $filename;
    if (file_exists($localPath)) {
        return UPLOADS_URL . $filename;  // Use local path
    }
    
    // Fallback to HTTP URL
    return UPLOADS_HTTP_URL . $filename;
}
```

**Usage in HTML:**
```php
<img src="<?php echo getImageUrl($item['image']); ?>" alt="Item">
```

**`getImagePath($filename)`**
- Returns filesystem path for file operations
- Used for file existence checks

**`imageExists($filename)`**
- Check if image file exists before displaying

**Configuration Constants:**
```php
define('UPLOADS_PATH', __DIR__ . '/../ServerB/uploads/');       // Filesystem path
define('UPLOADS_URL', '../ServerB/uploads/');                   // Relative browser path
define('UPLOADS_HTTP_URL', 'http://172.24.194.6/Lostnfound/ServerB/uploads/');  // HTTP URL
```

#### 4. Server Connectivity Functions

**`testServerConnection($server_url, $timeout)`**
- Tests if a server is reachable via health endpoint
- Returns detailed response including timing metrics

```php
$result = testServerConnection(SERVERA_URL, 5);
// Returns: ['success' => true/false, 'response_time' => 123.45, 'http_code' => 200, ...]
```

**`getServerStatus($server_url, $server_name, $timeout)`**
- Enhanced wrapper around `testServerConnection()`
- Returns formatted status object for display

**`areAllServersOnline()`**
- Quick check if both ServerA and ServerB are reachable
- Used for system health dashboards

```php
if (!areAllServersOnline()) {
    echo "Warning: Some servers are offline";
}
```

---

## 🎨 Frontend Pages Documentation

### 1. Homepage - `index.php`

**Purpose**: Landing page with statistics and recent items.

**Key Features:**
- Display portal statistics (total, lost, found counts)
- Show 6 most recent items
- Quick action buttons (Report Lost/Found, Browse Items)
- "How It Works" section
- Tips for success

**API Calls:**
```php
// Get recent items (limit 6)
$response = makeAPIRequest(SERVERA_URL . '/get_all_items.php', 
    ['limit' => 6, 'sort' => 'recent'], 'GET');

// Get statistics
$response = makeAPIRequest(SERVERA_URL . '/get_all_items.php', [], 'GET');
$stats = $response['stats'];  // total, lost_count, found_count
```

**Data Processing:**
```php
// Handle different API response structures
if (isset($decoded['items'])) {
    $recentItems = array_slice($decoded['items'], 0, 6);
} else if (isset($decoded['data'])) {
    $recentItems = array_slice($decoded['data'], 0, 6);
} else {
    $recentItems = array_slice($decoded, 0, 6);
}
```

**UI Components:**
- **Hero Section**: Welcome message with gradient background
- **Statistics Cards**: Visual display of item counts
- **Item Grid**: Recent items in responsive grid layout
- **How It Works**: Educational section with icons
- **Tips Section**: Best practices for users

**Responsive Design:**
- Desktop: 3-column grid for statistics
- Tablet: 2-column grid
- Mobile: 1-column stacked layout

---

### 2. Browse Items - `items.php`

**Purpose**: Comprehensive item browsing with search and filters.

**Key Features:**
- Search by keywords (title, description, location)
- Filter by type (all, lost, found)
- Display all items in grid layout
- Show item statistics
- Image modal for enlarged view

**API Call:**
```php
$api_response = makeAPIRequest(
    SERVERA_URL . '/get_all_items.php', 
    [
        'type' => $filter !== 'all' ? $filter : '',
        'search' => $search
    ], 
    'GET', 
    ['return_json' => true]
);

$items = $api_response['items'] ?? [];
$stats = $api_response['stats'] ?? [];
```

**Search & Filter Form:**
```php
<form method="GET">
    <input type="text" name="search" placeholder="Search..." 
           value="<?php echo htmlspecialchars($search); ?>">
    <select name="filter">
        <option value="all">All Items</option>
        <option value="lost">Lost Items</option>
        <option value="found">Found Items</option>
    </select>
    <button type="submit">Apply Filters</button>
</form>
```

**Statistics Display:**
- Total items count
- Lost items count (red accent)
- Found items count (green accent)
- Current filter results count

**Item Cards:**
Each item displayed with:
- Badge (Lost=red, Found=green)
- Image or placeholder
- Title with icon
- Description (with "Read more" for long text)
- Location with map icon
- Contact email with envelope icon
- Timestamp with clock icon

---

### 3. Report Lost Item - `report_lost.php`

**Purpose**: Form for users to report lost items.

**Authentication**: Requires login (`requireUser()`)

**Form Fields:**
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| Title | Text | Yes | Max 100 chars |
| Description | Textarea | Yes | Detailed description |
| Location | Text | Yes | Where item was lost |
| Contact | Email | Yes | Valid email format |
| Image | File | No | JPG, PNG, GIF |

**Form Processing Flow:**
```
1. User submits form
    ↓
2. ServerC validates required fields
    ↓
3. If image uploaded:
   - Generate unique filename (uniqid() + extension)
   - Move to ../ServerB/uploads/
    ↓
4. ServerC calls ServerA API: POST /add_item.php
   - user_id, title, description, type='lost', location, contact, image_filename
    ↓
5. ServerA inserts into database
    ↓
6. ServerA returns success/error
    ↓
7. ServerC displays message to user
```

**File Upload Implementation:**
```php
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $upload_dir = '../ServerB/uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_filename = uniqid() . '.' . $extension;
    $upload_path = $upload_dir . $image_filename;
    
    move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
}
```

**API Call:**
```php
$response = makeAPIRequest(SERVERA_URL . '/add_item.php', [
    'user_id' => getCurrentUserId(),
    'title' => $title,
    'description' => $description,
    'type' => 'lost',
    'location' => $location,
    'contact' => $contact,
    'image_filename' => $image_filename
], 'POST', ['return_json' => true]);
```

**Success Message:**
```
📢 Lost item reported successfully! Your listing is now live and people who find items can contact you directly.
```

**Tips Section:**
Provides guidance on:
- Being specific with details
- Including unique identifiers
- Acting quickly
- Providing accurate location

**Form Reset:**
On success, form fields are cleared to allow reporting another item.

---

### 4. Report Found Item - `report_found.php`

**Purpose**: Form for users to report found items.

**Functionality**: Nearly identical to `report_lost.php` with these differences:
- Title: "Report a Found Item" instead of "Report a Lost Item"
- Form description emphasizes helping return items
- API call uses `type='found'` instead of `type='lost'`
- Success message: "✅ Found item reported successfully!"
- Button text: "✅ Submit Found Item Report"
- Color scheme: Green accent (success) instead of red/warning

**API Call:**
```php
$response = makeAPIRequest(SERVERA_URL . '/add_item.php', [
    'user_id' => getCurrentUserId(),
    'title' => $title,
    'description' => $description,
    'type' => 'found',  // Changed from 'lost'
    'location' => $location,
    'contact' => $contact,
    'image_filename' => $image_filename
], 'POST', ['return_json' => true]);
```

**Tips Section:**
Focuses on:
- Photographing the found item
- Noting exact location
- Keeping item safe
- Responding to inquiries

---

### 5. User Dashboard - `user_dashboard.php`

**Purpose**: Personal dashboard for logged-in users.

**Authentication**: Requires login (`requireUser()`)

**Key Features:**
- Display user's posted items (lost and found)
- Statistics (total, lost count, found count)
- Edit and delete item functionality
- Admin access notification (if user is admin)

**API Call:**
```php
// Get all items posted by current user
$api_response = makeAPIRequest(SERVERA_URL . '/get_user_items.php', [
    'user_id' => getCurrentUserId()
], 'GET', ['return_json' => true]);

$userItems = $api_response['items'] ?? [];
$stats = $api_response['stats'] ?? [];
```

**Statistics Cards:**
- Total items posted
- Lost items posted (red)
- Found items posted (green)

**Item Actions:**
Each item card has two buttons:
1. **Edit Button** (✏️ Edit): Links to `edit_item.php?id=[item_id]`
2. **Delete Button** (🗑️ Delete): Confirms before deletion

**Delete Functionality:**
```php
if (isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];
    $current_user_id = getCurrentUserId();
    
    // Verify ownership via ServerA API
    $item = makeAPIRequest(SERVERA_URL . '/get_item.php', 
        ['item_id' => $item_id], 'GET', ['return_json' => true]);
    
    if ($item['item']['user_id'] != $current_user_id) {
        $message = 'Access denied';
    } else {
        // Delete via ServerA API
        $response = makeAPIRequest(SERVERA_URL . '/delete_item.php', [
            'id' => $item_id,
            'user_id' => $current_user_id
        ], 'POST', ['return_json' => true]);
        
        // Clean up image file locally
        if ($item['item']['image']) {
            $image_path = '../ServerB/uploads/' . $item['item']['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
}
```

**Admin Notice:**
If user is admin, displays a banner:
```html
<div class="alert" style="background: #10b981;">
    ⭐ Admin Access: You have administrator privileges. 
    <a href="admin_dashboard.php">Go to Admin Dashboard →</a>
</div>
```

**Empty State:**
If no items posted:
```
You haven't posted any items yet.
[Report Lost Item] [Report Found Item]
```

---

### 6. Edit Item - `edit_item.php`

**Purpose**: Edit existing item details.

**Authentication**: Requires login + ownership verification

**Security:**
1. Get item from ServerA API
2. Verify `user_id` matches current user
3. If not owner: Redirect with error message

**Pre-population:**
Form fields pre-filled with current item data:
```php
<input type="text" name="title" 
       value="<?php echo htmlspecialchars($item['title']); ?>">
```

**Update Process:**
```php
$response = makeAPIRequest(SERVERA_URL . '/update_item.php', [
    'id' => $item_id,
    'user_id' => getCurrentUserId(),
    'title' => $title,
    'description' => $description,
    'type' => $type,
    'location' => $location,
    'contact' => $contact,
    'image_filename' => $new_image_filename ?? null
], 'POST', ['return_json' => true]);
```

**Image Handling:**
- If new image uploaded: Upload to ServerB, delete old image
- If no new image: Keep existing image filename
- API parameter: `image_filename` (optional)

**Current Image Display:**
```php
if ($item['image']) {
    echo '<img src="' . getImageUrl($item['image']) . '" 
          style="max-width: 200px; display: block; margin-top: 0.5rem;">';
}
```

---

### 7. User Login - `user_login.php`

**Purpose**: Authenticate existing users.

**Redirect**: If already logged in → `index.php`

**Form Fields:**
- Username (required)
- Password (required)

**Authentication Flow:**
```
1. User enters credentials
    ↓
2. ServerC calls ServerB: POST /verify_user.php
   - username, password
    ↓
3. ServerB queries database by username
    ↓
4. ServerB verifies password using password_verify()
    ↓
5. ServerB returns user data (id, username, email, is_admin)
    ↓
6. ServerC stores in session:
   - $_SESSION['user_id']
   - $_SESSION['username']
   - $_SESSION['user_email']
   - $_SESSION['is_admin']
    ↓
7. Redirect to user_dashboard.php
```

**API Call:**
```php
$response = makeAPIRequest(SERVERB_URL . '/verify_user.php', [
    'username' => $username,
    'password' => $password
], 'POST', ['return_json' => true]);

if (is_array($response) && isset($response['success']) && $response['success']) {
    $_SESSION['user_id'] = $response['user_id'];
    $_SESSION['username'] = $response['username'];
    $_SESSION['user_email'] = $response['email'];
    $_SESSION['is_admin'] = $response['is_admin'] ?? 0;
    
    header('Location: user_dashboard.php');
    exit();
}
```

**Success Message:**
If redirected from registration (`?registered=1`):
```
Registration successful! Please login with your credentials.
```

**Error Handling:**
- Empty fields: "Please fill all fields"
- Invalid credentials: Error from ServerB API
- Network error: "Login failed"

**Links:**
- "Don't have an account? Register here" → `user_register.php`
- "Back to Home" → `index.php`

---

### 8. User Registration - `user_register.php`

**Purpose**: Create new user accounts.

**Redirect**: If already logged in → `index.php`

**Form Fields:**
| Field | Validation |
|-------|------------|
| Username | Required, unique |
| Email | Required, valid email, unique |
| Password | Required, min 6 chars (recommended) |
| Confirm Password | Must match password |

**Client-Side Validation:**
```php
if ($password != $confirm_password) {
    $error = 'Passwords do not match';
}
```

**Registration Flow:**
```
1. User fills registration form
    ↓
2. ServerC validates password match
    ↓
3. ServerC calls ServerB: POST /register_user.php
   - username, email, password
    ↓
4. ServerB validates email format
    ↓
5. ServerB checks for existing username/email
    ↓
6. ServerB hashes password (bcrypt)
    ↓
7. ServerB inserts user into database
    ↓
8. ServerB returns user data
    ↓
9. ServerC logs user in automatically
   (stores data in session)
    ↓
10. Redirect to user_dashboard.php
```

**API Call:**
```php
$response = makeAPIRequest(SERVERB_URL . '/register_user.php', [
    'username' => $username,
    'email' => $email,
    'password' => $password
], 'POST');

$decoded = json_decode($response, true);

if ($decoded && isset($decoded['success']) && $decoded['success']) {
    $_SESSION['user_id'] = $decoded['user_id'];
    $_SESSION['username'] = $decoded['username'];
    $_SESSION['user_email'] = $decoded['email'];
    $_SESSION['is_admin'] = $decoded['is_admin'] ?? 0;
    
    header('Location: user_dashboard.php');
    exit();
}
```

**Auto-Login:**
Unlike login page, registration automatically logs the user in upon success.

**Password Requirements:**
HTML5 validation:
```html
<input type="password" minlength="6" 
       placeholder="At least 6 characters">
```

---

### 9. Admin Dashboard - `admin_dashboard.php`

**Purpose**: Comprehensive admin control panel.

**Authentication**: Requires admin privileges
```php
if (!isUserLoggedIn() || !isCurrentUserAdmin()) {
    header('Location: user_login.php');
    exit();
}
```

**Key Features:**
- System statistics overview
- View all users with admin toggle
- View and delete all items
- System information display

**API Calls:**
```php
// Get all items
$api_response = makeAPIRequest(SERVERA_URL . '/get_all_items.php', 
    [], 'GET', ['return_json' => true, 'timeout' => 3]);
$all_items = $api_response['items'] ?? [];

// Get all users
$api_response = makeAPIRequest(SERVERB_URL . '/get_all_users.php', 
    [], 'GET', ['return_json' => true, 'timeout' => 3]);
$users = $api_response['users'] ?? [];
$user_stats = $api_response['stats'] ?? [];
```

**Statistics Cards:**
- 👥 Total Users (admins + regular)
- 📦 Total Items (lost + found)
- 🔍 Lost Items count
- ✅ Found Items count

**Quick Actions:**
- 📦 Manage All Items
- 👥 Manage Users
- ➕ Add Lost Item
- ➕ Add Found Item

**All Items Management:**
Displays table with:
- Item thumbnail image
- Title with type badge (LOST/FOUND)
- Posted by username
- Creation date
- Delete button (with confirmation)

**Delete Item (Admin):**
```php
if ($action === 'delete_item') {
    $item_id = $_POST['item_id'];
    
    $api_response = makeAPIRequest(
        SERVERA_URL . '/delete_item.php',
        [
            'id' => $item_id,
            'is_admin' => 1  // Bypass ownership check
        ],
        'POST',
        ['return_json' => true]
    );
}
```

**User Management Table:**
Columns:
- ID
- Username
- Email
- Status (👑 Admin or 👤 User badge)
- Joined date
- Actions

**Toggle Admin Status:**
```php
if ($action === 'toggle_user_status') {
    $target_user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];  // 0 or 1
    
    $api_response = makeAPIRequest(
        SERVERB_URL . '/toggle_admin.php',
        [
            'user_id' => $target_user_id,
            'is_admin' => $new_status
        ],
        'POST',
        ['return_json' => true]
    );
}
```

**Protection:**
Cannot modify own admin status:
```php
if ($user['id'] != getCurrentUserId()) {
    // Show toggle button
} else {
    echo "Current User";
}
```

**System Information:**
- Server configuration (ServerA, ServerB, ServerC roles)
- Session status
- Current user role and ID
- PHP version
- Login timestamp

**Auto-Refresh:**
Commented out auto-refresh (can enable):
```javascript
// setInterval(function() {
//     location.reload();
// }, 30000); // 30 seconds
```

---

## 🎨 Frontend Design & Styling

### Style Architecture (`assets/style.css` - 2083 lines)

**Color System:**
```css
:root {
    /* Primary Colors */
    --primary: #2563eb;         /* Blue */
    --primary-dark: #1e40af;
    --primary-light: #3b82f6;
    
    /* Neutral Colors */
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    
    /* Accent Colors */
    --success: #10b981;         /* Green (found items) */
    --error: #ef4444;           /* Red (lost items) */
    --warning: #f59e0b;         /* Orange */
    
    /* Borders & Shadows */
    --border: #e2e8f0;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
```

**Typography:**
- **Font Family**: System fonts for native feel
  ```css
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 
               Roboto, 'Helvetica Neue', Arial, sans-serif;
  ```
- **Base Size**: 16px
- **Line Height**: 1.7 (comfortable reading)
- **Headings**: 700 weight, varied sizes

**Component Styles:**

**1. Header (Sticky Navigation)**
```css
header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}
```

**2. Hero Section (Gradient)**
```css
.hero {
    background: linear-gradient(135deg, 
                var(--primary) 0%, 
                var(--primary-dark) 100%);
    color: white;
    padding: 4rem 2rem;
    border-radius: 12px;
}
```

**3. Item Cards**
```css
.item-card {
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
}
```

**4. Item Type Badges**
```css
.item-type.lost {
    background: #ef4444;
    color: white;
}

.item-type.found {
    background: #10b981;
    color: white;
}
```

**5. Buttons**
```css
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}
```

**6. Form Elements**
```css
input, textarea, select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 6px;
    transition: all 0.2s ease;
}

input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
```

**Responsive Breakpoints:**
- **Desktop**: 1200px+ (full layout)
- **Tablet**: 768px - 1199px (adapted grid)
- **Mobile**: < 768px (stacked layout, hamburger menu)

**Mobile Menu:**
- Hamburger icon (3 bars)
- Slide-down menu animation
- Closes on link click or outside click

**Accessibility Features:**
- High contrast text (WCAG AA compliant)
- Focus indicators on interactive elements
- Semantic HTML structure
- ARIA labels on menu toggle

**Performance Optimizations:**
- CSS Custom Properties (CSS variables)
- Minimal use of expensive properties (backdrop-filter removed)
- Hardware-accelerated transforms
- Optimized box shadows

---

## 📜 Client-Side JavaScript (`assets/script.js`)

### Key Functions

**1. Form Validation**
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
    
    // Simple email validation
    if (contact && contact.value.indexOf('@') == -1) {
        alert('Please enter a valid email');
        return false;
    }
    
    return true;
}
```

**2. Mobile Menu Toggle**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.menu-toggle');
    var nav = document.querySelector('nav');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (nav.classList.contains('active') && 
                !nav.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                nav.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
    }
});
```

**3. Read More Functionality**
For long item descriptions:
```javascript
function initializeReadMore() {
    var descriptions = document.querySelectorAll('.item-description');
    
    descriptions.forEach(function(desc) {
        var fullText = desc.textContent.trim();
        
        if (fullText.length > 200) {
            desc.classList.add('truncated');
            desc.setAttribute('data-full-text', fullText);
            
            var readMoreBtn = document.createElement('span');
            readMoreBtn.className = 'read-more-btn';
            readMoreBtn.textContent = 'Read more...';
            readMoreBtn.onclick = function() {
                toggleDescription(desc, readMoreBtn);
            };
            
            desc.parentNode.insertBefore(readMoreBtn, desc.nextSibling);
        }
    });
}

function toggleDescription(descElement, btnElement) {
    if (descElement.classList.contains('truncated')) {
        descElement.classList.remove('truncated');
        btnElement.textContent = 'Show less';
    } else {
        descElement.classList.add('truncated');
        btnElement.textContent = 'Read more...';
    }
}
```

**CSS for Truncation:**
```css
.item-description.truncated {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
```

---

## 🔒 Session Management & Authentication

### Session Initialization
```php
// In config.php
session_start();
```

### Session Variables
| Variable | Type | Description |
|----------|------|-------------|
| `$_SESSION['user_id']` | int | User's database ID |
| `$_SESSION['username']` | string | User's username |
| `$_SESSION['user_email']` | string | User's email |
| `$_SESSION['is_admin']` | int | Admin flag (0 or 1) |

### Helper Functions
```php
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

function isCurrentUserAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: user_login.php');
        exit();
    }
}

function logoutUser() {
    session_destroy();
    header('Location: index.php');
    exit();
}
```

### Login Flow
```
POST to verify_user.php API
    ↓
Receive user data from ServerB
    ↓
Store in $_SESSION
    ↓
Redirect to dashboard
```

### Logout Flow
```
User clicks logout link
    ↓
Call logoutUser()
    ↓
session_destroy()
    ↓
Redirect to homepage
```

### Session Security
- **HTTP-only cookies**: Configured in php.ini
- **Session regeneration**: On login (future enhancement)
- **Session timeout**: Default PHP timeout (24 minutes)
- **No session fixation**: New session ID on login (recommended)

---

## 📡 API Client Class (`api_client.php`)

### Purpose
Object-oriented alternative to `makeAPIRequest()` function.

### Class Structure
```php
class APIClient {
    private $base_url;
    private $timeout = 30;
    private $connect_timeout = 10;
    private $max_retries = 3;
    private $retry_delay = 1;
    private $verify_ssl = false;
    
    public function __construct($base_url, $options = []) { ... }
    public function post($endpoint, $data = [], $return_json = false) { ... }
    public function get($endpoint, $params = [], $return_json = false) { ... }
    public function delete($endpoint, $data = [], $return_json = false) { ... }
    public function put($endpoint, $data = [], $return_json = false) { ... }
    
    private function request($method, $endpoint, $data = [], $return_json = false) { ... }
    private function executeRequest($method, $url, $data = [], $return_json = false) { ... }
    
    public function getLastHttpCode() { ... }
    public function getLastError() { ... }
    public function isSuccess() { ... }
    public function testConnection() { ... }
}
```

### Usage Example
```php
// Create client for ServerA
$client = new APIClient(SERVERA_API_URL, [
    'timeout' => 15,
    'max_retries' => 5
]);

// Make GET request
$items = $client->get('/get_all_items.php', ['type' => 'lost'], true);

// Check status
if ($client->isSuccess()) {
    echo "Success! HTTP Code: " . $client->getLastHttpCode();
} else {
    echo "Error: " . $client->getLastError();
}
```

### Features
✅ **Chainable methods** (post, get, delete, put)  
✅ **Automatic retry logic**  
✅ **State tracking** (last response, HTTP code, error)  
✅ **Connection testing**  
✅ **User-Agent identification** (`LostFound-APIClient/2.0`)  

### Advantages over Function
- Object state preservation
- Reusable configuration
- Cleaner syntax for multiple requests
- Better for testing (can mock)

---

## 🌐 WAMP Deployment (Windows)

### Server Configuration
- **OS**: Windows 23H2
- **Web Server**: Apache 2.4.62
- **PHP Version**: 8.3.14
- **Database**: MySQL (accessed remotely)

### Apache Configuration
For external access, add to `httpd-vhosts.conf` or `.htaccess`:
```apache
<Directory "c:/xampp/htdocs/Lostnfound/ServerC">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### PHP Configuration
Recommended `php.ini` settings:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
session.gc_maxlifetime = 1440
```

### Network Access
- **Internal Network**: `http://172.24.14.184/Lostnfound/ServerC`
- **External Access**: Requires port forwarding or public IP
- **Firewall**: Allow TCP port 80 (HTTP) or 443 (HTTPS)

---

## 🔗 Integration Summary

### ServerC → ServerA (Item Operations)
| Operation | Endpoint | Method |
|-----------|----------|--------|
| Get all items | `/get_all_items.php` | GET |
| Get single item | `/get_item.php` | GET |
| Get user items | `/get_user_items.php` | GET |
| Add item | `/add_item.php` | POST |
| Update item | `/update_item.php` | POST |
| Delete item | `/delete_item.php` | POST |

### ServerC → ServerB (User Operations)
| Operation | Endpoint | Method |
|-----------|----------|--------|
| Register user | `/register_user.php` | POST |
| Login user | `/verify_user.php` | POST |
| Get all users | `/get_all_users.php` | GET |
| Toggle admin | `/toggle_admin.php` | POST |
| Get user items (proxy) | `/get_user_items.php` | GET |

---

## 🐛 Common Issues & Solutions

### Issue: "ServerC cannot connect directly to the database"
**Cause**: Code trying to call `connectDB()`  
**Solution**: This is by design. Use API calls instead.

### Issue: "Failed to upload image"
**Cause**: Uploads directory not writable  
**Solution**: 
```bash
# On Windows, right-click folder → Properties → Security
# Grant "Full Control" to IIS_IUSRS or authenticated users
```

### Issue: "API timeout"
**Cause**: ServerA/B not responding within timeout  
**Solution**: Increase timeout in API call options:
```php
makeAPIRequest($url, $data, 'GET', ['timeout' => 60]);
```

### Issue: "Session not persisting"
**Cause**: Browser blocking cookies  
**Solution**: 
- Check browser privacy settings
- Ensure `session_start()` is called before any output
- Verify cookies are enabled

### Issue: "Image not displaying"
**Cause**: Incorrect path to ServerB uploads  
**Solution**: Verify path in `deployment_config.php`:
```php
define('UPLOADS_BASE_URL', 'http://172.24.194.6/Lostnfound/ServerB/uploads/');
```

---

## 📝 Best Practices

### When Making API Calls
✅ Always use `return_json => true` for API responses  
✅ Handle both success and error cases  
✅ Log errors for debugging  
✅ Set appropriate timeouts  
✅ Use try-catch for network operations  

### When Handling Forms
✅ Validate on both client and server side  
✅ Sanitize output with `htmlspecialchars()`  
✅ Clear form after successful submission  
✅ Provide clear error messages  
✅ Use CSRF tokens (future enhancement)  

### When Managing Sessions
✅ Call `session_start()` early  
✅ Don't store sensitive data in sessions  
✅ Regenerate session ID on login  
✅ Clear session on logout  
✅ Implement session timeout  

### When Displaying User Data
✅ Always escape output: `htmlspecialchars($data)`  
✅ Validate email addresses  
✅ Don't display passwords (even hashed)  
✅ Implement pagination for large datasets  
✅ Show loading states during API calls  

---

**ServerC provides a beautiful, responsive, and secure user interface that seamlessly integrates with backend services through robust API communication.**
