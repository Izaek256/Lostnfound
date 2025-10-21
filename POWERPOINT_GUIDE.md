# PowerPoint Creation Guide - Lost & Found Portal
## Easy-to-Follow Template for Creating Your 15-Slide Presentation

---

## HOW TO CREATE THE PRESENTATION:

### Option 1: Use Microsoft PowerPoint
1. Open PowerPoint
2. Create a blank presentation
3. Add 15 slides (using the templates below)
4. Copy the content from each section
5. Add your project screenshots

### Option 2: Use Google Slides
1. Go to slides.google.com
2. Create new presentation
3. Follow the templates below
4. Add screenshots from your project

### Option 3: Use the Python Script
1. Install: `pip install python-pptx`
2. Run: `python generate_ppt.py`
3. Open the generated .pptx file

---

## COLOR SCHEME FOR YOUR SLIDES:

- **Primary Blue:** RGB(37, 99, 235) or #2563EB
- **Danger Red:** RGB(239, 68, 68) or #EF4444
- **Success Green:** RGB(16, 185, 129) or #10B981
- **Dark Text:** RGB(30, 41, 59) or #1E293B
- **Light Background:** RGB(248, 250, 252) or #F8FAFC

---

## SLIDE-BY-SLIDE TEMPLATE:

### SLIDE 1: TITLE SLIDE
**Background:** Blue gradient
**Title:** University Lost & Found Portal
**Subtitle:** A Full-Stack Web Application
**Tech Stack:** PHP | MySQL | HTML5 | CSS3 | JavaScript
**Team Info:** 6-Member Team Presentation

**Design Tips:**
- Use large, bold white text on blue background
- Center-align everything
- Add school logo if available
- Keep it clean and professional

---

### SLIDE 2: MEMBER 1 - DATABASE ARCHITECTURE (PART 1)
**Title:** Database Architecture & Environment Configuration
**Subtitle:** 🗄️ Automatic Database Setup & Secure Configuration

**Files to Reference:**
- `db.php` (lines 1-81) - Main database connection
- `env_loader.php` (lines 1-50) - Environment loader
- `.env` - Configuration file

**Content:**
```
FILES: db.php, env_loader.php, .env

FUNCTIONALITY:
• Environment-based configuration (.env file)
• Automatic database creation
• Auto-creates tables (users, items)
• Automatic migration system
• Creates uploads directory

DATABASE SCHEMA:
USERS TABLE:
  - id, username, email, password(hashed)
  - is_admin, created_at

ITEMS TABLE:
  - id, user_id(FK), title, description
  - type(lost/found), location, contact
  - image(required), created_at

RELATIONSHIP:
Foreign key cascade - deleting user removes all their items
```

**Visual:** Show database diagram or schema screenshot

---

### SLIDE 3: MEMBER 1 - DATABASE ARCHITECTURE (PART 2)
**Title:** Database Implementation - Backend

**Code References:**
- `db.php` lines 11-28: Environment-based configuration
- `db.php` lines 30-36: Database creation
- `db.php` lines 38-48: Users table creation
- `db.php` lines 50-56: Admin column migration
- `db.php` lines 58-69: Items table with foreign key

**Content:**
```php
// 1. Load secure credentials (db.php lines 11-28)
$host = env('DB_HOST', 'localhost');

// 2. Create database
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");

// 3. Auto-create tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255), -- hashed
    is_admin TINYINT(1) DEFAULT 0
);

CREATE TABLE items (
    user_id INT,
    title VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

WHY THIS APPROACH:
✅ Zero configuration - works immediately
✅ Credentials secure in .env file
✅ Auto-migration for existing databases
✅ Cascade deletion maintains integrity
```

**Visual:** Show code snippet or environment file example

---

### SLIDE 4: MEMBER 2 - USER AUTHENTICATION (PART 1)
**Title:** User Authentication System - Frontend
**Subtitle:** 🔐 Secure Registration & Login

**Files to Reference:**
- `user_register.php` (lines 50-120) - Registration form HTML
- `user_login.php` (lines 40-90) - Login form HTML
- `script.js` (lines 3-35) - Client-side validation

**Content:**
```html
REGISTRATION FORM: (user_register.php lines 50-120)
<form method="POST" onsubmit="return validateForm();">
    <input type="text" name="username" required>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button>Register</button>
</form>

JAVASCRIPT VALIDATION:
function validateForm() {
    if (password.length < 6) {
        alert('Password must be at least 6 characters');
        return false;
    }
    if (email.indexOf('@') == -1) {
        alert('Invalid email');
        return false;
    }
    return true;
}

UI FEATURES:
✨ Professional form design
🔵 Blue border on focus
✅ Green/red alerts
📱 Mobile-responsive
```

**Visual:** Screenshot of login/registration page

---

### SLIDE 5: MEMBER 2 - USER AUTHENTICATION (PART 2)
**Title:** Authentication Implementation - Backend

**Code References:**
- `user_config.php` lines 69-112: registerUser() function
- `user_config.php` lines 119-150: loginUser() function
- `user_config.php` lines 23-27: isUserLoggedIn() check
- `user_config.php` lines 157-174: requireUser() protection

**Content:**
```php
REGISTRATION: (user_config.php lines 69-112)
function registerUser($conn, $username, $email, $password) {
    // 1. Validate input
    // 2. Check for duplicates
    // 3. Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    // 4. Insert to database
}

LOGIN:
function loginUser($conn, $username, $password) {
    // 1. Fetch user
    SELECT id, username, password, is_admin FROM users
    
    // 2. Verify password (timing-safe)
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
    }
}

SECURITY LAYERS:
🔒 Bcrypt hashing - irreversible
🛡️ SQL injection prevention
⏱️ Timing-safe verification
🔐 Session persistence
```

**Visual:** Security flow diagram or code screenshot

---

### SLIDE 6: MEMBER 3 - FILE UPLOAD (PART 1)
**Title:** Item Submission & File Upload - Frontend
**Subtitle:** 📝 Report Lost/Found Items with Images

**Files to Reference:**
- `report_lost.php` (lines 80-150) - Lost item form HTML
- `report_found.php` (lines 80-150) - Found item form HTML
- `script.js` (lines 3-35) - Form validation function
- `style.css` (lines 225-245) - Button styling (red/green)

**Content:**
```html
SUBMISSION FORM: (report_lost.php lines 80-150)
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Item name">
    <textarea name="description"></textarea>
    <input type="text" name="location">
    <input type="email" name="contact">
    <input type="file" name="image" accept="image/*" required>
    <button class="btn-danger">Report Lost Item</button>
</form>

STYLING:
🔴 Red buttons for "Lost Items"
🟢 Green buttons for "Found Items"
✅ Real-time validation
📸 Image type restriction

CLIENT VALIDATION:
if (contact.indexOf('@') == -1) {
    alert('Invalid email');
    return false;
}
```

**Visual:** Screenshot of report lost/found forms

---

### SLIDE 7: MEMBER 3 - FILE UPLOAD (PART 2)
**Title:** File Upload Implementation - Backend

**Code References:**
- `report_lost.php` lines 16-28: Image upload validation
- `report_lost.php` lines 30-35: Unique filename generation
- `report_lost.php` lines 37-40: File movement to uploads/
- `report_lost.php` lines 45-55: Database insertion
- `user_dashboard.php` lines 38-42: File cleanup on deletion

**Content:**
```php
IMAGE UPLOAD PROCESS: (report_lost.php lines 16-55)

// 1. Validate file type (security) - lines 16-28
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($fileType, $allowedTypes)) {
    error('Only images allowed');
}

// 2. Generate unique filename
$imageName = uniqid() . '.jpg';
// Result: 65a7b2f1c3d4e.jpg

// 3. Move to uploads directory
move_uploaded_file($_FILES['image']['tmp_name'], 
                    'uploads/' . $imageName);

// 4. Insert to database
INSERT INTO items (user_id, title, type, image) 
VALUES ($userId, '$title', 'lost', '$imageName')

FILE CLEANUP:
if (file_exists('uploads/' . $image)) {
    unlink('uploads/' . $image);
}

SECURITY:
🔒 Whitelist prevents malicious uploads
🎲 Unique names prevent conflicts
👥 Guest support (NULL user_id)
🗑️ Auto-cleanup on deletion
```

**Visual:** File upload flow diagram

---

### SLIDE 8: MEMBER 4 - SEARCH & BROWSE (PART 1)
**Title:** Search & Filter System - Frontend
**Subtitle:** 🔍 Dynamic Item Display

**Files to Reference:**
- `items.php` (lines 60-80) - Search form HTML
- `style.css` (lines 350-380) - Responsive grid layout
- `style.css` (lines 420-450) - Item card hover effects
- `script.js` (lines 70-85) - Modal functions

**Content:**
```html
SEARCH INTERFACE: (items.php lines 60-80)
<form method="GET">
    <input type="text" name="search" placeholder="🔍 Search...">
    <select name="filter">
        <option value="all">All Items</option>
        <option value="lost">Lost Items</option>
        <option value="found">Found Items</option>
    </select>
    <button>Apply Filters</button>
</form>

RESPONSIVE GRID:
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px rgba(0,0,0,0.1);
}

IMAGE MODAL:
function openImageModal(src, title) {
    modalImage.src = src;
    imageModal.style.display = 'block';
}
Press ESC to close
```

**Visual:** Screenshot of items page with grid layout

---

### SLIDE 9: MEMBER 4 - SEARCH & BROWSE (PART 2)
**Title:** Search Implementation - Backend

**Code References:**
- `items.php` lines 15-18: Get filter parameters
- `items.php` lines 20-35: Dynamic SQL query building
- `index.php` lines 28-35: Statistics calculation
- `items.php` lines 90-150: Display loop with foreach

**Content:**
```php
DYNAMIC SQL BUILDING: (items.php lines 20-35)

$sql = "SELECT * FROM items WHERE 1=1";

// Add type filter
if ($filter != 'all') {
    $sql .= " AND type = '$filter'";
}

// Add search (multiple columns)
if ($search != '') {
    $sql .= " AND (title LIKE '%$search%' 
                 OR description LIKE '%$search%' 
                 OR location LIKE '%$search%')";
}

$sql .= " ORDER BY created_at DESC";

STATISTICS:
SELECT COUNT(*) as total,
       SUM(CASE WHEN type='lost' THEN 1 ELSE 0 END) as lost,
       SUM(CASE WHEN type='found' THEN 1 ELSE 0 END) as found
FROM items

FEATURES:
🎯 LIKE operator - partial matching
🔗 Bookmarkable URLs
📊 Real-time statistics
📱 Responsive grid
```

**Visual:** Search results screenshot

---

### SLIDE 10: MEMBER 5 - USER DASHBOARD (PART 1)
**Title:** User Dashboard - Frontend
**Subtitle:** 👤 Personal Control Panel

**Files to Reference:**
- `user_dashboard.php` (lines 120-145) - Statistics cards HTML
- `user_dashboard.php` (lines 180-230) - Item actions buttons
- `edit_item.php` (lines 90-140) - Edit form HTML
- `style.css` (lines 800-850) - Mobile responsive grid

**Content:**
```html
STATISTICS DISPLAY: (user_dashboard.php lines 120-145)
<div style="display: grid; grid-template-columns: repeat(3, 1fr);">
    <div>
        <h3 style="color: #2563eb;">5</h3>
        <p>Total Items</p>
    </div>
    <div>
        <h3 style="color: #ef4444;">3</h3>
        <p>Lost Items</p>
    </div>
    <div>
        <h3 style="color: #10b981;">2</h3>
        <p>Found Items</p>
    </div>
</div>

ITEM ACTIONS:
<a href="edit_item.php?id=<?php echo $id; ?>">
    ✏️ Edit
</a>
<button onclick="confirm('Delete?')">
    🗑️ Delete
</button>

RESPONSIVE:
@media (max-width: 768px) {
    .items-grid { grid-template-columns: 1fr; }
}
```

**Visual:** Screenshot of user dashboard

---

### SLIDE 11: MEMBER 5 - USER DASHBOARD (PART 2)
**Title:** Dashboard Implementation - Backend

**Code References:**
- `user_dashboard.php` lines 13-14: Access control
- `user_dashboard.php` lines 61-63: Fetch user's items query
- `user_dashboard.php` lines 28-50: Delete with file cleanup
- `edit_item.php` lines 25-30: Ownership verification
- `edit_item.php` lines 50-75: Update item with optional image

**Content:**
```php
ACCESS CONTROL: (user_dashboard.php lines 13-14)
requireUser(); // Protect page
$userId = getCurrentUserId();

FETCH USER'S ITEMS:
SELECT * FROM items 
WHERE user_id = '$userId' 
ORDER BY created_at DESC

DELETE WITH CLEANUP:
// 1. Verify ownership
SELECT * FROM items WHERE id='$id' AND user_id='$userId'

// 2. Delete file
unlink('uploads/' . $item['image']);

// 3. Delete record
DELETE FROM items WHERE id='$id' AND user_id='$userId'

EDIT ITEM:
// Verify ownership
// Optional image update (keeps old if not replaced)
UPDATE items SET title='$title', image='$newImage' 
WHERE id='$id' AND user_id='$userId'

SECURITY:
✅ Ownership in SQL WHERE
✅ File cleanup
✅ Optional image update
✅ Confirmation dialogs
```

**Visual:** Edit item page screenshot

---

### SLIDE 12: MEMBER 6 - ADMIN PANEL (PART 1)
**Title:** Admin Panel - Frontend
**Subtitle:** 🛡️ System Management

**Files to Reference:**
- `admin_dashboard.php` (lines 95-120) - Admin header HTML
- `admin_dashboard.php` (lines 210-240) - Statistics cards
- `admin_dashboard.php` (lines 480-550) - User management rows
- `admin_dashboard.php` (lines 110-350 in <style>) - Red theme CSS

**Content:**
```html
ADMIN HEADER (Red Theme): (admin_dashboard.php lines 95-120)
<div style="background: #dc3545;">
    <h1 style="color: white;">🛡️ Admin Dashboard</h1>
    <a href="grant_admin.php">Grant Admin</a>
    <a href="?logout=1">Logout</a>
</div>

STATISTICS:
<div style="display: grid; grid-template-columns: repeat(3, 1fr);">
    <div><h2>125</h2><p>Total Items</p></div>
    <div><h2>78</h2><p>Lost Items</p></div>
    <div><h2>47</h2><p>Found Items</p></div>
</div>

USER MANAGEMENT:
<h4>Username 
    <span style="background: green;">ADMIN</span>
</h4>
<button name="toggle_admin">Remove Admin</button>
<button name="delete_user">Delete User</button>
```

**Visual:** Screenshot of admin dashboard

---

### SLIDE 13: MEMBER 6 - ADMIN PANEL (PART 2)
**Title:** Admin Implementation - Backend

**Code References:**
- `admin_config.php` lines 23-30: isAdminLoggedIn() function
- `admin_config.php` lines 52-60: requireAdmin() protection
- `admin_dashboard.php` lines 44-52: Toggle admin rights
- `admin_dashboard.php` lines 54-60: Delete user cascade
- `admin_dashboard.php` lines 18-28: Delete any item
- `admin_dashboard.php` lines 64-68: Get users with stats JOIN query

**Content:**
```php
ROLE-BASED AUTH: (admin_config.php lines 23-30)
function isAdminLoggedIn() {
    return isset($_SESSION['is_admin']) && 
           $_SESSION['is_admin'] == 1;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}

TOGGLE ADMIN:
$newStatus = ($currentStatus == 1) ? 0 : 1;
UPDATE users SET is_admin='$newStatus' WHERE id='$userId'

DELETE USER (cascade):
DELETE FROM users WHERE id='$userId'
// Foreign key CASCADE deletes all items

DELETE ANY ITEM:
unlink('uploads/' . $image);
DELETE FROM items WHERE id='$itemId'

GET USERS WITH STATS:
SELECT users.*, COUNT(items.id) as item_count 
FROM users LEFT JOIN items ON users.id = items.user_id 
GROUP BY users.id

WHY:
🔑 Database-driven (no hardcoded credentials)
👥 Multiple admin support
⚡ Instant changes
🗑️ Cascade deletion
```

**Visual:** User management screenshot

---

### SLIDE 14: PROJECT SUMMARY
**Title:** ✅ Complete System Overview

**Content:**
```
6 CORE MODULES:
1. ✅ Database Architecture
2. ✅ User Authentication
3. ✅ Item Submission
4. ✅ Search & Browse
5. ✅ User Dashboard
6. ✅ Admin Panel

SECURITY:
🔒 Bcrypt password hashing
🛡️ SQL injection prevention
🔐 XSS protection
📁 File upload whitelist
👤 Role-based access

RESPONSIVE DESIGN:
📱 Mobile hamburger menu
📊 Auto-adjusting grids
🎨 Professional styling
⌨️ Keyboard accessibility

TECH STACK:
PHP 7.4+ • MySQL 5.7+ • HTML5 • CSS3 • JavaScript

STATISTICS:
15 PHP files • 962 lines CSS • 106 lines JS
2 tables • 5 security layers • 3 breakpoints
```

**Visual:** System architecture diagram

---

### SLIDE 15: LIVE DEMONSTRATION
**Title:** 🎬 Live Demo Flow

**Content:**
```
DEMO SCRIPT (60 seconds):

1. HOMEPAGE (10 sec)
   → Statistics: 125 items
   → Recent items grid

2. REPORT ITEM (15 sec)
   → Fill form
   → Upload image
   → Success message

3. SEARCH & FILTER (10 sec)
   → Search "phone"
   → Filter to "Lost"
   → Image zoom modal

4. USER DASHBOARD (10 sec)
   → View stats
   → Edit item
   → Delete item

5. ADMIN PANEL (15 sec)
   → System stats
   → Grant admin
   → Delete item
   → Mobile view

READY FOR QUESTIONS!
```

**Visual:** Collage of screenshots from demo

---

## PRESENTATION DELIVERY TIPS:

### Timing Guide (10 minutes total):
- Slide 1 (Title): 30 seconds
- Member 1: 90 seconds (Slides 2-3)
- Member 2: 90 seconds (Slides 4-5)
- Member 3: 90 seconds (Slides 6-7)
- Member 4: 90 seconds (Slides 8-9)
- Member 5: 90 seconds (Slides 10-11)
- Member 6: 90 seconds (Slides 12-13)
- Slide 14 (Summary): 60 seconds
- Slide 15 (Demo): 60 seconds
- Q&A: Remaining time

### Speaking Tips:
1. **Practice transitions** between members
2. **Point to code** as you explain it
3. **Show actual website** alongside slides
4. **Emphasize security** features
5. **Highlight teamwork** and integration
6. **Be ready for questions** about:
   - Why PHP instead of modern frameworks?
   - Security vulnerabilities and mitigations
   - Scalability considerations
   - Database design choices

### Visual Recommendations:
1. **Screenshots:** Take clear screenshots of each page
2. **Code Highlighting:** Use syntax highlighting for code blocks
3. **Diagrams:** Create simple flow diagrams for processes
4. **Consistency:** Use same color scheme throughout
5. **Animations:** Simple bullet-point animations only

---

## QUICK SCREENSHOT CHECKLIST:

Take these screenshots from your website:

1. ✅ Homepage with statistics
2. ✅ Report Lost Item form
3. ✅ Report Found Item form
4. ✅ Items browse page with grid
5. ✅ Search results
6. ✅ Image modal zoom
7. ✅ Login page
8. ✅ Registration page
9. ✅ User dashboard
10. ✅ Edit item page
11. ✅ Admin dashboard
12. ✅ User management section
13. ✅ Mobile responsive view
14. ✅ Database schema (phpMyAdmin)
15. ✅ File structure in IDE

---

## BACKUP PLAN:

If technology fails during demo:
1. Have screenshots ready
2. Record a 60-second demo video beforehand
3. Print key slides as handouts
4. Know the code well enough to explain without slides

---

**GOOD LUCK WITH YOUR PRESENTATION!**

Remember: You built this project, you understand it better than anyone else. Be confident, speak clearly, and show enthusiasm for your work!
