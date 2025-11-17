# University Lost & Found Portal - Technical Analysis

## Table of Contents
1. [System Architecture](#system-architecture)
2. [Data Transmission](#data-transmission)
3. [Data Validation](#data-validation)
4. [Technical Questions and Answers](#technical-questions-and-answers)
5. [Why Not Use Fetch/Async-Await](#why-not-use-fetchasync-await)
6. [Why Use cURL](#why-use-curl)

## System Architecture

### Three-Tier Architecture
The website follows a three-tier architecture with clear separation of concerns:
- **Frontend Layer**: PHP-based web interface that users interact with
- **ItemsServer Layer**: Dedicated server handling item-related operations
- **UserServer Layer**: Dedicated server handling user authentication and management

### Communication Mechanisms

#### Frontend to Backend Communication:
- **HTTP API Calls**: The frontend communicates with backend servers exclusively through HTTP API requests using cURL
- **Custom API Client**: Implemented in `makeAPIRequest()` function with retry logic, timeout management, and error handling
- **JSON Data Exchange**: All data is serialized as JSON for transmission between layers
- **CORS Handling**: Proper CORS headers are set to allow cross-origin requests

#### Backend to Database Communication:
- **Direct MySQL Connections**: Both ItemsServer and UserServer connect directly to MySQL databases using `mysqli_connect()`
- **SQL Queries**: Data is retrieved and stored using prepared SQL statements

#### Inter-Server Communication:
- **UserServer to ItemsServer**: UserServer makes API calls to ItemsServer for item-related operations using the same `makeAPIRequest()` mechanism

### Data Flow Patterns

#### Reading Data (e.g., Viewing Items):
1. User requests `/Frontend/items.php`
2. Frontend makes GET request to `ItemsServer/api/get_all_items.php`
3. ItemsServer queries MySQL database directly
4. ItemsServer returns JSON response with items data
5. Frontend processes JSON and renders HTML

#### Writing Data (e.g., Reporting Lost Item):
1. User submits form on `/Frontend/report_lost.php`
2. Frontend makes POST request to `ItemsServer/api/add_item.php`
3. ItemsServer validates data and inserts into MySQL database
4. ItemsServer returns JSON confirmation
5. Frontend redirects or displays success message

### Security Measures
- **Session Management**: PHP sessions track user authentication state
- **Input Validation**: Data is sanitized before database insertion
- **CORS Headers**: Control cross-origin resource sharing
- **Error Handling**: Structured error responses with appropriate HTTP status codes

### Technical Protocols
- **HTTP/HTTPS**: Primary communication protocol
- **JSON**: Data serialization format
- **REST-like API**: Stateless operations with standard HTTP methods
- **cURL**: HTTP client library for inter-service communication

This architecture ensures loose coupling between components while maintaining clear data flow paths and enabling horizontal scalability of individual services.

## Data Transmission

### 1. Three-Tier Architecture
The website follows a three-tier architecture with clear separation of concerns:
- **Frontend Layer**: PHP-based web interface that users interact with
- **ItemsServer Layer**: Dedicated server handling item-related operations
- **UserServer Layer**: Dedicated server handling user authentication and management

### 2. Communication Mechanisms

#### Frontend to Backend Communication:
- **HTTP API Calls**: The frontend communicates with backend servers exclusively through HTTP API requests using cURL
- **Custom API Client**: Implemented in `makeAPIRequest()` function with retry logic, timeout management, and error handling
- **JSON Data Exchange**: All data is serialized as JSON for transmission between layers
- **CORS Handling**: Proper CORS headers are set to allow cross-origin requests

#### Backend to Database Communication:
- **Direct MySQL Connections**: Both ItemsServer and UserServer connect directly to MySQL databases using `mysqli_connect()`
- **SQL Queries**: Data is retrieved and stored using prepared SQL statements

#### Inter-Server Communication:
- **UserServer to ItemsServer**: UserServer makes API calls to ItemsServer for item-related operations using the same `makeAPIRequest()` mechanism

### 3. Data Flow Patterns

#### Reading Data (e.g., Viewing Items):
1. User requests `/Frontend/items.php`
2. Frontend makes GET request to `ItemsServer/api/get_all_items.php`
3. ItemsServer queries MySQL database directly
4. ItemsServer returns JSON response with items data
5. Frontend processes JSON and renders HTML

#### Writing Data (e.g., Reporting Lost Item):
1. User submits form on `/Frontend/report_lost.php`
2. Frontend makes POST request to `ItemsServer/api/add_item.php`
3. ItemsServer validates data and inserts into MySQL database
4. ItemsServer returns JSON confirmation
5. Frontend redirects or displays success message

### 4. Security Measures
- **Session Management**: PHP sessions track user authentication state
- **Input Validation**: Data is sanitized before database insertion
- **CORS Headers**: Control cross-origin resource sharing
- **Error Handling**: Structured error responses with appropriate HTTP status codes

### 5. Technical Protocols
- **HTTP/HTTPS**: Primary communication protocol
- **JSON**: Data serialization format
- **REST-like API**: Stateless operations with standard HTTP methods
- **cURL**: HTTP client library for inter-service communication

This architecture ensures loose coupling between components while maintaining clear data flow paths and enabling horizontal scalability of individual services.

## Data Validation

### 1. Client-Side Validation
The website implements JavaScript-based form validation to provide immediate feedback to users:

- **Basic Field Validation**: Checks for empty required fields (title, description, location, contact)
- **Email Format Validation**: Uses regex patterns to validate email addresses
- **File Validation**: For image uploads, validates file type (JPG, PNG, GIF) and size limits (5MB)
- **User Registration Validation**: Checks password length and confirms password matching

Example from `Frontend/assets/script.js`:
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
    // ... more validation checks
}
```

### 2. Server-Side Validation
All data is validated again on the server side for security:

#### Input Sanitization:
- **Email Validation**: Uses PHP's `filter_var($email, FILTER_VALIDATE_EMAIL)`
- **SQL Injection Prevention**: Escapes all inputs with `mysqli_real_escape_string()`
- **Type Validation**: Ensures item types are only "lost" or "found"
- **Numeric Validation**: Checks user IDs and other numeric values with `is_numeric()`

#### Required Field Checks:
- All API endpoints verify required fields are present before processing
- Returns 400 HTTP status codes with descriptive error messages for missing data

Example from `ItemsServer/api/add_item.php`:
```php
if (empty($user_id) || empty($title) || empty($description) || empty($type) || empty($location) || empty($contact)) {
    sendJSONResponse(['error' => 'Please fill all required fields'], 400);
}

if (!in_array($type, ['lost', 'found'])) {
    sendJSONResponse(['error' => 'Invalid item type'], 400);
}
```

### 3. Authentication Validation
User authentication has specific validation:

- **Password Security**: Uses bcrypt hashing via `password_hash()` with `PASSWORD_DEFAULT`
- **Duplicate Prevention**: Checks that usernames and emails don't already exist
- **Session Validation**: Verifies user is logged in before allowing certain operations

Example from `UserServer/api/register_user.php`:
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(['error' => 'Invalid email format'], 400);
}

// Check if user exists
$check_sql = "SELECT id FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' 
              OR email = '" . mysqli_real_escape_string($conn, $email) . "'";
```

### 4. File Upload Validation
Image uploads are validated for security:

- **File Type Checking**: Only allows JPEG, PNG, GIF files
- **File Size Limits**: Restricts uploads to 5MB maximum
- **Storage Validation**: Ensures uploaded files are stored in the correct directory

### 5. API Request Validation
All API endpoints validate:

- **HTTP Method**: Only allows appropriate methods (POST, GET, etc.)
- **Request Origin**: Checks for proper CORS headers
- **Data Integrity**: Validates data structure and content before database operations

### 6. Ownership Validation
For item modification operations:

- **User Ownership Verification**: Ensures users can only modify their own items
- **Permission Checks**: Admin users have extended privileges

Example from `ItemsServer/api/update_item.php`:
```php
// Check if item exists and belongs to user
$check_sql = "SELECT * FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) === 0) {
    mysqli_close($conn);
    sendJSONResponse(['error' => 'Item not found or access denied'], 404);
}
```

### 7. Error Handling and Response Validation
The system implements consistent error handling:

- **Proper HTTP Status Codes**: 400 for bad requests, 404 for not found, 500 for server errors
- **Structured Error Responses**: JSON responses with error messages
- **No Sensitive Data Exposure**: Error messages don't reveal database structure

This multi-layered validation approach ensures data integrity and security throughout the application, with validation occurring at both client and server levels to provide immediate feedback while maintaining robust security.

## Technical Questions and Answers

### 1. Question: How does the system implement secure password handling?
**Answer:** The system uses PHP's built-in `password_hash()` function with the `PASSWORD_DEFAULT` algorithm (bcrypt) for password hashing. During registration, passwords are hashed before storage:
``php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```
For authentication, `password_verify()` compares plaintext passwords with stored hashes:
``php
if (password_verify($plaintext_password, $hashed_password)) {
    // Authentication successful
}
```
This approach provides salted, slow hashing that's resistant to brute-force and rainbow table attacks.

### 2. Question: Explain the database connection architecture and security measures.
**Answer:** The system uses direct MySQL connections with `mysqli_connect()` in ItemsServer and UserServer. Key security measures include:
- Centralized configuration in `deployment_config.php` with consistent credentials
- Input sanitization using `mysqli_real_escape_string()` for all database queries
- Prepared statements pattern through manual escaping (though not using true prepared statements)
- Database hosted on UserServer (172.24.58.54) with all services connecting remotely using the same credentials

### 3. Question: How does the system prevent SQL injection attacks?
**Answer:** The system implements manual input escaping using `mysqli_real_escape_string()` for all user inputs before database insertion:
```php
$username_escaped = mysqli_real_escape_string($conn, $username);
$email_escaped = mysqli_real_escape_string($conn, $email);
$sql = "INSERT INTO users (username, email, password) VALUES ('$username_escaped', '$email_escaped', '$hashed_password')";
```
While effective, a more robust approach would use true prepared statements with parameterized queries.

### 4. Question: Describe the inter-service communication mechanism.
**Answer:** Services communicate via HTTP REST-like APIs using a custom `makeAPIRequest()` function that wraps cURL with retry logic:
```php
function makeAPIRequest($url, $data = [], $method = 'POST', $options = []) {
    $ch = curl_init();
    // Configuration with retry logic, timeout handling, and error management
    // Supports GET, POST, PUT, DELETE methods
    // Automatic JSON encoding/decoding
}
```
Frontend communicates with ItemsServer and UserServer through this mechanism, while UserServer can also call ItemsServer APIs for composite operations.

### 5. Question: How does the system handle Cross-Origin Resource Sharing (CORS)?
**Answer:** Each API endpoint implements CORS headers through a `setCORSHeaders()` function:
```php
function setCORSHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
```
This allows cross-origin requests from the frontend while maintaining security through controlled header access.

### 6. Question: Explain the session management and authentication flow.
**Answer:** The system uses PHP sessions with a role-based approach:
- Sessions initiated with `session_start()` in config files
- User data stored in `$_SESSION` variables (`user_id`, `username`, `is_admin`)
- Authentication verification through `isUserLoggedIn()` and `requireUser()` functions
- Session destruction via `session_destroy()` during logout
- Admin privileges checked with `isCurrentUserAdmin()`

The flow: User login → Verify credentials via UserServer API → Store user data in session → Validate session on protected pages.

### 7. Question: How does the system validate and handle file uploads securely?
**Answer:** File upload validation includes:
- MIME type checking: `['image/jpeg', 'image/png', 'image/gif', 'image/webp']`
- File size limits: 15MB maximum (`$max_size = 15 * 1024 * 1024`)
- Storage in centralized `ItemsServer/uploads/` directory
- Filename sanitization and unique naming to prevent overwrites
- Validation in both frontend JavaScript and backend PHP:
```php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($_FILES['image']['type'], $allowed_types)) {
    // Reject upload
}
if ($_FILES['image']['size'] > $max_size) {
    // Reject upload
}
```

### 8. Question: What architectural pattern does this system follow and why?
**Answer:** The system follows a Service-Oriented Architecture (SOA) with three distinct services:
- **Frontend**: Presentation layer handling UI and user interactions
- **ItemsServer**: Dedicated service for item management operations
- **UserServer**: Dedicated service for user authentication and management

Benefits:
- Separation of concerns and loose coupling
- Independent scalability of services
- Technology specialization per service
- Improved maintainability and fault isolation
- Clear API boundaries enabling independent development

### 9. Question: How does the system handle error responses and logging?
**Answer:** The system implements structured error handling:
- JSON responses with consistent format: `{'error': 'message'}` or `{'success': true, 'data': ...}`
- Appropriate HTTP status codes (400, 404, 405, 500)
- Error logging using `error_log()` for debugging:
```php
error_log("[APIRequest] Success: $method $url | HTTP $http_code | {$elapsed_time}ms");
```
- User-friendly error messages without exposing sensitive system information
- Retry mechanisms with exponential backoff for transient failures

### 10. Question: Explain the data flow when a user reports a lost item.
**Answer:** The data flow is:
1. User submits form on `Frontend/report_lost.php`
2. Frontend validates data client-side with JavaScript
3. Form data POSTed to same PHP file for server-side processing
4. Frontend makes API call to `ItemsServer/api/add_item.php` using `makeAPIRequest()`
5. ItemsServer validates inputs and escapes data with `mysqli_real_escape_string()`
6. ItemsServer inserts record into MySQL database
7. ItemsServer returns JSON response with success/failure
8. Frontend processes response and redirects or shows confirmation

This maintains the service-oriented architecture while ensuring data validation at multiple layers.

## Why Not Use Fetch/Async-Await

### 1. Server-Side Architecture Choice
The project is built primarily with **server-side rendering using PHP**, not a client-side JavaScript framework. The frontend pages are PHP files that generate HTML on the server before sending it to the browser. This means:

- Data fetching happens on the server-side during PHP execution, not in the browser
- The JavaScript in `script.js` is primarily for UI enhancements and basic form validation
- Complex asynchronous operations are handled by PHP's synchronous `curl` functions

### 2. PHP Backend Processing
As seen in `Frontend/items.php` lines 15-18:
```php
$api_response = makeAPIRequest(ITEMSSERVER_URL . '/get_all_items.php', [
    'type' => $filter !== 'all' ? $filter : '',
    'search' => $search
], 'GET', ['return_json' => true]);
```

This demonstrates that API calls are made server-side using PHP's `makeAPIRequest()` function (which uses cURL) during page generation, not asynchronously in the browser with fetch.

### 3. Simplicity and Compatibility
The project was designed for:
- **Broader compatibility** with older browsers that might not fully support modern JavaScript features
- **Simpler debugging** with synchronous server-side processing
- **Traditional web development patterns** that many developers are familiar with

## Why This Architecture Was Chosen

### 1. Service-Oriented Architecture (SOA)
From `README.md`, the project uses a three-tier distributed architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend                                  │
│         - User Interface & Rendering                         │
│         - API client only (no direct database access)        │
└──────────────────┬──────────────────┬───────────────────────┘
                   │                   │
        ┌──────────▼──────────┐       │
        │   ItemsServer       │       │
        │  - Item CRUD ops    │       │
        └──────────┬──────────┘       │
                   │                   │
        ┌──────────▼──────────────────▼──────────┐
        │        UserServer                       │
        │   - User authentication                 │
        │   - Database hosting                    │
        │   - File storage                        │
        └─────────────────────────────────────────┘
```

### 2. Clear Separation of Concerns
Each service has a specific responsibility:
- **ItemsServer**: Handles all item-related operations (CRUD)
- **UserServer**: Manages user authentication and hosts the database
- **Frontend**: Provides UI rendering and acts as an API client only

### 3. Security Benefits
- **Database Isolation**: Only backend services connect directly to the database
- **API Gateway Pattern**: Frontend cannot directly access the database, only through controlled API endpoints
- **Centralized Authentication**: User management is centralized in UserServer

### 4. Scalability and Maintainability
- **Independent Scaling**: Each service can be scaled independently based on demand
- **Technology Flexibility**: Different services could potentially use different technologies
- **Fault Isolation**: Issues in one service don't necessarily affect others
- **Team Development**: Different teams can work on different services

### 5. Network and Deployment Considerations
The architecture was designed for deployment across multiple servers:
- ItemsServer: 172.24.194.6
- UserServer: 172.24.194.6 (also hosts database)
- Frontend: 172.24.14.184

This distributed approach allows for:
- **Load Distribution**: Different servers handle different workloads
- **Redundancy**: Critical services can have backup instances
- **Maintenance**: Individual services can be updated without affecting others

### 6. Robust Communication Layer
The custom `makeAPIRequest()` function in `Frontend/config.php` provides:
- **Retry Logic**: Automatic retries on failed requests
- **Timeout Management**: Configurable connection and response timeouts
- **Error Handling**: Comprehensive error handling and logging
- **Protocol Support**: Support for multiple HTTP methods (GET, POST, PUT, DELETE)

This approach provides more reliability than basic fetch calls, especially in a distributed system where network issues might occur.

In summary, the choice to use server-side PHP with cURL instead of client-side fetch/async/await, and the service-oriented architecture, was driven by security considerations, scalability requirements, deployment flexibility, and the need for robust inter-service communication in a distributed system.

## Why Use cURL

### 1. HTTP Client Requirements
The project needed a robust HTTP client to handle communication between distributed services. cURL provides:
- **Comprehensive Protocol Support**: HTTP/HTTPS, FTP, and other protocols
- **Fine-grained Control**: Complete control over HTTP requests, headers, timeouts, and options
- **Reliability**: Mature, well-tested library with consistent behavior

### 2. Advanced Features Not Available with Basic HTTP Functions
As seen in the `makeAPIRequest()` function in `Frontend/config.php` and `UserServer/config.php`, cURL enables:
- **Retry Logic**: Automatic retries on failed requests (lines 61-199 in Frontend/config.php)
- **Timeout Management**: Separate connection and response timeouts
- **Custom Headers**: Adding User-Agent, Content-Type, Accept headers
- **HTTP Method Support**: GET, POST, PUT, DELETE methods
- **SSL Configuration**: Ability to disable SSL verification for internal communication
- **Redirect Handling**: Automatic following of redirects with max redirect limits
- **Response Information**: Access to HTTP status codes, content types, and timing data

### 3. Error Handling and Diagnostics
cURL provides detailed error information that's essential for debugging distributed systems:
```php
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_errno = curl_errno($ch);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
```

This allows the system to distinguish between network errors, HTTP errors, and application-level errors.

### 4. Cross-Server Communication
The architecture requires communication between:
- Frontend → ItemsServer
- Frontend → UserServer
- UserServer → ItemsServer (proxy requests)

cURL provides the flexibility needed for these different communication patterns:
```php
// From Frontend/config.php - line 273
$health_url = $server_url . '/health.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $health_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
```

### 5. Consistent API Request Handling
Both Frontend and UserServer implement the same `makeAPIRequest()` pattern using cURL, providing:
- **Unified Error Handling**: Consistent error responses across services
- **Retry Mechanism**: Exponential backoff for transient failures
- **JSON Response Parsing**: Automatic JSON decoding when needed
- **Structured Logging**: Detailed request/response logging for debugging

### 6. Performance Considerations
cURL offers performance benefits:
- **Connection Reuse**: Options for persistent connections
- **Compression Support**: Automatic handling of gzip/deflate encoding
- **IP Resolution Control**: Ability to force IPv4/IPv6 resolution
- **Efficient Memory Usage**: Stream-based response handling

### 7. Compatibility and Availability
cURL was chosen because:
- **PHP Extension**: cURL is a standard PHP extension available in most hosting environments
- **No External Dependencies**: Unlike some HTTP libraries, cURL doesn't require additional packages
- **Wide Server Support**: Works consistently across different server configurations

### 8. Enterprise-Grade Features
The implementation leverages cURL's enterprise features:
- **Certificate Management**: SSL certificate validation options
- **Authentication Support**: Basic auth, digest auth, and custom auth headers
- **Proxy Support**: Ability to route requests through proxies if needed
- **Cookie Handling**: Automatic cookie management for session persistence

### 9. Monitoring and Debugging
cURL enables detailed monitoring:
```php
// From Frontend/config.php - line 134-136
$start_time = microtime(true);
$response = curl_exec($ch);
$elapsed_time = round((microtime(true) - $start_time) * 1000, 2);
error_log("[APIRequest] Success: $method $url | HTTP $http_code | {$elapsed_time}ms");
```

This provides performance metrics and request tracing essential for a distributed system.

In summary, cURL was chosen because it provides the robustness, flexibility, and enterprise features required for reliable inter-service communication in a distributed architecture, while being widely available and well-supported across different environments.

## Responsive Design and Item Display

### How the Website Achieves Responsiveness

The University Lost & Found Portal implements a comprehensive responsive design using modern CSS techniques:

#### 1. Mobile-First Approach with Media Queries
The CSS uses multiple breakpoints to adapt the layout for different screen sizes:
- **Large screens** (default): Full desktop experience
- **Tablet screens** (max-width: 1024px): Adjusted grid layouts and spacing
- **Mobile screens** (max-width: 768px): Collapsed navigation, single-column layouts
- **Small mobile screens** (max-width: 480px): Further optimizations for tiny screens

#### 2. Flexible Grid System
The design uses CSS Grid and Flexbox for adaptive layouts:

**Statistics Section** (lines 134-147 in items.php):
```html
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
```

**Items Grid** (lines 370-374 in style.css):
```css
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}
```

These grid systems automatically adjust the number of columns based on available space:
- On large screens: 3-4 columns of item cards
- On tablets: 2 columns
- On mobile: 1 column (single file)

#### 3. Responsive Navigation
The navigation transforms from a horizontal menu to a collapsible mobile menu:
- **Desktop**: Horizontal navigation bar with all menu items visible
- **Mobile**: Hamburger menu that expands to a vertical dropdown

Implementation in style.css (lines 639-711):
```css
/* Show hamburger menu */
.menu-toggle {
    display: none;
}

@media (max-width: 768px) {
    /* Show hamburger menu */
    .menu-toggle {
        display: flex;
    }
    
    /* Hide navigation by default on mobile */
    nav ul {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    /* Show menu when active */
    nav.active ul {
        max-height: 600px;
        opacity: 1;
        padding: 0.5rem 0;
    }
}
```

#### 4. Flexible Typography and Spacing
The design uses relative units (rem, em) and adjusts spacing based on screen size:
- Padding and margins reduce on smaller screens
- Font sizes adjust for better readability
- Line heights optimized for different devices

#### 5. Image Responsiveness
Images are designed to be fully responsive:
```css
.item-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
```

### How Items Are Displayed on Pages

#### 1. Item Card Structure
Each item is displayed in a consistent card format with the following elements:

**Visual Hierarchy**:
- **Header**: Item type badge (Lost/Found) positioned in top-right corner
- **Image**: Full-width image container with placeholder for items without images
- **Title**: Prominent heading with icon
- **Description**: Truncated text with "Read more" functionality
- **Details**: Location and contact information with icons
- **Metadata**: Creation date and time

#### 2. Item Grid Layout
The items are displayed in a responsive grid system:
- **Desktop**: 3-4 columns with 280px minimum width
- **Tablet**: 2 columns
- **Mobile**: Single column

Implementation in style.css (lines 1257-1262):
```css
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}
```

#### 3. Interactive Elements
Each item card includes interactive features:
- **Hover Effects**: Cards lift slightly and gain shadow on hover
- **Image Modal**: Clicking images opens them in a full-screen modal
- **Truncated Descriptions**: Long descriptions are truncated with "Read more" option

JavaScript implementation in script.js (lines 75-112):
```javascript
// Read More functionality for item descriptions
function initializeReadMore() {
    var descriptions = document.querySelectorAll('.item-description');

    descriptions.forEach(function (desc) {
        var fullText = desc.textContent.trim();

        // Check if text is longer than 3 lines (approximately 200 characters)
        if (fullText.length > 200) {
            desc.classList.add('truncated');
            desc.setAttribute('data-full-text', fullText);

            // Create read more button
            var readMoreBtn = document.createElement('span');
            readMoreBtn.className = 'read-more-btn';
            readMoreBtn.textContent = 'Read more...';
            readMoreBtn.onclick = function () {
                toggleDescription(desc, readMoreBtn);
            };

            desc.parentNode.insertBefore(readMoreBtn, desc.nextSibling);
        }
    });
}
```

#### 4. Visual Indicators
The design uses clear visual indicators:
- **Color Coding**: Red for lost items, green for found items
- **Icons**: SVG icons for location, contact, and time information
- **Typography**: Clear hierarchy with headings, body text, and metadata

#### 5. Empty States
When no items are found, the system displays a helpful message:
```html
<div style="text-align: center; padding: 3rem;">
    <h3>No items found</h3>
    <p>Try adjusting your search criteria or <a href="items.php">view all items</a></p>
</div>
```

#### 6. Filtering and Search
The items page includes filtering capabilities:
- **Search Box**: Text search across titles, descriptions, and locations
- **Type Filter**: Dropdown to show only lost or found items
- **Real-time Updates**: Filters applied through page reload with query parameters

This responsive design ensures the website works well on all devices while maintaining a consistent and user-friendly experience for browsing lost and found items.