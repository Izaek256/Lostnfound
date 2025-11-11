# University Lost & Found Portal

## Project Overview

The **University Lost & Found Portal** is a comprehensive web application designed to help students reunite lost items with their owners on campus. The system employs a **distributed three-server architecture** where each server has a specific responsibility, ensuring modularity, scalability, and separation of concerns.

---

## 🏗️ System Architecture

This project follows a **three-tier distributed architecture** with clear separation of responsibilities:

```
┌─────────────────────────────────────────────────────────────┐
│                    ServerC (Frontend/UI)                     │
│                    http://172.24.14.184                      │
│         - User Interface & Client-Side Rendering            │
│         - NO direct database access                         │
│         - Communicates via API calls only                   │
└──────────────────┬──────────────────┬───────────────────────┘
                   │                   │
                   │ API Calls         │ API Calls
                   │                   │
        ┌──────────▼──────────┐       │
        │   ServerA (Items)   │       │
        │  172.24.194.6      │          
        │  - Item CRUD ops    │       │
        │  - Direct DB access │       │
        └──────────┬──────────┘       │
                   │                   │
                   │ DB Connection     │
                   │                   │
        ┌──────────▼──────────────────▼──────────┐
        │        ServerB (Users & DB)             │
        │        http://172.24.194.6             │
        │   - User authentication                 │
        │   - User management                     │
        │   - Database hosting                    │
        │   - File uploads storage                │
        │   - Proxies item requests to ServerA    │
        └─────────────────────────────────────────┘
```

### Server Roles

| Server | Role | Responsibilities | Database Access |
|--------|------|-----------------|-----------------|
| **ServerA** | **Item Logic Server** | Handle all item operations (CRUD) | ✅ Direct (remote) |
| **ServerB** | **User Logic & Database Server** | User authentication, database hosting, file storage | ✅ Direct (local) |
| **ServerC** | **User Interface Client** | Frontend, UI rendering, API client | ❌ API only |

---

## 🌐 Network Configuration

### Deployment Mode
- **Mode**: Staging Deployment
- **Configuration**: Auto-managed via `deploy.php`
- **Database Location**: ServerB (172.24.194.6)

### Server URLs
- **ServerA API**: `http://172.24.194.6/Lostnfound/ServerA/api`
- **ServerB API**: `http://172.24.194.6/Lostnfound/ServerB/api`
- **ServerC UI**: `http://172.24.14.184/Lostnfound/ServerC`

### Database Configuration
- **Host**: `172.24.194.6` (ServerB)
- **Database Name**: `lostfound_db`
- **Access**: ServerA and ServerB have direct access; ServerC uses APIs only

---

## 📋 Core Features

### For Users
✅ **Report Lost Items** - Create detailed listings of lost items with photos  
✅ **Report Found Items** - Post items you've found to help others  
✅ **Search & Filter** - Search by keywords, filter by type (lost/found)  
✅ **User Dashboard** - Manage your posted items, edit, and delete  
✅ **Email Contact** - Direct contact between finders and owners  
✅ **User Authentication** - Secure login and registration system  

### For Administrators
✅ **Admin Dashboard** - Overview of all users and items  
✅ **User Management** - Promote/demote admin privileges  
✅ **Item Moderation** - View and delete any item  
✅ **System Statistics** - Real-time stats on items and users  
✅ **Health Monitoring** - Server status and connectivity checks  

---

## 🗄️ Database Schema

### Tables

#### `users` Table
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AUTO_INCREMENT) | Unique user identifier |
| `username` | VARCHAR(50) UNIQUE | User's login name |
| `email` | VARCHAR(100) UNIQUE | User's email address |
| `password` | VARCHAR(255) | Hashed password (bcrypt) |
| `is_admin` | TINYINT(1) | Admin flag (0=user, 1=admin) |
| `created_at` | TIMESTAMP | Account creation time |

#### `items` Table
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AUTO_INCREMENT) | Unique item identifier |
| `user_id` | INT (FK → users.id) | Owner of the listing |
| `title` | VARCHAR(100) | Item name/title |
| `description` | TEXT | Detailed description |
| `type` | ENUM('lost', 'found') | Item type |
| `location` | VARCHAR(100) | Where item was lost/found |
| `contact` | VARCHAR(100) | Contact email/phone |
| `image` | VARCHAR(255) | Image filename |
| `created_at` | TIMESTAMP | Listing creation time |

---

## 🔌 Inter-Server Communication

### Communication Protocol
All servers communicate via **HTTP REST API calls** using the centralized `makeAPIRequest()` function.

### Key Features
- **Automatic Retry Logic**: Up to 3 retries on failure with exponential backoff
- **Timeout Management**: Configurable connection and request timeouts
- **JSON Support**: Automatic JSON parsing for API responses
- **Error Handling**: Comprehensive error logging and user-friendly messages
- **CORS Headers**: Cross-origin requests fully supported
- **cURL-Based**: Robust HTTP client implementation

### Example API Call Flow
```
User clicks "Report Lost Item" on ServerC
    ↓
ServerC uploads image to ../ServerB/uploads/
    ↓
ServerC calls ServerA API: POST /add_item.php
    ↓
ServerA inserts item into database
    ↓
ServerA returns JSON response
    ↓
ServerC displays success message to user
```

---

## 📁 Project Structure

```
Lostnfound/
├── ServerA/                      # Item Logic Server
│   ├── api/                      # API Endpoints
│   │   ├── add_item.php         # Create new item
│   │   ├── update_item.php      # Update existing item
│   │   ├── delete_item.php      # Delete item
│   │   ├── get_all_items.php    # Retrieve all items with filters
│   │   ├── get_item.php         # Get single item by ID
│   │   ├── get_user_items.php   # Get items by user
│   │   └── health.php           # Health check endpoint
│   ├── config.php               # Server configuration
│   ├── deployment_config.php    # Auto-generated deployment config
│   └── db_setup.php             # Database initialization
│
├── ServerB/                      # User Logic & Database Server
│   ├── api/                      # API Endpoints
│   │   ├── register_user.php    # User registration
│   │   ├── verify_user.php      # User authentication
│   │   ├── get_all_users.php    # Get all users (admin)
│   │   ├── get_user_items.php   # Proxy to ServerA
│   │   ├── toggle_admin.php     # Toggle admin status
│   │   └── health.php           # Health check endpoint
│   ├── config.php               # Server configuration
│   ├── deployment_config.php    # Auto-generated deployment config
│   └── uploads/                 # Image storage directory
│
├── ServerC/                      # User Interface Client
│   ├── api/                      
│   │   └── health.php           # Health check endpoint
│   ├── assets/                   # Frontend assets
│   │   ├── style.css            # Main stylesheet (2000+ lines)
│   │   ├── script.js            # Client-side JavaScript
│   │   ├── logo.webp            # Application logo
│   │   └── favicon.svg          # Browser favicon
│   ├── index.php                # Homepage
│   ├── items.php                # Browse all items
│   ├── report_lost.php          # Report lost item form
│   ├── report_found.php         # Report found item form
│   ├── user_login.php           # User login page
│   ├── user_register.php        # User registration page
│   ├── user_dashboard.php       # User dashboard
│   ├── admin_dashboard.php      # Admin control panel
│   ├── edit_item.php            # Edit item page
│   ├── config.php               # Client configuration
│   ├── api_client.php           # OOP API client class
│   └── deployment_config.php    # Auto-generated deployment config
│
├── server_status.php             # Overall system health check
└── README.md                     # This file
```

---

## 🚀 Installation & Setup

### Prerequisites
- **PHP**: 8.0 or higher
- **MySQL/MariaDB**: 5.7 or higher
- **Apache/Nginx**: Web server with PHP support
- **cURL Extension**: For inter-server communication
- **GD/Imagick**: For image handling (optional)

### Step 1: Database Setup
```bash
# On ServerB (Database Server)
1. Import/run ServerA/db_setup.php
2. Create uploads directory: mkdir ServerB/uploads && chmod 755 ServerB/uploads
```

### Step 2: Configure Deployment
```bash
# Edit deploy.php with your server IPs
php deploy.php
```

### Step 3: Set Permissions
```bash
# Make upload directories writable
chmod -R 755 ServerB/uploads/
```

### Step 4: Apache Configuration (ServerC - WAMP)
```apache
<Directory "c:/xampp/htdocs/Lostnfound/ServerC">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Step 5: Test Health Endpoints
- ServerA: `http://172.24.194.6/Lostnfound/ServerA/api/health.php`
- ServerB: `http://172.24.194.6/Lostnfound/ServerB/api/health.php`
- ServerC: `http://172.24.14.184/Lostnfound/ServerC/health.php`

---

## 🔐 Security Features

### Password Security
- **Bcrypt Hashing**: All passwords use PHP's `password_hash()` with bcrypt
- **No Plain Text**: Passwords never stored in plain text

### Input Validation
- **SQL Injection Protection**: `mysqli_real_escape_string()` on all inputs
- **XSS Prevention**: `htmlspecialchars()` on all outputs
- **Email Validation**: `filter_var()` with FILTER_VALIDATE_EMAIL
- **File Upload Validation**: File type and size checks

### Session Management
- **Secure Sessions**: Session-based authentication
- **Session Hijacking Prevention**: Session regeneration on login
- **Auto-logout**: Session destruction on logout

### API Security
- **CORS Headers**: Controlled cross-origin access
- **Method Validation**: Strict HTTP method checking (GET, POST, DELETE)
- **Error Handling**: No sensitive data in error messages

---

## 📊 API Documentation

### ServerA Endpoints (Item Operations)

#### POST `/api/add_item.php`
Create a new item listing.

**Parameters:**
- `user_id` (required): User's ID
- `title` (required): Item title
- `description` (required): Item description
- `type` (required): "lost" or "found"
- `location` (required): Location details
- `contact` (required): Contact information
- `image_filename` (optional): Uploaded image filename

**Response:**
```json
{
  "success": true,
  "item_id": 123,
  "message": "Item added successfully"
}
```

#### GET `/api/get_all_items.php`
Retrieve all items with optional filtering.

**Parameters:**
- `type` (optional): Filter by "lost" or "found"
- `search` (optional): Search in title, description, location

**Response:**
```json
{
  "success": true,
  "items": [...],
  "count": 25,
  "stats": {
    "total": 50,
    "lost_count": 30,
    "found_count": 20
  }
}
```

### ServerB Endpoints (User Operations)

#### POST `/api/register_user.php`
Register a new user account.

**Parameters:**
- `username` (required): Unique username
- `email` (required): Valid email address
- `password` (required): User password

**Response:**
```json
{
  "success": true,
  "user_id": 5,
  "username": "john_doe",
  "email": "john@university.edu",
  "message": "User registered successfully"
}
```

#### POST `/api/verify_user.php`
Authenticate a user (login).

**Parameters:**
- `username` (required): Username
- `password` (required): Password

**Response:**
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

---

## 🎨 Design & UI

### Design Philosophy
- **Clean & Professional**: Modern, minimalist design
- **User-Friendly**: Intuitive navigation and clear CTAs
- **Responsive**: Mobile-first design, works on all devices
- **Accessible**: High contrast, readable fonts, semantic HTML

### Color Scheme
- **Primary**: `#2563eb` (Blue) - Trust, reliability
- **Success**: `#10b981` (Green) - Found items, positive actions
- **Error**: `#ef4444` (Red) - Lost items, warnings
- **Neutral**: Grayscale palette for backgrounds and text

### Typography
- **Font Family**: System fonts (-apple-system, Segoe UI, Roboto)
- **Base Size**: 16px for optimal readability
- **Line Height**: 1.7 for comfortable reading

### Responsive Breakpoints
- **Desktop**: 1200px+ (Full layout)
- **Tablet**: 768px - 1199px (Adapted layout)
- **Mobile**: < 768px (Stacked layout, hamburger menu)

---

## 🔧 Maintenance & Troubleshooting

### Common Issues

#### "ServerC cannot connect directly to the database"
✅ **Expected Behavior**: ServerC is designed to use APIs only. Use `makeAPIRequest()` instead.

#### "Image not displaying"
- Check `ServerB/uploads/` directory exists and is writable
- Verify image path in database matches actual file
- Check `UPLOADS_BASE_URL` in deployment_config.php

#### "API request timeout"
- Increase timeout in `makeAPIRequest()` options
- Check server connectivity (firewall, network)
- Verify target server is running

#### "Session not persisting"
- Check `session_start()` is called in config.php
- Verify PHP session directory is writable
- Check for `SameSite` cookie issues

### Health Monitoring
Visit these endpoints to check server status:
- ServerA: `/api/health.php`
- ServerB: `/api/health.php`
- ServerC: `/health.php`

---

## 👥 User Roles

### Regular Users
- Register and log in
- Report lost/found items
- Edit/delete own items
- Search and browse all items
- Contact other users via email

### Administrators
- All regular user permissions
- View all users in system
- Promote/demote admin status
- Delete any item (moderation)
- View system statistics
- Access admin dashboard

---

## 📝 License & Credits

### Project Information
- **Name**: University Lost & Found Portal
- **Version**: 2.0
- **Architecture**: Distributed Three-Server System
- **Built With**: PHP, MySQL, JavaScript, HTML5, CSS3

### Technologies Used
- **Backend**: PHP 8+ with mysqli
- **Database**: MySQL/MariaDB
- **Frontend**: Vanilla JavaScript, Custom CSS
- **Communication**: cURL for HTTP requests
- **Authentication**: Session-based with bcrypt
- **File Handling**: PHP file uploads

---

## 🚧 Future Enhancements

- [ ] Email notifications for matches
- [ ] Image compression and optimization
- [ ] Advanced search with filters (date range, category)
- [ ] User reputation system
- [ ] Mobile app (React Native)
- [ ] Real-time notifications (WebSockets)
- [ ] Multi-language support (i18n)
- [ ] Dark mode toggle
- [ ] Export reports to PDF
- [ ] Integration with university ID system

---

## 📞 Support

For issues, questions, or contributions:
1. Check the health endpoints first
2. Review server logs (`error_log`)
3. Verify deployment configuration
4. Check inter-server connectivity

---

**Built with ❤️ for campus communities**
