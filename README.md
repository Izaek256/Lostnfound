# University Lost & Found Portal

A web application to help students reunite lost items with their owners on campus. The system uses a distributed three-server architecture with clear separation of concerns.

## System Architecture

Three-tier distributed architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend                                  │
│                    http://172.24.14.184                      │
│         - User Interface & Rendering                         │
│         - API client only (no direct database access)        │
└──────────────────┬──────────────────┬───────────────────────┘
                   │                   │
        ┌──────────▼──────────┐       │
        │   ItemsServer       │       │
        │  172.24.194.6       │       │
        │  - Item CRUD ops    │       │
        │  - Direct DB access │       │
        └──────────┬──────────┘       │
                   │                   │
        ┌──────────▼──────────────────▼──────────┐
        │        UserServer                       │
        │        http://172.24.194.6              │
        │   - User authentication                 │
        │   - Database hosting                    │
        │   - File storage                        │
        └─────────────────────────────────────────┘
```

### Server Roles

| Server | Responsibilities | Database Access |
|--------|-----------------|---------------|
| **ItemsServer** | Item operations (CRUD) | Direct |
| **UserServer** | User auth, database hosting, file storage | Direct |
| **Frontend** | UI rendering, API client | API only |

## Network Configuration

**Deployment Mode**: Staging  
**Database Location**: UserServer (172.24.194.6)

### Server URLs
- **ItemsServer API**: `http://172.24.194.6/Lostnfound/ItemsServer/api`
- **UserServer API**: `http://172.24.194.6/Lostnfound/UserServer/api`
- **Frontend UI**: `http://172.24.14.184/Lostnfound/Frontend`

### Database
- **Host**: 172.24.194.6
- **Name**: lostfound
- **Access**: ItemsServer and UserServer have direct access; Frontend uses APIs only

## Core Features

### User Features
- Report lost/found items with photos
- Search and filter items
- User dashboard to manage items
- Email contact system
- Secure authentication

### Admin Features
- Admin dashboard with system overview
- User management
- Item moderation
- System statistics
- Health monitoring

## Database Schema

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

## Inter-Server Communication

All servers communicate via HTTP REST API using `makeAPIRequest()` function.

### Features
- Automatic retry logic (up to 3 attempts)
- Configurable timeouts
- JSON response support
- Comprehensive error handling
- CORS headers for cross-origin requests

### API Call Flow Example
```
User reports lost item on Frontend
  ↓
Frontend uploads image to ItemsServer/uploads/
  ↓
Frontend calls ItemsServer API: POST /add_item.php
  ↓
ItemsServer inserts item into database
  ↓
ItemsServer returns JSON response
  ↓
Frontend displays success message
```

## Project Structure

```
Lostnfound/
├── ItemsServer/
│   ├── api/
│   │   ├── add_item.php
│   │   ├── update_item.php
│   │   ├── delete_item.php
│   │   ├── get_all_items.php
│   │   ├── get_item.php
│   │   ├── get_user_items.php
│   │   └── health.php
│   ├── config.php
│   ├── deployment_config.php
│   └── db_setup.php
│
├── UserServer/
│   ├── api/
│   │   ├── register_user.php
│   │   ├── verify_user.php
│   │   ├── get_all_users.php
│   │   ├── get_user_items.php
│   │   ├── toggle_admin.php
│   │   └── health.php
│   ├── config.php
│   ├── deployment_config.php
│   └── uploads/
│
├── Frontend/
│   ├── api/
│   │   └── health.php
│   ├── assets/
│   │   ├── style.css
│   │   ├── script.js
│   │   ├── logo.webp
│   │   └── favicon.svg
│   ├── index.php
│   ├── items.php
│   ├── report_lost.php
│   ├── report_found.php
│   ├── user_login.php
│   ├── user_register.php
│   ├── user_dashboard.php
│   ├── admin_dashboard.php
│   ├── edit_item.php
│   ├── config.php
│   ├── api_client.php
│   └── deployment_config.php
│
└── README.md
```

## Installation & Setup

### Prerequisites
- PHP 8.0+
- MySQL/MariaDB 5.7+
- Apache/Nginx with PHP support
- cURL extension
- GD/Imagick (optional)

### Setup Steps

1. **Database Setup** (on UserServer)
   ```bash
   php ItemsServer/db_setup.php
   mkdir ItemsServer/uploads && chmod 755 ItemsServer/uploads
   ```

2. **Configure Deployment**
   ```bash
   php deploy.php
   ```

3. **Set Permissions**
   ```bash
   chmod -R 755 ItemsServer/uploads/
   ```

4. **Test Health Endpoints**
   - ItemsServer: `http://172.24.194.6/Lostnfound/ItemsServer/api/health.php`
   - UserServer: `http://172.24.194.6/Lostnfound/UserServer/api/health.php`
   - Frontend: `http://172.24.14.184/Lostnfound/Frontend/health.php`

## Security Features

- **Password Security**: Bcrypt hashing via `password_hash()`
- **SQL Injection Protection**: `mysqli_real_escape_string()` on all inputs
- **XSS Prevention**: `htmlspecialchars()` on outputs
- **Email Validation**: `filter_var()` with FILTER_VALIDATE_EMAIL
- **File Upload Validation**: Type and size checks
- **Session-Based Auth**: Secure server-side sessions
- **CORS Headers**: Controlled cross-origin access
- **Method Validation**: Strict HTTP method checking

## API Documentation

### ItemsServer Endpoints

#### POST `/api/add_item.php`
Create a new item.

**Parameters**: `user_id`, `title`, `description`, `type`, `location`, `contact`, `image_filename`

**Response**:
```json
{
  "success": true,
  "item_id": 123,
  "message": "Item added successfully"
}
```

#### GET `/api/get_all_items.php`
Retrieve items with optional filtering.

**Parameters**: `type` (optional), `search` (optional)

**Response**:
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

### UserServer Endpoints

#### POST `/api/register_user.php`
Register a new user.

**Parameters**: `username`, `email`, `password`

**Response**:
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
Authenticate a user.

**Parameters**: `username`, `password`

**Response**:
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

## Design & UI

### Design Philosophy
- Clean and professional modern design
- User-friendly with intuitive navigation
- Responsive mobile-first approach
- Accessible with semantic HTML

### Color Scheme
- **Primary**: #2563eb (Blue)
- **Success**: #10b981 (Green) 
- **Error**: #ef4444 (Red)
- **Neutral**: Grayscale palette

### Typography
- **Font**: System fonts (-apple-system, Segoe UI, Roboto)
- **Base Size**: 16px
- **Line Height**: 1.7

### Responsive Breakpoints
- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: < 768px

## Troubleshooting

### Common Issues

**"Frontend cannot connect directly to the database"**  
Expected behavior. Frontend must use API calls via `makeAPIRequest()`.

**"Image not displaying"**  
- Check `ItemsServer/uploads/` directory exists and is writable
- Verify image path in database matches actual file
- Check `UPLOADS_BASE_URL` in deployment_config.php

**"API request timeout"**  
- Increase timeout in `makeAPIRequest()` options
- Check server connectivity (firewall, network)
- Verify target server is running

**"Session not persisting"**  
- Ensure `session_start()` is called in config.php
- Verify PHP session directory is writable
- Check for SameSite cookie issues

### Health Monitoring
- ItemsServer: `/api/health.php`
- UserServer: `/api/health.php`
- Frontend: `/health.php`

## User Roles

### Regular Users
- Register and log in
- Report lost/found items
- Edit/delete own items
- Search and browse items
- Contact other users

### Administrators
- All regular user permissions
- View all users
- Promote/demote admin status
- Delete any item
- View system statistics
- Access admin dashboard

## License & Credits

**Name**: University Lost & Found Portal  
**Version**: 2.0  
**Architecture**: Distributed Three-Server System  

### Technologies
- Backend: PHP 8+ with mysqli
- Database: MySQL/MariaDB
- Frontend: Vanilla JavaScript, Custom CSS
- Communication: cURL for HTTP requests
- Authentication: Session-based with bcrypt
- File Handling: PHP file uploads

## Future Enhancements

- Email notifications for matches
- Image compression and optimization
- Advanced search with filters
- User reputation system
- Mobile app (React Native)
- Real-time notifications
- Multi-language support
- Dark mode
- Export reports to PDF
- University ID integration

## Support

For issues or questions:
1. Check health endpoints
2. Review server logs
3. Verify deployment configuration
4. Check inter-server connectivity

---

**Built for campus communities**
