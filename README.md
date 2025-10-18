# 🎓 University Lost and Found Portal - System Architecture & Analysis

## 📊 Executive Summary

A full-stack web application built with PHP and MySQL that facilitates the reporting and recovery of lost items within a university campus. The system supports both guest and registered user interactions, with an administrative backend for moderation and user management.

**Technology Stack:**
- **Backend:** PHP 7.4+ (Procedural MySQLi)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Server:** Apache 2.4+ (XAMPP/WAMP)
- **Architecture:** Server-Side Rendered Multi-Page Application (MPA)

---

## 🏛️ System Architecture

### Architecture Pattern
**Model-View Pattern** with procedural PHP (not MVC framework-based)

```
┌─────────────────────────────────────────┐
│          CLIENT (Browser)               │
│  HTML/CSS/JavaScript                     │
└─────────────┬────────────────────────────┘
                │
                │ HTTP Requests
                │
┌─────────────┴───────────────────────────┐
│       PHP APPLICATION LAYER             │
│  ┌───────────────────────────────────┐  │
│  │   Configuration Layer          │  │
│  │   - db.php                      │  │
│  │   - admin_config.php            │  │
│  │   - user_config.php             │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │   Page Controllers             │  │
│  │   - index.php                   │  │
│  │   - items.php                   │  │
│  │   - report_lost.php             │  │
│  │   - report_found.php            │  │
│  │   - user_login.php              │  │
│  │   - user_register.php           │  │
│  │   - user_dashboard.php          │  │
│  │   - edit_item.php               │  │
│  │   - admin_login.php             │  │
│  │   - admin_dashboard.php         │  │
│  └───────────────────────────────────┘  │
└─────────────┬───────────────────────────┘
                │
                │ MySQLi Queries
                │
┌─────────────┴───────────────────────────┐
│         DATABASE LAYER                  │
│  MySQL Database: lostfound_db          │
│  - users table                         │
│  - items table                         │
│  - deletion_requests table             │
└───────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│      FILE STORAGE                      │
│  uploads/ directory                    │
│  (Item images)                         │
└─────────────────────────────────────────┘
```

### Component Architecture

#### 1. **Configuration Layer**
- `db.php` - Database connection singleton
- `admin_config.php` - Admin authentication functions
- `user_config.php` - User authentication functions

#### 2. **Page Controllers** (Each file handles both logic and view)
- Public pages: `index.php`, `items.php`, `report_lost.php`, `report_found.php`
- User pages: `user_login.php`, `user_register.php`, `user_dashboard.php`, `edit_item.php`
- Admin pages: `admin_login.php`, `admin_dashboard.php`
- Setup: `setup.php`

#### 3. **Frontend Assets**
- `style.css` - Professional, clean UI styling
- `script.js` - Client-side form validation

#### 4. **Data Storage**
- MySQL database with 3 tables
- File system for uploaded images (`uploads/` directory)

---

## ⚙️ Core Features & Operations

### 👥 User Management System

#### 1. **User Registration**
**File:** `user_register.php`

**Process Flow:**
1. User submits registration form (username, email, password, confirm password)
2. System validates:
   - All fields are filled
   - Email format is valid
   - Password is at least 6 characters
   - Passwords match
   - Username/email doesn't already exist
3. Password is hashed using `password_hash(PASSWORD_DEFAULT)`
4. User record inserted into `users` table
5. Redirect to login page with success message

**Security Features:**
- Password hashing with bcrypt (via `PASSWORD_DEFAULT`)
- Unique constraint on username and email
- SQL injection prevention via `mysqli_real_escape_string()`
- Input validation

#### 2. **User Login**
**File:** `user_login.php`

**Process Flow:**
1. User submits login credentials (username, password)
2. System queries database for matching username
3. Password verified using `password_verify()`
4. On success:
   - Session variables set: `user_id`, `username`, `user_email`
   - Redirect to user dashboard
5. On failure: Display error message

**Session Management:**
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['user_email'] = $user['email'];
```

#### 3. **User Dashboard**
**File:** `user_dashboard.php`

**Features:**
- View personal statistics (total items, lost count, found count)
- List all items posted by the user
- Edit own items (redirects to `edit_item.php`)
- Delete own items directly (no admin approval needed)
- Logout functionality

**Authorization:**
- Protected by `requireUser()` function
- Users can only view/edit/delete their own items

#### 4. **Edit Item**
**File:** `edit_item.php`

**Features:**
- Modify item details (title, description, location, contact)
- Replace item image
- Ownership verification (user can only edit their own items)

**Process:**
1. Verify item ownership via `user_id` match
2. Display pre-filled form with current data
3. On submit: Update database record
4. Handle image replacement (delete old, upload new)

---

### 📝 Item Reporting System

#### 1. **Report Lost Item**
**File:** `report_lost.php`

**Process Flow:**
1. User fills out form:
   - Title (required)
   - Description (required)
   - Location where lost (required)
   - Contact email (required)
   - Image (optional)
2. Form validation (client-side via `script.js` and server-side)
3. Image upload handling:
   - Check file type (jpg, jpeg, png, gif only)
   - Generate unique filename using `uniqid()`
   - Move to `uploads/` directory
4. Data sanitization via `mysqli_real_escape_string()`
5. Insert into `items` table with `type='lost'`
6. Display success message

**Guest vs Registered Users:**
- Guests: Can post items with `user_id = NULL`
- Registered users: Items linked to their account via `user_id`

#### 2. **Report Found Item**
**File:** `report_found.php`

**Identical to Report Lost**, except:
- Item type set to `type='found'`
- Different UI messaging (privacy guidelines emphasized)
- Tips about protecting personal information on found items

**Privacy Features:**
- Guidelines warn against sharing personal info from found items
- Recommendations for safe handoff locations

---

### 🔍 Search & Browse System

#### **View All Items**
**File:** `items.php`

**Features:**
1. **Dynamic Filtering:**
   - Filter by type: All / Lost / Found
   - Real-time search across title, description, location
   
2. **Search Implementation:**
   ```php
   $sql = "SELECT * FROM items WHERE 1=1";
   
   // Add type filter
   if ($filter != 'all') {
       $sql .= " AND type = '$filter'";
   }
   
   // Add search filter
   if ($search != '') {
       $sql .= " AND (title LIKE '%$search%' 
                     OR description LIKE '%$search%' 
                     OR location LIKE '%$search%')";
   }
   
   $sql .= " ORDER BY created_at DESC";
   ```

3. **Display Features:**
   - Grid layout of item cards
   - Color-coded type badges (red for lost, green for found)
   - Image zoom modal on click
   - Statistics summary (total, lost, found counts)
   - Email contact links

4. **Client-Side Enhancements:**
   - JavaScript-based real-time filtering (no page reload)
   - Live count updates
   - Escape key closes image modal

---

### 🛡️ Admin Management System

#### 1. **Admin Authentication**
**File:** `admin_config.php`, `admin_login.php`

**Credentials:**
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'isaacK@12345');
```

**Session-Based Auth:**
- `$_SESSION['admin_logged_in'] = true` on successful login
- `requireAdmin()` function protects admin pages
- Simple username/password comparison (not database-stored)

**Security Note:** This is a hardcoded authentication system. In production, admin credentials should be:
- Stored in database with hashed passwords
- Support multiple admin accounts
- Include role-based permissions

#### 2. **Admin Dashboard**
**File:** `admin_dashboard.php`

**Features:**

**A. Statistics Display:**
- Total items count
- Lost items count
- Found items count

**B. Item Management:**
- View recent 10 items
- Delete any item directly
- View item details
- Image deletion on item removal

**C. User Management:**
- View all registered users
- See user statistics (items posted, join date)
- Delete users (cascades to their items)
- User activity monitoring

**D. Deletion Request Management:**
- View pending deletion requests from users
- Approve requests (deletes item)
- Reject requests (keeps item, marks request rejected)
- See requester information

**Operations:**

1. **Delete Item:**
   ```php
   // Get image filename
   $item = mysqli_fetch_assoc($result);
   
   // Delete physical image file
   if ($item['image'] && file_exists('uploads/' . $item['image'])) {
       unlink('uploads/' . $item['image']);
   }
   
   // Delete database record
   DELETE FROM items WHERE id = $itemId;
   ```

2. **Delete User:**
   - Cascades to all user's items and deletion requests
   - Foreign key constraints handle cleanup

3. **Approve Deletion Request:**
   - Deletes the item
   - Request auto-deleted via CASCADE constraint

---

### 📂 File Upload System

**Location:** Used in `report_lost.php`, `report_found.php`, `edit_item.php`

**Process:**

1. **Validation:**
   ```php
   $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
   $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
   
   if (in_array($imageFileType, $allowedTypes)) {
       // Proceed with upload
   }
   ```

2. **Unique Naming:**
   ```php
   $imageName = uniqid() . '.' . $imageFileType;
   // Example: 64f7a2b3c9d8e.jpg
   ```

3. **Storage:**
   ```php
   move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName);
   ```

4. **Database Reference:**
   - Only filename stored in database
   - Actual file in `uploads/` directory

5. **Cleanup on Deletion:**
   - Physical file deleted when item removed
   - Prevents orphaned files

**Security Considerations:**
- File type whitelist (only images)
- Unique filenames prevent overwrites
- No execution permissions on uploads directory recommended

---

### 🔑 Authentication & Authorization

#### **User Authentication**
**File:** `user_config.php`

**Functions:**
- `isUserLoggedIn()` - Check if user session exists
- `getCurrentUserId()` - Get logged-in user's ID
- `getCurrentUsername()` - Get logged-in user's username
- `getCurrentUserEmail()` - Get logged-in user's email
- `registerUser($conn, $username, $email, $password)` - Create new account
- `loginUser($conn, $username, $password)` - Authenticate user
- `logoutUser()` - Destroy session and redirect
- `requireUser()` - Protect pages (redirect if not logged in)

**Session Variables:**
```php
$_SESSION['user_id']     // User's database ID
$_SESSION['username']    // Display name
$_SESSION['user_email']  // Email address
```

#### **Admin Authentication**
**File:** `admin_config.php`

**Functions:**
- `isAdminLoggedIn()` - Check admin session
- `authenticateAdmin($username, $password)` - Validate credentials
- `logoutAdmin()` - End admin session
- `requireAdmin()` - Protect admin pages

**Session Variables:**
```php
$_SESSION['admin_logged_in'] = true  // Admin authentication flag
```

---

### 📦 Database Setup System

**File:** `setup.php`

**Features:**
1. **Automated Database Creation:**
   ```sql
   CREATE DATABASE IF NOT EXISTS lostfound_db;
   ```

2. **Table Creation:**
   - Creates `users` table
   - Creates `items` table
   - Creates `deletion_requests` table
   - Sets up foreign key relationships

3. **Sample Data Insertion:**
   - Optional checkbox to include test data
   - Inserts 4 sample items (2 lost, 2 found)

4. **Directory Setup:**
   - Creates `uploads/` directory if missing
   - Sets permissions to 0755

5. **System Information Display:**
   - PHP version
   - Server software
   - Upload size limits
   - Post size limits

**Smart Detection:**
- Checks if database already exists
- Prevents duplicate setup
- Shows appropriate messages

---

## 📦 File Structure & Responsibilities

```
lostfound/
├── README.md                    # This documentation file
│
├── 🔧 CONFIGURATION FILES
│   ├── db.php                   # Database connection management
│   │                            # - MySQLi connection setup
│   │                            # - Character encoding (UTF-8)
│   │                            # - Connection error handling
│   │
│   ├── admin_config.php         # Admin authentication system
│   │                            # - Hardcoded credentials
│   │                            # - Session management functions
│   │                            # - isAdminLoggedIn()
│   │                            # - authenticateAdmin()
│   │                            # - logoutAdmin()
│   │                            # - requireAdmin()
│   │
│   └── user_config.php          # User authentication system
│                                # - Database-based user auth
│                                # - Password hashing/verification
│                                # - registerUser()
│                                # - loginUser()
│                                # - logoutUser()
│                                # - requireUser()
│                                # - Session helper functions
│
├── 🌐 PUBLIC PAGES
│   ├── index.php                # Homepage / Landing page
│   │                            # - Portal statistics display
│   │                            # - Recent 6 items showcase
│   │                            # - "How it works" information
│   │                            # - Quick action buttons
│   │                            # - SQL: Aggregate COUNT queries
│   │
│   ├── items.php                # Browse all items page
│   │                            # - Dynamic filtering (type: all/lost/found)
│   │                            # - Search functionality (title/desc/location)
│   │                            # - Grid display of item cards
│   │                            # - Image modal viewer
│   │                            # - Real-time client-side filtering (JS)
│   │                            # - SQL: Dynamic WHERE clause building
│   │
│   ├── report_lost.php          # Report lost item form
│   │                            # - Multi-field form (title/desc/location/contact/image)
│   │                            # - Image upload handling
│   │                            # - Form validation
│   │                            # - Recent lost items display
│   │                            # - SQL: INSERT with type='lost'
│   │
│   └── report_found.php         # Report found item form
│                                # - Same as report_lost.php
│                                # - Different UI messaging
│                                # - Privacy protection guidelines
│                                # - SQL: INSERT with type='found'
│
├── 👤 USER PAGES (Authentication Required)
│   ├── user_login.php           # User login form
│   │                            # - Username/password authentication
│   │                            # - Session creation on success
│   │                            # - Error message display
│   │                            # - Link to registration
│   │
│   ├── user_register.php        # New user registration
│   │                            # - Account creation form
│   │                            # - Password confirmation
│   │                            # - Email validation
│   │                            # - Duplicate username/email check
│   │                            # - Password hashing
│   │
│   ├── user_dashboard.php       # User control panel
│   │                            # - Personal statistics
│   │                            # - List of user's items
│   │                            # - Edit/Delete buttons for each item
│   │                            # - SQL: WHERE user_id filtering
│   │
│   └── edit_item.php            # Edit existing item
│                                # - Pre-filled form with current data
│                                # - Ownership verification
│                                # - Image replacement capability
│                                # - SQL: UPDATE query
│
├── 🛡️ ADMIN PAGES (Admin Authentication Required)
│   ├── admin_login.php          # Admin login portal
│   │                            # - Simple username/password form
│   │                            # - Default credentials display (dev mode)
│   │                            # - Session-based authentication
│   │
│   └── admin_dashboard.php      # Admin control panel
│                                # - System statistics
│                                # - Recent items management
│                                # - Item deletion (any item)
│                                # - User management
│                                # - Deletion request approval/rejection
│                                # - SQL: Complex JOIN queries
│
├── ⚙️ SETUP & UTILITIES
│   └── setup.php                # Database initialization
│                                # - Database creation
│                                # - Table creation (users, items, deletion_requests)
│                                # - Foreign key setup
│                                # - Sample data insertion (optional)
│                                # - uploads/ directory creation
│                                # - System info display
│
├── 🎨 FRONTEND ASSETS
│   ├── style.css                # Main stylesheet
│   │                            # - CSS Custom Properties (variables)
│   │                            # - Responsive grid layouts
│   │                            # - Professional color scheme
│   │                            # - Button styles and states
│   │                            # - Form styling
│   │                            # - Card components
│   │                            # - Media queries (mobile responsive)
│   │
│   └── script.js                # Client-side JavaScript
│                                # - Form validation (validateForm())
│                                # - Email format checking
│                                # - Required field validation
│                                # - Alert messages
│
└── 📸 UPLOADS DIRECTORY
    └── uploads/                 # User-uploaded images
                                 # - Created by setup.php
                                 # - Permissions: 0755
                                 # - Unique filenames (uniqid())
                                 # - File types: jpg, jpeg, png, gif
```

### Key File Relationships

```
┌────────────────────┐
│   ALL PHP PAGES      │
└────────┬───────────┘
          │
          ├────────> require_once 'db.php'
          │
          ├────────> require_once 'user_config.php' (user pages)
          │
          └────────> require_once 'admin_config.php' (admin pages)

┌────────────────────┐
│   ALL HTML PAGES     │
└────────┬───────────┘
          │
          ├────────> <link rel="stylesheet" href="style.css">
          │
          └────────> <script src="script.js"></script>

┌────────────────────┐
│  FORM PAGES         │
└────────┬───────────┘
          │
          └────────> onsubmit="return validateForm();" (script.js)
```

---

## 🗄️ Database Schema

### Database: `lostfound_db`

#### Table 1: `users`
**Purpose:** Stores registered user accounts

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| `username` | VARCHAR(50) | NOT NULL, UNIQUE | User's login name |
| `email` | VARCHAR(100) | NOT NULL, UNIQUE | User's email address |
| `password` | VARCHAR(255) | NOT NULL | Hashed password (using `password_hash()`) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation time |

**Relationships:**
- One user can have many items (1:N)
- One user can have many deletion requests (1:N)

#### Table 2: `items`
**Purpose:** Stores all lost and found item reports

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique item identifier |
| `user_id` | INT | FOREIGN KEY, NULL allowed | Owner of the post (NULL for guest posts) |
| `title` | VARCHAR(100) | NOT NULL | Item name/title |
| `description` | TEXT | NOT NULL | Detailed item description |
| `type` | ENUM('lost', 'found') | NOT NULL | Item status type |
| `location` | VARCHAR(100) | NOT NULL | Where item was lost/found |
| `contact` | VARCHAR(100) | NOT NULL | Contact email |
| `image` | VARCHAR(255) | NULL | Uploaded image filename |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Post creation time |

**Relationships:**
- Foreign key to `users.id` with CASCADE delete
- When user is deleted, all their items are deleted

#### Table 3: `deletion_requests`
**Purpose:** User-initiated deletion requests pending admin approval

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique request identifier |
| `item_id` | INT | FOREIGN KEY, NOT NULL | Item to be deleted |
| `user_id` | INT | FOREIGN KEY, NOT NULL | User requesting deletion |
| `status` | ENUM('pending', 'approved', 'rejected') | DEFAULT 'pending' | Request status |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Request creation time |

**Relationships:**
- Foreign key to `items.id` with CASCADE delete
- Foreign key to `users.id` with CASCADE delete

### Entity Relationship Diagram

```
┌─────────────────────┐
│      USERS           │
│─────────────────────│
│ id (PK)             │
│ username (UNIQUE)   │
│ email (UNIQUE)      │
│ password            │
│ created_at          │
└─────────┬────────────┘
          │
          │ 1:N
          │
┌─────────┴──────────────────────────┐
│           ITEMS                        │
│─────────────────────────────────────│
│ id (PK)                              │
│ user_id (FK → users.id, NULLABLE)  │
│ title                                │
│ description                          │
│ type (lost/found)                    │
│ location                             │
│ contact                              │
│ image                                │
│ created_at                           │
└──────────────┬──────────────────────┘
               │
               │ 1:N
               │
┌──────────────┴────────────────────────────┐
│       DELETION_REQUESTS                     │
│────────────────────────────────────────────│
│ id (PK)                                   │
│ item_id (FK → items.id)                 │
│ user_id (FK → users.id)                 │
│ status (pending/approved/rejected)        │
│ created_at                                │
└────────────────────────────────────────────┘
```

---

**Database**: `lostfound_db`

**Table**: `items`
- `id` - Unique identifier (AUTO_INCREMENT)
- `title` - Item name (VARCHAR 100)
- `description` - Detailed description (TEXT)
- `type` - 'lost' or 'found' (ENUM)
- `location` - Where item was lost/found (VARCHAR 100)
- `contact` - Email address (VARCHAR 100)
- `image` - Image filename (VARCHAR 255)
- `created_at` - Timestamp (TIMESTAMP)

## 🚀 Installation & Setup Guide

### Prerequisites

**Required Software:**
1. **XAMPP** (recommended) or **WAMP**
   - Download: https://www.apachefriends.org/
   - Includes: Apache, MySQL, PHP, phpMyAdmin
   - Version: PHP 7.4 or higher

2. **Web Browser**
   - Chrome, Firefox, Safari, or Edge
   - JavaScript enabled

**System Requirements:**
- Windows, macOS, or Linux
- 2GB RAM minimum
- 500MB free disk space
- Internet connection (for setup only)

### Step-by-Step Installation

#### **Step 1: Install XAMPP/WAMP**

```bash
# Windows (XAMPP)
1. Download XAMPP from https://www.apachefriends.org/
2. Run installer (xampp-windows-x64-8.x.x-installer.exe)
3. Install to C:\xampp
4. Launch XAMPP Control Panel
5. Start Apache and MySQL services
```

```bash
# Windows (WAMP)
1. Download WAMP from https://www.wampserver.com/
2. Run installer
3. Install to C:\wamp64
4. Launch WAMP
5. Ensure icon is green (all services running)
```

#### **Step 2: Copy Project Files**

```bash
# For XAMPP
Copy lostfound folder to: C:\xampp\htdocs\lostfound

# For WAMP
Copy lostfound folder to: C:\wamp64\www\lostfound
```

**File Structure After Copy:**
```
C:\xampp\htdocs\lostfound\
├── index.php
├── db.php
├── setup.php
├── ... (all other files)
└── uploads/  (will be created by setup)
```

#### **Step 3: Configure Database Connection**

Open `db.php` and verify/update settings:

```php
$host = 'localhost';        // Usually 'localhost'
$username = 'root';         // Default for XAMPP/WAMP
$password = '';             // Empty for XAMPP (default)
                            // 'kpet' for current WAMP setup
$database = 'lostfound_db'; // Database name
```

**WAMP Users:** If your MySQL password is not empty, update it:
```php
$password = 'your_mysql_password';
```

#### **Step 4: Run Database Setup**

1. **Open browser and navigate to:**
   ```
   http://localhost/lostfound/setup.php
   ```

2. **Setup page will display:**
   - System information (PHP version, upload limits)
   - Database creation options
   - Sample data checkbox

3. **Check "Include sample data"** (recommended for testing)

4. **Click "Setup Database" button**

5. **Verify success messages:**
   ```
   ✓ Database 'lostfound_db' created successfully!
   ✓ Table 'users' created successfully!
   ✓ Table 'items' created successfully!
   ✓ Table 'deletion_requests' created successfully!
   ✓ Sample data inserted successfully!
   ✓ Uploads directory created successfully!
   ```

6. **Click "Go to Portal" button**

#### **Step 5: Verify Installation**

**Test Public Access:**
```
http://localhost/lostfound/index.php  → Should show homepage with stats
http://localhost/lostfound/items.php  → Should show items (if sample data)
```

**Test User Registration:**
```
1. Go to http://localhost/lostfound/user_register.php
2. Create account: username=testuser, email=test@test.com, password=test123
3. Should redirect to login page
4. Login with credentials
5. Should see user dashboard
```

**Test Admin Access:**
```
1. Go to http://localhost/lostfound/admin_login.php
2. Username: admin
3. Password: isaacK@12345
4. Should see admin dashboard with statistics
```

#### **Step 6: Configure File Uploads (Optional)**

If file uploads fail, check `php.ini`:

**XAMPP:** `C:\xampp\php\php.ini`
**WAMP:** `C:\wamp64\bin\php\php8.x.x\php.ini`

```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
```

Restart Apache after changes.

### Troubleshooting

#### **Problem: Database Connection Failed**

**Error:** "Connection failed: Access denied for user 'root'@'localhost'"

**Solution:**
1. Check MySQL service is running (XAMPP/WAMP control panel)
2. Verify username/password in `db.php`
3. Try default XAMPP password (empty string)
4. Reset MySQL password if needed

#### **Problem: Page Not Found (404)**

**Error:** "Object not found!"

**Solution:**
1. Verify Apache is running
2. Check file path: `http://localhost/lostfound/` (not `file:///`)
3. Ensure files are in correct directory (htdocs or www)
4. Check folder name matches URL

#### **Problem: Images Not Uploading**

**Error:** Silent failure or "Error uploading image"

**Solution:**
1. Check `uploads/` directory exists
2. Set folder permissions (Windows: Full Control)
3. Verify `php.ini` settings:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
4. Restart Apache
5. Check file type is allowed (jpg, jpeg, png, gif)

#### **Problem: Session Issues**

**Error:** Can't login, session not persisting

**Solution:**
1. Check `session.save_path` in `php.ini`
2. Ensure temp directory exists and is writable
3. Clear browser cookies
4. Try different browser
5. Check `session.auto_start = 0` in `php.ini`

#### **Problem: Setup Page Shows Errors**

**Error:** MySQL errors during table creation

**Solution:**
1. Drop existing database via phpMyAdmin:
   ```sql
   DROP DATABASE lostfound_db;
   ```
2. Re-run `setup.php`
3. Check MySQL user has CREATE privileges
4. Review error messages for specific issues

### Uninstallation

**To completely remove:**

1. **Delete Database:**
   ```sql
   -- Via phpMyAdmin or MySQL command line
   DROP DATABASE lostfound_db;
   ```

2. **Delete Files:**
   ```bash
   # Windows
   rmdir /s C:\xampp\htdocs\lostfound
   ```

3. **Clear Browser Data:**
   - Clear cookies for localhost
   - Clear cache

---

## 🔐 Admin Access

**Default Credentials** (Change in production!):
- Username: `admin`
- Password: `isaacK@12345`

Modify in `admin_config.php`:
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'isaacK@12345');
```

## 💻 Code Functionality

### 1. Database Connection (`db.php`)
```php
// Establishes MySQLi connection
$conn = mysqli_connect($host, $username, $password, $database);

// Checks connection success
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
```

### 2. Reporting Items (`report_lost.php`, `report_found.php`)

**Process Flow**:
1. User fills form with item details
2. Server validates required fields
3. Image uploaded to `uploads/` folder (if provided)
4. Data sanitized with `mysqli_real_escape_string()`
5. Record inserted into database
6. Success message displayed

**Key Code**:
```php
// Sanitize input to prevent SQL injection
$title = mysqli_real_escape_string($conn, $_POST['title']);

// Insert into database
$sql = "INSERT INTO items (title, description, type, location, contact, image) 
        VALUES ('$title', '$description', 'lost', '$location', '$contact', '$imageName')";
mysqli_query($conn, $sql);
```

### 3. Viewing Items (`items.php`)

**Dynamic Query Building**:
```php
// Base query
$sql = "SELECT * FROM items WHERE 1=1";

// Add filter for type (lost/found)
if ($filter != 'all') {
    $sql .= " AND type = '$filter'";
}

// Add search term
if ($search != '') {
    $sql .= " AND (title LIKE '%$search%' 
                   OR description LIKE '%$search%' 
                   OR location LIKE '%$search%')";
}

// Order by newest first
$sql .= " ORDER BY created_at DESC";
```

### 4. Admin Authentication (`admin_config.php`)

**Session-Based Authentication**:
```php
// Check if logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) 
           && $_SESSION['admin_logged_in'] == true;
}

// Authenticate credentials
function authenticateAdmin($username, $password) {
    if ($username == ADMIN_USERNAME && $password == ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

// Protect admin pages
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}
```

### 5. Admin Dashboard (`admin_dashboard.php`)

**Item Deletion**:
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
    
    // Delete from database
    $sql = "DELETE FROM items WHERE id = '$itemId'";
    mysqli_query($conn, $sql);
}
```

## 🔒 Security Implementation

### 1. **SQL Injection Prevention**

**Method:** Input Sanitization
```php
$title = mysqli_real_escape_string($conn, $_POST['title']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
```

**Applied to:**
- All form inputs before database queries
- Search parameters
- Filter values
- Item IDs from URLs

**Note:** Modern best practice would use prepared statements:
```php
// Current implementation (procedural MySQLi)
$title = mysqli_real_escape_string($conn, $_POST['title']);
$sql = "INSERT INTO items (title) VALUES ('$title')";

// Recommended alternative (prepared statements)
$stmt = $conn->prepare("INSERT INTO items (title) VALUES (?)");
$stmt->bind_param("s", $_POST['title']);
$stmt->execute();
```

### 2. **XSS (Cross-Site Scripting) Prevention**

**Method:** Output Escaping
```php
<?php echo htmlspecialchars($item['title']); ?>
```

**Applied to:**
- All user-generated content displayed in HTML
- Item titles, descriptions, locations
- Usernames and email addresses
- Error messages

### 3. **Password Security**

**User Passwords:**
- Hashed using `password_hash(PASSWORD_DEFAULT)` (bcrypt)
- Verified using `password_verify()`
- Never stored in plain text
- Minimum length: 6 characters (configurable)

**Example:**
```php
// Registration
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Login
if (password_verify($inputPassword, $storedHash)) {
    // Authentication successful
}
```

**Admin Credentials:**
- Currently hardcoded (development environment)
- Should be moved to environment variables or database in production

### 4. **File Upload Security**

**Restrictions:**
- File type whitelist (only jpg, jpeg, png, gif)
- File extension validation
- Unique filename generation (prevents overwrites)
- Stored outside document root recommended (not implemented)

**Current Implementation:**
```php
$allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
$imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

if (in_array($imageFileType, $allowedTypes)) {
    $imageName = uniqid() . '.' . $imageFileType;
    move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName);
}
```

**Security Gaps:**
- No file size validation in code (relies on php.ini)
- No MIME type verification
- No virus scanning
- Upload directory accessible via web (potential security risk)

### 5. **Session Security**

**Implementation:**
```php
session_start();  // At top of every page using sessions

// User authentication
$_SESSION['user_id'] = $userId;

// Admin authentication
$_SESSION['admin_logged_in'] = true;
```

**Security Measures:**
- Session-based authentication
- Sessions destroyed on logout
- Protected pages check session before rendering

**Missing Security Features:**
- No session regeneration on login (prevents session fixation)
- No session timeout
- No CSRF token protection
- No "remember me" functionality

### 6. **Authorization Controls**

**User-Level:**
- Users can only edit/delete their own items
- Ownership verified via `user_id` matching
- Protected pages redirect to login if not authenticated

**Example:**
```php
// Edit item - verify ownership
$sql = "SELECT * FROM items WHERE id = '$itemId' AND user_id = '$userId'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    header('Location: user_dashboard.php');  // Unauthorized
    exit();
}
```

**Admin-Level:**
- Full access to all items and users
- Can delete any item or user
- Separate authentication system

### 7. **Security Weaknesses & Recommendations**

| Vulnerability | Current State | Recommendation |
|---------------|---------------|----------------|
| SQL Injection | Partially protected (escape strings) | Use prepared statements |
| XSS | Protected (htmlspecialchars) | ✓ Adequate |
| CSRF | Not protected | Add CSRF tokens to forms |
| Session Fixation | Vulnerable | Regenerate session ID on login |
| File Upload | Basic validation | Add MIME check, size limit, virus scan |
| Password Storage | Secure (bcrypt) | ✓ Adequate |
| Admin Auth | Hardcoded | Move to database with hashing |
| HTTPS | Not enforced | Force HTTPS in production |
| Rate Limiting | None | Add login attempt limiting |
| Input Validation | Server-side only | ✓ Adequate |

---

## 📊 Key MySQLi Functions

| Function | Purpose | Example |
|----------|---------|----------|
| `mysqli_connect()` | Connect to database | `mysqli_connect($host, $user, $pass, $db)` |
| `mysqli_query()` | Execute SQL query | `mysqli_query($conn, $sql)` |
| `mysqli_fetch_assoc()` | Get one row as array | `mysqli_fetch_assoc($result)` |
| `mysqli_fetch_all()` | Get all rows as array | `mysqli_fetch_all($result, MYSQLI_ASSOC)` |
| `mysqli_num_rows()` | Count result rows | `mysqli_num_rows($result)` |
| `mysqli_real_escape_string()` | Sanitize input | `mysqli_real_escape_string($conn, $data)` |
| `mysqli_error()` | Get error message | `mysqli_error($conn)` |
| `mysqli_close()` | Close connection | `mysqli_close($conn)` |

## 🔄 Complete User Workflows

### Workflow 1: Guest User Reports Lost Item

```
1. User visits index.php (homepage)
   ↓
2. Clicks "Report Lost Item" button
   ↓
3. Redirected to report_lost.php
   ↓
4. Fills out form:
   - Item title
   - Description
   - Location where lost
   - Contact email
   - Optional image upload
   ↓
5. Clicks "Submit Lost Item Report"
   ↓
6. Server processes:
   - Validates all required fields
   - Uploads image (if provided)
   - Sanitizes input data
   - Inserts into items table with user_id=NULL
   ↓
7. Success message displayed
   ↓
8. Item now visible on items.php
   ↓
9. Other users can search and find the item
   ↓
10. Someone contacts via email
```

### Workflow 2: Registered User Posts and Manages Items

```
1. User visits user_register.php
   ↓
2. Creates account (username, email, password)
   ↓
3. Redirected to user_login.php with success message
   ↓
4. Logs in with credentials
   ↓
5. Redirected to user_dashboard.php
   ↓
6. Views their statistics and posted items
   ↓
7. Clicks "Report Found Item"
   ↓
8. Fills form on report_found.php
   ↓
9. Item inserted with user_id linking to their account
   ↓
10. Returns to dashboard, sees new item listed
   ↓
11. Later, clicks "Edit" on an item
   ↓
12. Modifies details on edit_item.php
   ↓
13. Updates saved, returns to dashboard
   ↓
14. When item is claimed, clicks "Delete"
   ↓
15. Confirms deletion
   ↓
16. Item removed from database and filesystem
```

### Workflow 3: Search and Contact Flow

```
1. User lost their phone
   ↓
2. Visits items.php
   ↓
3. Selects "Found Items" filter
   ↓
4. Types "iphone" in search box
   ↓
5. Real-time JavaScript filters results
   ↓
6. Finds matching item with image
   ↓
7. Clicks image to view enlarged
   ↓
8. Confirms it's their phone
   ↓
9. Clicks email link to contact finder
   ↓
10. Email client opens with finder's address
   ↓
11. Sends email describing phone to prove ownership
   ↓
12. Arranges meetup via email
   ↓
13. Item successfully returned!
```

### Workflow 4: Admin Moderation

```
1. Admin visits admin_login.php
   ↓
2. Enters credentials (admin / isaacK@12345)
   ↓
3. Authenticated, redirected to admin_dashboard.php
   ↓
4. Views dashboard with statistics:
   - Total items: 47
   - Lost items: 23
   - Found items: 24
   ↓
5. Scrolls through recent items list
   ↓
6. Finds inappropriate post (spam)
   ↓
7. Clicks "Delete" button
   ↓
8. Confirms deletion
   ↓
9. System:
   - Deletes image file from uploads/
   - Removes database record
   - Shows success message
   ↓
10. Checks "Pending Deletion Requests" section
   ↓
11. Sees user requested to delete their item
   ↓
12. Reviews request, clicks "Approve"
   ↓
13. Item deleted, request auto-removed (CASCADE)
   ↓
14. Checks "User Management" section
   ↓
15. Finds inactive user with no items
   ↓
16. Clicks "Delete User"
   ↓
17. User and all related data removed (CASCADE)
   ↓
18. Clicks "Logout" when done
```

---

### Reporting a Lost Item
1. Click "Report Lost" in navigation
2. Fill in:
   - Item title (e.g., "Black iPhone 13")
   - Detailed description
   - Last known location
   - Your email address
3. Upload image (optional but recommended)
4. Click "Submit Lost Item Report"

### Finding Lost Items
1. Go to "View Items" page
2. Use search box for keywords
3. Select filter (All/Lost/Found)
4. Click email to contact item owner/finder

### Admin Tasks
1. Login at `/admin_login.php`
2. View dashboard statistics
3. Delete inappropriate items
4. Monitor recent activity

## 🛠️ Troubleshooting

### Database Connection Error
- Check XAMPP/WAMP MySQL is running
- Verify credentials in `db.php`
- Ensure database exists (run `setup.php`)

### Images Not Uploading
- Check `uploads/` folder exists
- Set folder permissions to 755
- Verify `upload_max_filesize` in `php.ini`

### Admin Login Fails
- Check credentials in `admin_config.php`
- Clear browser cache
- Verify sessions enabled in PHP

## 📱 Browser Compatibility

- ✅ Chrome 80+
- ✅ Firefox 75+
- ✅ Safari 13+
- ✅ Edge 80+

## 🛠️ Technology Stack Deep Dive

### Backend Technologies

#### **PHP 7.4+**
- **Style:** Procedural (not object-oriented)
- **Database API:** MySQLi (MySQL Improved Extension)
- **Session Management:** Native PHP sessions
- **File Handling:** Native PHP file functions

**Key MySQLi Functions Used:**

| Function | Purpose | Usage Count |
|----------|---------|-------------|
| `mysqli_connect()` | Establish DB connection | 1 (in db.php) |
| `mysqli_query()` | Execute SQL queries | ~50+ uses |
| `mysqli_fetch_assoc()` | Fetch single row as array | ~30+ uses |
| `mysqli_fetch_all()` | Fetch all rows as array | ~15+ uses |
| `mysqli_num_rows()` | Count result rows | ~10+ uses |
| `mysqli_real_escape_string()` | Sanitize input | ~40+ uses |
| `mysqli_error()` | Get error messages | ~20+ uses |
| `mysqli_select_db()` | Select database | 1 (in setup.php) |
| `mysqli_set_charset()` | Set character encoding | 1 (in db.php) |

**Why Procedural MySQLi?**
- ✓ Beginner-friendly, easier to learn
- ✓ Direct and straightforward
- ✓ Less abstraction overhead
- ✗ Not using prepared statements (security concern)
- ✗ Repetitive code patterns
- ✗ Harder to maintain at scale

### Frontend Technologies

#### **HTML5**
**Features Used:**
- Semantic elements (`<header>`, `<main>`, `<footer>`, `<nav>`)
- Form elements (`<input>`, `<textarea>`, `<select>`)
- File input for image uploads
- Email input type for validation

#### **CSS3**
**Architecture:**
- CSS Custom Properties (CSS Variables)
- Flexbox for layouts
- Grid for item cards
- Media queries for responsiveness
- No CSS frameworks (vanilla CSS)

**Design System:**
```css
:root {
    --primary: #2563eb;
    --success: #10b981;
    --error: #ef4444;
    --bg-primary: #ffffff;
    --text-primary: #1e293b;
    /* ... more variables */
}
```

**Responsive Breakpoints:**
- Desktop: > 768px
- Tablet: 768px
- Mobile: 480px

#### **JavaScript (Vanilla)**
**Functionality:**
- Form validation before submission
- Real-time search filtering (on items.php)
- Image modal viewer
- Event listeners (keyboard, click)

**No frameworks/libraries used:**
- No jQuery
- No React/Vue/Angular
- Pure vanilla JavaScript

### Database Technology

#### **MySQL 5.7+**
**Features Used:**
- InnoDB storage engine (default)
- Foreign key constraints
- CASCADE deletion
- ENUM data types
- AUTO_INCREMENT primary keys
- TIMESTAMP columns with automatic defaults

**SQL Patterns:**
1. **Aggregate Queries:**
   ```sql
   SELECT COUNT(*) as total,
          SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count
   FROM items
   ```

2. **Dynamic Filtering:**
   ```sql
   SELECT * FROM items WHERE 1=1
   AND (title LIKE '%$search%' OR description LIKE '%$search%')
   ORDER BY created_at DESC
   ```

3. **JOIN Queries:**
   ```sql
   SELECT dr.*, items.title, users.username 
   FROM deletion_requests dr
   JOIN items ON dr.item_id = items.id
   JOIN users ON dr.user_id = users.id
   WHERE dr.status = 'pending'
   ```

### Server Environment

#### **Apache 2.4+**
**Configuration Requirements:**
- mod_rewrite enabled (optional, not currently used)
- PHP module enabled
- .htaccess support (not currently used)

**Directory Structure:**
```
C:\xampp\htdocs\lostfound\    (XAMPP)
C:\wamp64\www\lostfound\      (WAMP)
```

#### **PHP Configuration (php.ini)**
**Required Settings:**
```ini
upload_max_filesize = 10M
post_max_size = 10M
file_uploads = On
session.auto_start = 0
mysqli.default_socket = MySQL socket path
```

### Development Tools

**Required:**
- XAMPP or WAMP (Apache + MySQL + PHP bundle)
- Text editor or IDE
- Web browser (Chrome, Firefox, Safari, Edge)

**Optional:**
- phpMyAdmin (database management - included in XAMPP/WAMP)
- Git for version control
- Browser DevTools for debugging

---

## 📈 Performance Considerations

### Current Performance Characteristics

**Strengths:**
- ✓ Simple architecture = fast page loads
- ✓ No heavy frameworks = minimal overhead
- ✓ Direct database queries = low latency
- ✓ Static assets (CSS/JS) cached by browser

**Weaknesses:**
- ✗ No query optimization (no indexes beyond primary keys)
- ✗ No caching layer (Redis, Memcached)
- ✗ Full page reloads (not SPA)
- ✗ Images not optimized or resized
- ✗ No CDN for static assets
- ✗ No database connection pooling

### Database Performance

**Queries Per Page Load:**
- `index.php`: 2 queries (stats + recent items)
- `items.php`: 2 queries (stats + filtered items)
- `report_*.php`: 1-2 queries (insert + recent items)
- `user_dashboard.php`: 2 queries (stats + user items)
- `admin_dashboard.php`: 4 queries (stats + items + users + requests)

**Recommended Optimizations:**
1. **Add Indexes:**
   ```sql
   CREATE INDEX idx_type ON items(type);
   CREATE INDEX idx_created ON items(created_at);
   CREATE INDEX idx_user ON items(user_id);
   ```

2. **Pagination:**
   - Currently loads all items
   - Should implement LIMIT/OFFSET pagination
   - Example: 20 items per page

3. **Query Caching:**
   - Cache statistics queries
   - Cache recent items for homepage
   - Invalidate on new item creation

### File Upload Performance

**Current Implementation:**
- No image resizing (stores original size)
- No image compression
- No lazy loading
- Direct filesystem storage

**Recommendations:**
- Resize images to max 1024x1024
- Compress JPEG quality to 85%
- Generate thumbnails for grid view
- Consider cloud storage (AWS S3, Cloudinary)
- Implement lazy loading for images

### Frontend Performance

**Current Metrics:**
- CSS: ~600 lines (~15KB)
- JavaScript: ~40 lines (~1KB)
- No external dependencies
- Minimal HTTP requests

**Optimization Opportunities:**
- Minify CSS and JavaScript
- Combine files (single CSS, single JS)
- Enable gzip compression
- Add browser cache headers
- Use image sprites for icons

---

## 📖 Documentation

## 📚 API Reference (Internal Functions)

### Database Functions (db.php)

**Connection:**
```php
$conn = mysqli_connect($host, $username, $password, $database);
mysqli_set_charset($conn, "utf8mb4");
```

### User Authentication Functions (user_config.php)

#### `isUserLoggedIn()`
**Returns:** `bool`
**Description:** Checks if user has active session
```php
if (isUserLoggedIn()) {
    // User is logged in
}
```

#### `getCurrentUserId()`
**Returns:** `int|null`
**Description:** Gets current user's ID from session
```php
$userId = getCurrentUserId();  // Returns user ID or null
```

#### `getCurrentUsername()`
**Returns:** `string|null`
**Description:** Gets current user's username
```php
$username = getCurrentUsername();
```

#### `getCurrentUserEmail()`
**Returns:** `string|null`
**Description:** Gets current user's email
```php
$email = getCurrentUserEmail();
```

#### `registerUser($conn, $username, $email, $password)`
**Parameters:**
- `$conn` - MySQLi connection object
- `$username` - Desired username (string)
- `$email` - User email address (string)
- `$password` - Plain text password (string)

**Returns:** `string` - Empty on success, error message on failure

**Example:**
```php
$error = registerUser($conn, 'john_doe', 'john@example.com', 'password123');
if (empty($error)) {
    // Registration successful
} else {
    echo $error;  // Display error
}
```

#### `loginUser($conn, $username, $password)`
**Parameters:**
- `$conn` - MySQLi connection object
- `$username` - Username (string)
- `$password` - Plain text password (string)

**Returns:** `string` - Empty on success, error message on failure

**Example:**
```php
$error = loginUser($conn, 'john_doe', 'password123');
if (empty($error)) {
    // Login successful, session created
    header('Location: user_dashboard.php');
} else {
    echo $error;
}
```

#### `logoutUser()`
**Returns:** `void` (redirects to index.php)
**Description:** Destroys session and redirects to homepage
```php
logoutUser();  // User logged out
```

#### `requireUser()`
**Returns:** `void` (redirects if not authenticated)
**Description:** Protects pages, redirects to login if not logged in
```php
// At top of protected pages
requireUser();
```

### Admin Authentication Functions (admin_config.php)

#### `isAdminLoggedIn()`
**Returns:** `bool`
**Description:** Checks if admin is authenticated
```php
if (isAdminLoggedIn()) {
    // Show admin content
}
```

#### `authenticateAdmin($username, $password)`
**Parameters:**
- `$username` - Admin username (string)
- `$password` - Admin password (string)

**Returns:** `bool` - True on success, false on failure

**Example:**
```php
if (authenticateAdmin($_POST['username'], $_POST['password'])) {
    header('Location: admin_dashboard.php');
} else {
    $error = 'Invalid credentials';
}
```

#### `logoutAdmin()`
**Returns:** `void` (redirects to admin_login.php)
**Description:** Destroys admin session
```php
logoutAdmin();
```

#### `requireAdmin()`
**Returns:** `void` (redirects if not authenticated)
**Description:** Protects admin pages
```php
// At top of admin pages
requireAdmin();
```

### JavaScript Functions (script.js)

#### `validateForm()`
**Returns:** `bool`
**Description:** Validates form before submission
**Checks:**
- Title is not empty
- Description is not empty
- Location is not empty
- Contact email is not empty
- Email contains '@' symbol

**Usage:**
```html
<form onsubmit="return validateForm();">
    <!-- form fields -->
</form>
```

---

## 🔧 Configuration Reference

### Admin Credentials
**File:** `admin_config.php`

**Change Admin Password:**
```php
define('ADMIN_USERNAME', 'admin');           // Change username
define('ADMIN_PASSWORD', 'your_new_password'); // Change password
```

**Production Recommendation:**
- Move credentials to environment variables
- Store in database with hashed passwords
- Implement multi-admin support
- Add role-based permissions

### Database Configuration
**File:** `db.php`

**Connection Settings:**
```php
$host = 'localhost';        // Database server address
$username = 'root';         // MySQL username
$password = '';             // MySQL password (empty for XAMPP)
$database = 'lostfound_db'; // Database name
```

**For Production:**
```php
$host = 'your-database-server.com';
$username = 'your_db_user';
$password = 'strong_password_here';
$database = 'lostfound_production';
```

### Upload Configuration
**File:** `php.ini`

**Adjust Upload Limits:**
```ini
file_uploads = On
upload_max_filesize = 10M    ; Maximum file size
post_max_size = 10M          ; Maximum POST data size
max_file_uploads = 20        ; Maximum files per request
```

**Allowed File Types:**
**Files:** `report_lost.php`, `report_found.php`, `edit_item.php`

```php
$allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
```

To add more types:
```php
$allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp');
```

### Session Configuration
**File:** `php.ini`

```ini
session.auto_start = 0           ; Don't auto-start (we use session_start())
session.cookie_lifetime = 0      ; Session ends when browser closes
session.gc_maxlifetime = 1440    ; Session timeout (24 minutes)
session.save_path = "/tmp"        ; Session storage location
```

---

## ⚡ Quick Reference

### File Purposes

| File | Function |
|------|----------|
| `db.php` | Database connection setup |
| `setup.php` | Initialize database and tables |
| `index.php` | Homepage with stats and recent items |
| `items.php` | Browse all items with search/filter |
| `report_lost.php` | Form to report lost items |
| `report_found.php` | Form to report found items |
| `admin_config.php` | Admin authentication functions |
| `admin_login.php` | Admin login page |
| `admin_dashboard.php` | Admin control panel |

### Common Tasks

**Change Admin Password**:
```php
// In admin_config.php
define('ADMIN_PASSWORD', 'your_new_password');
```

**Change Database Name**:
```php
// In db.php
$database = 'your_database_name';
```

**Adjust Upload Size**:
```ini
; In php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

## 🎓 Learning Outcomes

This project demonstrates:
1. **Database Operations**: CRUD operations with MySQLi
2. **Form Handling**: Processing user input securely
3. **File Uploads**: Managing uploaded images
4. **Authentication**: Session-based login system
5. **Security**: SQL injection prevention, XSS protection
6. **Dynamic Queries**: Building queries based on filters
7. **MVC Separation**: Separating logic from presentation

---

## 🧪 Testing & Quality Assurance

### Manual Testing Checklist

#### Public Features
- ☐ Homepage loads with statistics
- ☐ Recent items display correctly
- ☐ Search filters items in real-time
- ☐ Image modal opens/closes properly
- ☐ Email links work

#### User Features
- ☐ Registration validates all fields
- ☐ Login authenticates correctly
- ☐ Users can edit only their items
- ☐ Delete removes items and images

#### Admin Features
- ☐ Admin can delete any item
- ☐ User management works
- ☐ Deletion requests processed correctly

#### Security
- ☐ SQL injection blocked
- ☐ XSS attacks prevented
- ☐ Unauthorized access redirected
- ☐ File upload type restricted

---

## 🚀 Future Enhancements & Roadmap

### Phase 1: Core Improvements
1. **Email Notifications** - Notify users of matching items
2. **Prepared Statements** - Replace string escaping with prepared statements
3. **Item Status** - Mark items as resolved/returned
4. **Password Reset** - Email-based password recovery

### Phase 2: User Experience
5. **Advanced Search** - Category, date range, building filters
6. **Image Gallery** - Multiple images per item
7. **Profile System** - User profiles with ratings
8. **Mobile App** - Native iOS/Android apps

### Phase 3: Admin Tools
9. **Analytics Dashboard** - Charts and statistics
10. **Bulk Operations** - Delete/edit multiple items
11. **Activity Logs** - Audit trail of all actions
12. **Export Data** - CSV/Excel export capability

### Phase 4: Integration
13. **SMS Notifications** - Text message alerts
14. **Social Sharing** - Share items on social media
15. **Campus Integration** - Security office sync
16. **API Development** - REST API for third-party apps

### Technical Improvements
- Migrate to Laravel/CodeIgniter framework
- Implement Redis caching
- Add database indexing
- Set up CI/CD pipeline
- Implement automated testing (PHPUnit)
- Add HTTPS enforcement
- Implement CSRF protection
- Add rate limiting

---

## 📊 Performance & Scalability

### Current Capabilities
**Concurrent Users:** ~100-500 (local server)  
**Database Size:** Unlimited (MySQL limit)  
**File Storage:** Limited by disk space  
**Page Load Time:** <1 second (local)  

### Recommended Production Specs
**Server:** 2 CPU cores, 4GB RAM, SSD  
**Database:** MySQL 8.0+, 4GB RAM  
**PHP:** 7.4+ with 256MB memory_limit  

### Optimization Opportunities
- Add database indexes on frequently queried columns
- Implement pagination (20 items per page)
- Enable query caching
- Compress and resize uploaded images
- Minify CSS/JS files
- Enable gzip compression
- Use CDN for static assets

---

## 🎓 Educational Value

### Skills Developed
**Backend:** PHP, MySQL, MySQLi, Session Management, File Handling  
**Frontend:** HTML5, CSS3, JavaScript, Responsive Design  
**Security:** Password Hashing, Input Sanitization, XSS Prevention  
**Database:** Schema Design, Foreign Keys, SQL Queries, Joins  
**Architecture:** MVC Concepts, Code Organization, Reusability  

### Suitable For
- Computer Science students
- Web development bootcamps
- Portfolio projects
- Learning full-stack development
- Understanding CRUD operations
- Practicing security best practices

## 📝 Presentation Notes

### Key Points to Highlight

1. **Problem Solved**: Helps community reunite lost items with owners
2. **Simple Technology**: Uses beginner-friendly MySQLi functions
3. **Security First**: All inputs sanitized, files validated
4. **User-Friendly**: Clean interface, easy navigation
5. **Admin Control**: Moderation and management capabilities

### Demo Flow

1. Show homepage with statistics
2. Report a lost item with image
3. Search for items using filters
4. Login as admin
5. View dashboard and delete an item
6. Show code examples with comments

## 🤝 Contributing

### Future Enhancements
- Email notifications when matching items found
- User registration and profiles
- Item status (resolved/unresolved)
- Advanced image gallery
- Mobile responsive design improvements
- Multi-language support

---

**Version**: 2.0  
**Last Updated**: 2024  
**License**: Educational Use  

*Built with ❤️ for the University Community*
