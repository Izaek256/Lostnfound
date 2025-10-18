<?php
/**
 * Lost and Found Portal - University
 * Database Setup Script
 * 
 * This script initializes the database and creates necessary tables.
 * Run this file once to set up the complete database structure.
 * 
 * Access: http://localhost/lostfound/setup.php
 * 
 * What it does:
 * 1. Creates the database if it doesn't exist
 * 2. Creates the 'items' table with all required columns
 * 3. Optionally inserts sample data for testing
 * 4. Creates the 'uploads' directory for storing images
 */

// Database configuration - same settings as in db.php
$host = 'localhost';        // Database server
$username = 'root';         // MySQL username
$password = 'isaacK@12345';         // MySQL password
$database = 'lostfound_db'; // Database name

// Variable to track setup completion status
$setupComplete = false;

// Array to store messages to display to the user
$messages = [];

// Check if the setup form was submitted
// $_SERVER['REQUEST_METHOD'] tells us if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    
    // Connect to MySQL server without selecting a database first
    // We need to do this because the database might not exist yet
    $conn = mysqli_connect($host, $username, $password);
    
    // Check if connection was successful
    if (!$conn) {
        // If connection fails, add error message to display
        $messages[] = "❌ Error: Connection failed - " . mysqli_connect_error();
    } else {
        
        // Create database if it doesn't exist
        // The IF NOT EXISTS clause prevents errors if database already exists
        $sql = "CREATE DATABASE IF NOT EXISTS $database";
        
        // mysqli_query() executes an SQL query
        if (mysqli_query($conn, $sql)) {
            $messages[] = "✅ Database '$database' created successfully!";
        } else {
            $messages[] = "❌ Error creating database: " . mysqli_error($conn);
        }
        
        // Select the database we just created
        // This is like telling MySQL "use this database for the next queries"
        mysqli_select_db($conn, $database);
        
        // Create users table for user accounts
        // This table stores registered user information
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,              -- Unique user ID
            username VARCHAR(50) NOT NULL UNIQUE,           -- Username (unique)
            email VARCHAR(100) NOT NULL UNIQUE,             -- Email address (unique)
            password VARCHAR(255) NOT NULL,                 -- Hashed password
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Registration date
        )";
        
        if (mysqli_query($conn, $sql)) {
            $messages[] = "✅ Table 'users' created successfully!";
        } else {
            $messages[] = "❌ Error creating users table: " . mysqli_error($conn);
        }
        
        // Create the items table with all required columns
        // This table will store all lost and found item reports
        $sql = "CREATE TABLE IF NOT EXISTS items (
            id INT AUTO_INCREMENT PRIMARY KEY,              -- Unique ID for each item
            user_id INT DEFAULT NULL,                       -- ID of user who posted (NULL for guests)
            title VARCHAR(100) NOT NULL,                    -- Item name/title
            description TEXT NOT NULL,                      -- Detailed description
            type ENUM('lost', 'found') NOT NULL,           -- Whether item is lost or found
            location VARCHAR(100) NOT NULL,                 -- Where item was lost/found
            contact VARCHAR(100) NOT NULL,                  -- Email for contact
            image VARCHAR(255) DEFAULT NULL,                -- Image filename (optional)
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When report was created
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE  -- Link to user
        )";
        
        // Execute the CREATE TABLE query for items
        if (mysqli_query($conn, $sql)) {
            $messages[] = "✅ Table 'items' created successfully!";
        } else {
            $messages[] = "❌ Error creating items table: " . mysqli_error($conn);
        }
        
        // Create deletion_requests table for user deletion requests
        // Users request deletion, admin approves
        $sql = "CREATE TABLE IF NOT EXISTS deletion_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,                  -- Unique request ID
            item_id INT NOT NULL,                               -- Item to be deleted
            user_id INT NOT NULL,                               -- User requesting deletion
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',  -- Request status
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,     -- When requested
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        if (mysqli_query($conn, $sql)) {
            $messages[] = "✅ Table 'deletion_requests' created successfully!";
        } else {
            $messages[] = "❌ Error creating deletion_requests table: " . mysqli_error($conn);
        }
        
        // Insert sample data if user checked the checkbox
        if (isset($_POST['sample_data'])) {
            
            // Array of sample items to insert for testing
            // Each item has all the required fields
            $sampleItems = [
                [
                    'title' => 'Black iPhone 13',
                    'description' => 'Black iPhone 13 with a clear case. Has a small scratch on the back. Lost near the library.',
                    'type' => 'lost',
                    'location' => 'Main Library, 2nd Floor',
                    'contact' => 'student1@university.edu'
                ],
                [
                    'title' => 'Blue Backpack',
                    'description' => 'Navy blue Jansport backpack with laptop compartment. Contains textbooks and notebooks.',
                    'type' => 'found',
                    'location' => 'Student Center Cafeteria',
                    'contact' => 'student2@university.edu'
                ],
                [
                    'title' => 'Silver MacBook Pro',
                    'description' => '13-inch MacBook Pro with university stickers. In a black laptop sleeve.',
                    'type' => 'lost',
                    'location' => 'Engineering Building, Room 205',
                    'contact' => 'student3@university.edu'
                ],
                [
                    'title' => 'Red Water Bottle',
                    'description' => 'Hydro Flask water bottle, red color, 32oz. Has dents from regular use.',
                    'type' => 'found',
                    'location' => 'Gym Locker Room',
                    'contact' => 'student4@university.edu'
                ]
            ];
            
            // Loop through each sample item and insert it into the database
            foreach ($sampleItems as $item) {
                // Build the INSERT query
                // We use mysqli_real_escape_string() to prevent SQL injection
                // This function escapes special characters in the data
                $title = mysqli_real_escape_string($conn, $item['title']);
                $description = mysqli_real_escape_string($conn, $item['description']);
                $type = mysqli_real_escape_string($conn, $item['type']);
                $location = mysqli_real_escape_string($conn, $item['location']);
                $contact = mysqli_real_escape_string($conn, $item['contact']);
                
                $sql = "INSERT INTO items (title, description, type, location, contact) 
                        VALUES ('$title', '$description', '$type', '$location', '$contact')";
                
                // Execute the INSERT query
                mysqli_query($conn, $sql);
            }
            
            $messages[] = "✅ Sample data inserted successfully!";
        }
        
        // Check if uploads directory exists, create it if not
        // This directory will store uploaded item images
        $uploadsDir = 'uploads';
        if (!is_dir($uploadsDir)) {
            // mkdir() creates a new directory
            // 0755 sets the permissions (read/write/execute for owner, read/execute for others)
            if (mkdir($uploadsDir, 0755, true)) {
                $messages[] = "✅ Uploads directory created successfully!";
            } else {
                $messages[] = "⚠️ Could not create uploads directory. Please create it manually.";
            }
        } else {
            $messages[] = "✅ Uploads directory already exists!";
        }
        
        // Mark setup as complete
        $setupComplete = true;
        
        // Close the database connection
        mysqli_close($conn);
    }
}

// Check if database already exists by trying to connect to it
// This helps show appropriate messages to the user
$databaseExists = false;

// First connect to MySQL server WITHOUT selecting a database
$testConn = @mysqli_connect($host, $username, $password);

// If connection successful, check if database exists
if ($testConn) {
    // Check if the database exists
    $result = mysqli_query($testConn, "SHOW DATABASES LIKE '$database'");
    
    // If database exists, check if tables exist
    if ($result && mysqli_num_rows($result) > 0) {
        // Database exists, now select it
        mysqli_select_db($testConn, $database);
        
        // Check if the items table exists
        $result = mysqli_query($testConn, "SHOW TABLES LIKE 'items'");
        
        // If table exists, mark database as complete
        if ($result && mysqli_num_rows($result) > 0) {
            $databaseExists = true;
        }
    }
    
    // Close the test connection
    mysqli_close($testConn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - University Lost and Found Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🎓 University Lost & Found - Setup</h1>
            </div>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h2>🛠️ Database Setup</h2>
            
            <?php if ($databaseExists && !$setupComplete): ?>
                <div class="alert alert-success">
                    <strong>✅ Database Already Exists!</strong><br>
                    The database and table are already set up. You can start using the portal.
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="btn">Go to Portal</a>
                </div>
                
            <?php elseif ($setupComplete): ?>
                <div class="alert alert-success">
                    <strong>🎉 Setup Complete!</strong><br>
                    Your Lost and Found Portal is ready to use.
                </div>
                
                <?php foreach ($messages as $message): ?>
                    <div style="margin: 0.5rem 0; padding: 0.5rem; background: #f8f9fa; border-radius: 5px;">
                        <?php echo $message; ?>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="btn">Go to Portal</a>
                </div>
                
            <?php else: ?>
                <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                    This will create the database and table needed for the Lost and Found Portal.
                </p>
                
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-error">
                            <?php echo $message; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <form method="POST">
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                        <h3 style="color: #667eea; margin-bottom: 1rem;">What will be created:</h3>
                        <ul style="color: #666; padding-left: 2rem;">
                            <li>Database: <code>lostfound_db</code></li>
                            <li>Table: <code>users</code> for user accounts</li>
                            <li>Table: <code>items</code> with all required columns</li>
                            <li>Table: <code>deletion_requests</code> for deletion approvals</li>
                            <li>Uploads directory for images</li>
                        </ul>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 5px; margin-bottom: 2rem; border: 1px solid #ffeaa7;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="sample_data" value="1" style="margin-right: 0.5rem;">
                            <span>Include sample data for testing (recommended for first-time setup)</span>
                        </label>
                        <small style="color: #666; margin-left: 1.5rem; display: block; margin-top: 0.5rem;">
                            This will add 4 sample lost/found items to help you test the portal functionality.
                        </small>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" name="setup" class="btn">🚀 Setup Database</button>
                    </div>
                </form>
                
                <div style="background: #e7f3ff; padding: 1.5rem; border-radius: 10px; margin-top: 2rem; border: 1px solid #b3d9ff;">
                    <h4 style="color: #0066cc; margin-bottom: 1rem;">📋 Prerequisites Check:</h4>
                    <ul style="color: #666; padding-left: 2rem;">
                        <li>✅ XAMPP Apache server is running</li>
                        <li>✅ XAMPP MySQL server is running</li>
                        <li>✅ PHP files are in the correct directory</li>
                        <li>⚠️ Make sure you have database creation permissions</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- System Information -->
        <div class="form-container">
            <h2>📊 System Information</h2>
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>PHP Version:</strong><br>
                        <span style="color: #666;"><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div>
                        <strong>Server:</strong><br>
                        <span style="color: #666;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                    </div>
                    <div>
                        <strong>Upload Max Size:</strong><br>
                        <span style="color: #666;"><?php echo ini_get('upload_max_filesize'); ?></span>
                    </div>
                    <div>
                        <strong>Post Max Size:</strong><br>
                        <span style="color: #666;"><?php echo ini_get('post_max_size'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Next Steps -->
        <?php if ($setupComplete || $databaseExists): ?>
        <div class="form-container">
            <h2>🎯 Next Steps</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏠</div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">Visit Homepage</h4>
                    <p style="color: #666; margin-bottom: 1rem;">Start using the portal and see the dashboard.</p>
                    <a href="index.php" class="btn">Go to Homepage</a>
                </div>
                
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📢</div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">Test Reporting</h4>
                    <p style="color: #666; margin-bottom: 1rem;">Try reporting a lost or found item.</p>
                    <a href="report_lost.php" class="btn">Report Lost Item</a>
                </div>
                
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">👀</div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">Browse Items</h4>
                    <p style="color: #666; margin-bottom: 1rem;">View all reported items and test search.</p>
                    <a href="items.php" class="btn">View All Items</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal Setup</p>
    </footer>
</body>
</html>
