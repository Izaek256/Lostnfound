# ItemsServer Documentation - Item Logic Server

## 🎯 Server Role & Responsibility

**ItemsServer** is the **Item Logic Server** in the Lost & Found distributed system. It serves as the **sole authority** for all item-related operations, including creating, reading, updating, and deleting items in the database.

**Directory**: `ItemsServer/`  
**IP Address**: `172.24.194.6`  
**Base URL**: `http://172.24.194.6/Lostnfound/ItemsServer`  
**API Base URL**: `http://172.24.194.6/Lostnfound/ItemsServer/api`

### Core Responsibilities
✅ Handle all item CRUD operations (Create, Read, Update, Delete)  
✅ Direct database access for items table  
✅ Item filtering and searching  
✅ Item statistics and analytics  
✅ Provide RESTful API endpoints for item operations  
✅ Validate item data before database insertion  
✅ Store uploaded item images in `/uploads/` directory  

### What ItemsServer Does NOT Handle
❌ User authentication  
❌ User registration  
❌ Session management  
❌ Frontend rendering (handled by Frontend)  

---

## 🌐 Network Configuration

### Server Details
- **IP Address**: `172.24.194.6`
- **Base URL**: `http://172.24.194.6/Lostnfound/ItemsServer`
- **API Base URL**: `http://172.24.194.6/Lostnfound/ItemsServer/api`
- **Location**: Same machine as UserServer
- **Role**: Item Logic Server

### Database Connection
- **Host**: `172.24.194.6` (Remote connection to UserServer's database)
- **Database**: `lostfound`
- **User**: `root`
- **Connection Type**: Remote MySQL connection
- **Access**: Direct database access via `connectDB()`

### API Endpoints
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/add_item.php` | POST | Create new item |
| `/api/update_item.php` | POST | Update existing item |
| `/api/delete_item.php` | POST | Delete item |
| `/api/get_all_items.php` | GET | Get all items with filters |
| `/api/get_item.php` | GET | Get single item by ID |
| `/api/get_user_items.php` | GET | Get items by user |
| `/api/health.php` | GET | Server health check |

---

## 📁 File Structure

```
ItemsServer/
├── api/
│   ├── add_item.php          # Create new item (POST)
│   ├── delete_item.php       # Delete item (POST)
│   ├── get_all_items.php     # Retrieve items with filters (GET)
│   ├── get_item.php          # Get single item (GET)
│   ├── get_user_items.php    # Get items by user (GET)
│   ├── health.php            # Health check endpoint (GET)
│   └── update_item.php       # Update item (POST)
├── uploads/                  # Image storage directory for items
│   └── [user_uploaded_files]
├── config.php                # Server configuration & functions
├── db_setup.php              # Database initialization script
└── deployment_config.php     # Auto-generated deployment settings
```

---

## 🔧 Core Configuration (`config.php`)

### Purpose
The `config.php` file serves as the **central configuration hub** for ItemsServer, providing:
- Database connection functions
- Session management
- CORS header handling
- User authentication helpers
- JSON response utilities

### Key Functions

#### 1. Database Connection
```php
function connectDB()
```
- **Purpose**: Establish connection to the centralized database on UserServer
- **Returns**: mysqli connection object
- **Error Handling**: Dies with error message if connection fails
- **Usage**: Used by all API endpoints to access the database

**Implementation Details:**
- Uses global variables: `$db_host`, `$db_name`, `$db_user`, `$db_pass`
- Connects to remote database at `172.24.194.6`
- Returns active mysqli connection
- Critical for all database operations

#### 2. Session & User Functions
```php
function isUserLoggedIn()         // Check if user has active session
function getCurrentUserId()       // Get logged-in user's ID
function getCurrentUsername()     // Get logged-in user's username
function getCurrentUserEmail()    // Get logged-in user's email
function isCurrentUserAdmin()     // Check if user is admin
function requireUser()            // Redirect if not logged in
function logoutUser()             // Destroy session and redirect
```

**Note**: While these functions exist in ItemsServer's config, they are primarily used by Frontend (UI server). ItemsServer APIs work independently of sessions.

#### 3. API Helper Functions

**`sendJSONResponse($data, $status_code = 200)`**
- Sends properly formatted JSON response
- Sets HTTP status code
- Adds CORS headers automatically
- Exits script after sending response

**Example:**
```php
sendJSONResponse([
    'success' => true,
    'item_id' => 123,
    'message' => 'Item added successfully'
], 200);
```

**`setCORSHeaders()`**
- Sets Cross-Origin Resource Sharing headers
- Allows requests from any origin (can be restricted in production)
- Handles OPTIONS preflight requests
- Enables cross-server API communication

**CORS Headers Set:**
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With`
- `Access-Control-Max-Age: 86400`

---

## 🔌 API Endpoints Documentation

### 1. Add Item - `POST /api/add_item.php`

**Purpose**: Create a new lost or found item listing in the database.

**Request Method**: POST

**Required Parameters:**
| Parameter | Type | Description | Validation |
|-----------|------|-------------|------------|
| `user_id` | integer | ID of the user posting the item | Required, must exist in users table |
| `title` | string | Item title/name | Required, max 100 chars |
| `description` | text | Detailed item description | Required |
| `type` | enum | "lost" or "found" | Required, must be 'lost' or 'found' |
| `location` | string | Where item was lost/found | Required, max 100 chars |
| `contact` | string | Contact information | Required, max 100 chars |
| `image_filename` | string | Filename of uploaded image | Optional |

**Request Example:**
```http
POST /api/add_item.php
Content-Type: application/x-www-form-urlencoded

user_id=5
&title=Black iPhone 13
&description=Lost my black iPhone 13 in the library
&type=lost
&location=Main Library 2nd Floor
&contact=john@university.edu
&image_filename=abc123.jpg
```

**Success Response (200):**
```json
{
  "success": true,
  "item_id": 123,
  "message": "Item added successfully"
}
```

**Error Responses:**
```json
// Missing fields (400)
{
  "error": "Please fill all required fields"
}

// Invalid type (400)
{
  "error": "Invalid item type"
}

// Database error (500)
{
  "error": "Failed to add item: [MySQL error]"
}
```

**Database Query:**
```sql
INSERT INTO items (user_id, title, description, type, location, contact, image, created_at) 
VALUES ('$user_id', '$title', '$description', '$type', '$location', '$contact', '$image_filename', NOW())
```

**Security Measures:**
- SQL injection prevention via `mysqli_real_escape_string()`
- Type validation (lost/found only)
- Required field validation
- CORS headers for cross-origin requests

---

### 2. Get All Items - `GET /api/get_all_items.php`

**Purpose**: Retrieve all items with optional filtering by type and search query.

**Request Method**: GET

**Optional Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `type` | string | Filter by "lost" or "found" | all |
| `search` | string | Search in title, description, location | none |

**Request Examples:**
```http
# Get all items
GET /api/get_all_items.php

# Get only lost items
GET /api/get_all_items.php?type=lost

# Search for iPhone
GET /api/get_all_items.php?search=iPhone

# Combine filters
GET /api/get_all_items.php?type=found&search=wallet
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
      "description": "Lost my black iPhone...",
      "type": "lost",
      "location": "Main Library",
      "contact": "john@university.edu",
      "image": "abc123.jpg",
      "created_at": "2024-11-11 10:30:00"
    },
    // ... more items
  ],
  "count": 25,
  "stats": {
    "total": 50,
    "lost_count": 30,
    "found_count": 20
  }
}
```

**Database Query:**
```sql
SELECT i.*, u.username 
FROM items i 
LEFT JOIN users u ON i.user_id = u.id 
WHERE 1=1
  [AND i.type = 'lost']  -- if type filter applied
  [AND (i.title LIKE '%search%' OR i.description LIKE '%search%' OR i.location LIKE '%search%')]
ORDER BY i.created_at DESC
```

**Features:**
- **Joins users table** to include username in response
- **Flexible filtering** by type and search query
- **Statistics calculation** for dashboard displays
- **Ordered by date** (newest first)

---

### 3. Get Single Item - `GET /api/get_item.php`

**Purpose**: Retrieve details of a specific item by its ID.

**Request Method**: GET

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `item_id` | integer | ID of the item to retrieve |

**Request Example:**
```http
GET /api/get_item.php?item_id=123
```

**Success Response (200):**
```json
{
  "success": true,
  "item": {
    "id": 123,
    "user_id": 5,
    "username": "john_doe",
    "title": "Black iPhone 13",
    "description": "Lost my black iPhone 13...",
    "type": "lost",
    "location": "Main Library 2nd Floor",
    "contact": "john@university.edu",
    "image": "abc123.jpg",
    "created_at": "2024-11-11 10:30:00"
  }
}
```

**Error Responses:**
```json
// Missing or invalid item_id (400)
{
  "error": "Valid item_id parameter is required"
}

// Item not found (404)
{
  "error": "Item not found"
}
```

**Database Query:**
```sql
SELECT i.*, u.username 
FROM items i 
LEFT JOIN users u ON i.user_id = u.id 
WHERE i.id = '$item_id'
```

---

### 4. Get User Items - `GET /api/get_user_items.php`

**Purpose**: Retrieve all items posted by a specific user.

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

**Database Query:**
```sql
SELECT i.*, u.username 
FROM items i 
LEFT JOIN users u ON i.user_id = u.id 
WHERE i.user_id = '$user_id' 
ORDER BY i.created_at DESC
```

**Features:**
- Returns all items for specified user
- Includes statistics breakdown
- Ordered by creation date (newest first)

---

### 5. Update Item - `POST /api/update_item.php`

**Purpose**: Update an existing item's details (only by owner).

**Request Method**: POST

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Item ID to update |
| `user_id` | integer | User's ID (for ownership verification) |
| `title` | string | Updated title |
| `description` | text | Updated description |
| `type` | enum | "lost" or "found" |
| `location` | string | Updated location |
| `contact` | string | Updated contact info |
| `image_filename` | string | New image filename (optional) |

**Request Example:**
```http
POST /api/update_item.php
Content-Type: application/x-www-form-urlencoded

id=123
&user_id=5
&title=Black iPhone 13 Pro
&description=Updated description
&type=lost
&location=Library 3rd Floor
&contact=newemail@university.edu
&image_filename=new_image.jpg
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item updated successfully"
}
```

**Error Responses:**
```json
// Item not found or not owned by user (404)
{
  "error": "Item not found or access denied"
}

// Invalid type (400)
{
  "error": "Invalid item type"
}
```

**Security:**
- **Ownership verification**: Checks `user_id` matches item owner
- **Type validation**: Only "lost" or "found" allowed
- **SQL injection protection**: All inputs escaped

**Database Queries:**
```sql
-- Ownership check
SELECT * FROM items WHERE id = '$item_id' AND user_id = '$user_id'

-- Update (if image provided)
UPDATE items 
SET title = '$title', description = '$description', 
    type = '$type', location = '$location', 
    contact = '$contact', image = '$image_filename' 
WHERE id = '$item_id' AND user_id = '$user_id'

-- Update (without image)
UPDATE items 
SET title = '$title', description = '$description', 
    type = '$type', location = '$location', contact = '$contact' 
WHERE id = '$item_id' AND user_id = '$user_id'
```

---

### 6. Delete Item - `POST /api/delete_item.php`

**Purpose**: Delete an item from the database (by owner or admin).

**Request Method**: POST

**Required Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Item ID to delete |
| `user_id` | integer | User's ID (optional for admin) |
| `is_admin` | integer | 1 if admin deletion, 0 otherwise |

**Request Example:**
```http
# User deletion
POST /api/delete_item.php
id=123&user_id=5&is_admin=0

# Admin deletion
POST /api/delete_item.php
id=123&is_admin=1
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item deleted successfully",
  "image": "abc123.jpg"
}
```

**Error Responses:**
```json
// Item not found (404)
{
  "error": "Item not found"
}

// Not owned by user and not admin (404)
{
  "error": "Item not found or access denied"
}
```

**Security Logic:**
- **Admin bypass**: If `is_admin = 1`, skip ownership check
- **User verification**: Regular users can only delete their own items
- **Image cleanup**: Returns image filename for client-side cleanup

**Database Queries:**
```sql
-- Check if item exists
SELECT image FROM items WHERE id = '$item_id'

-- Ownership check (if not admin)
SELECT id FROM items WHERE id = '$item_id' AND user_id = '$user_id'

-- Delete
DELETE FROM items WHERE id = '$item_id'
```

---

### 7. Health Check - `GET /api/health.php`

**Purpose**: Monitor server status and database connectivity.

**Request Method**: GET

**Request Example:**
```http
GET /api/health.php
```

**Success Response (200):**
```json
{
  "server": "ItemsServer",
  "status": "online",
  "database": "connected",
  "timestamp": "2024-11-11 14:30:45",
  "services": {
    "items_database": "50 items stored",
    "uploads_directory": "writable",
    "add_item_api": "active",
    "get_items_api": "active",
    "update_item_api": "active",
    "delete_item_api": "active"
  }
}
```

**Features:**
- **Direct DB check**: Bypasses session_start to avoid conflicts
- **User count**: Queries users table for statistics
- **Service availability**: Checks for API endpoint files
- **Timestamp**: Current server time

**Features:**
- **Direct DB check**: Bypasses session_start to avoid conflicts
- **Item count**: Queries items table for statistics
- **Service availability**: Checks for API endpoint files
- **Uploads directory**: Verifies uploads folder is writable
- **Timestamp**: Current server time

---

## 🗄️ Database Schema (Items Table)

ItemsServer directly interacts with the `items` table:

```sql
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(100) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Column Details
- **`id`**: Auto-incrementing primary key
- **`user_id`**: Foreign key to users table (CASCADE delete)
- **`title`**: Item name/title (max 100 characters)
- **`description`**: Detailed description (TEXT field)
- **`type`**: ENUM restricting values to 'lost' or 'found'
- **location**: Where item was lost/found
- **contact**: Email or phone for contact
- **image**: Filename of uploaded image (stored in ItemsServer/uploads/)
- **created_at**: Automatic timestamp on creation

---

## 🔒 Security Implementation

### SQL Injection Prevention
All user inputs are escaped using `mysqli_real_escape_string()`:
```php
$title = mysqli_real_escape_string($conn, $title);
$description = mysqli_real_escape_string($conn, $description);
```

### Input Validation
- **Required field checks**: Ensures all mandatory fields are present
- **Type validation**: Only 'lost' or 'found' accepted for item type
- **Numeric validation**: `is_numeric()` checks for IDs
- **Ownership verification**: Users can only modify their own items

### CORS Security
- **Cross-Origin Headers**: Allows API access from Frontend
- **Preflight Handling**: OPTIONS requests handled automatically
- **Method Restrictions**: Only specified HTTP methods allowed

### Error Handling
- **No sensitive data**: Error messages don't expose database structure
- **HTTP status codes**: Proper codes (400, 404, 500) for different errors
- **Logging**: Errors logged to error_log for debugging

---

## 🔗 Integration with Other Servers

### UserServer → ItemsServer
**Use Case**: When UserServer needs item data for a user dashboard

**Flow:**
```
UserServer receives request from client
    ↓
UserServer calls ItemsServer: GET /get_user_items.php?user_id=5
    ↓
ItemsServer queries database
    ↓
ItemsServer returns JSON with items
    ↓
UserServer forwards to client
```

**Implementation** (in UserServer's `get_user_items.php`):
```php
$response = makeAPIRequest(
    ITEMSSERVER_API_URL . '/get_user_items.php',
    ['user_id' => $user_id],
    'GET',
    ['return_json' => true, 'force_json' => true]
);
```

### Frontend → ItemsServer
**Use Case**: Direct item operations from the UI

**Flow:**
```
User fills out "Report Lost Item" form on Frontend
    ↓
Frontend uploads image to ../ItemsServer/uploads/
    ↓
Frontend calls ItemsServer: POST /add_item.php
    ↓
ItemsServer validates and inserts into database
    ↓
ItemsServer returns success/error JSON
    ↓
Frontend displays result to user
```

---

## 🚀 Deployment Configuration

ItemsServer uses auto-generated deployment configuration from `deployment_config.php`.

### Key Configuration Constants
```php
define('DEPLOYMENT_MODE', 'staging');
define('CURRENT_SERVER', 'ItemsServer');
define('ITEMSSERVER_IP', '172.24.194.6');
define('USERSERVER_IP', '172.24.194.6');
define('FRONTEND_IP', '172.24.14.184');
define('DB_HOST', '172.24.194.6');
define('DB_NAME', 'lostfound');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Helper Functions
- `validateDeploymentConfig()`: Validates all required constants
- `getCurrentServerRole()`: Returns "Authentication Server" (legacy name)
- `getDeploymentInfo()`: Returns full deployment details
- `isLocalDeployment()`: Checks if running in local mode
- `isProductionDeployment()`: Checks if running in production mode

---

## 📊 Performance Considerations

### Query Optimization
- **JOIN operations**: Uses LEFT JOIN to fetch username efficiently
- **Indexed columns**: Primary keys and foreign keys are indexed
- **ORDER BY**: Sorting by created_at (indexed timestamp)

### Connection Management
- **Single connection**: One database connection per request
- **Connection closing**: Explicitly closes connections after queries
- **Error handling**: Connections closed even on errors

### Response Size
- **Pagination**: Not yet implemented (future enhancement)
- **Field selection**: SELECT * used (could be optimized)
- **Image handling**: Only filename returned, not binary data

---

## 🧪 Testing ItemsServer APIs

### Using cURL

**Test Add Item:**
```bash
curl -X POST http://172.24.194.6/Lostnfound/ItemsServer/api/add_item.php \
  -d "user_id=1" \
  -d "title=Test Item" \
  -d "description=Test Description" \
  -d "type=lost" \
  -d "location=Test Location" \
  -d "contact=test@example.com"
```

**Test Get All Items:**
```bash
curl http://172.24.194.6/Lostnfound/ItemsServer/api/get_all_items.php
```

**Test Search:**
```bash
curl "http://172.24.194.6/Lostnfound/ItemsServer/api/get_all_items.php?search=iPhone"
```

**Test Health Check:**
```bash
curl http://172.24.194.6/Lostnfound/ItemsServer/api/health.php
```

---

## 🐛 Common Issues & Solutions

### Issue: "Database connection failed"
**Cause**: Cannot connect to remote database on UserServer  
**Solution**: 
- Check UserServer MySQL is running
- Verify firewall allows remote connections
- Confirm database credentials in deployment_config.php

### Issue: "Item not found or access denied"
**Cause**: User trying to modify item they don't own  
**Solution**: Ensure correct user_id is passed, or use admin flag

### Issue: "Failed to add item: Duplicate entry"
**Cause**: Attempting to insert duplicate data  
**Solution**: Check for unique constraints in database schema

---

## 📝 Best Practices

### When Calling ItemsServer APIs
✅ Always handle JSON responses  
✅ Check for 'success' key in response  
✅ Include proper error handling  
✅ Validate input before sending  
✅ Use appropriate HTTP methods  

### When Modifying ItemsServer Code
✅ Always escape user inputs  
✅ Use prepared statements (future enhancement)  
✅ Close database connections  
✅ Return consistent JSON format  
✅ Set proper HTTP status codes  
✅ Test with various edge cases  

---

**ItemsServer serves as the backbone of item management in the Lost & Found system, providing reliable and secure API endpoints for all item-related operations.**
