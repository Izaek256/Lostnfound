<?php
/**
 * Report Lost Item Page
 * 
 * This page allows users to report items they have lost.
 * It handles:
 * - Displaying the report form
 * - Processing form submissions
 * - Uploading images
 * - Inserting data into the database
 * - Showing recent lost items for reference
 */

// Include database connection
require_once 'db.php';

// Include user functions to check if user is logged in
require_once 'user_config.php';

// Require authentication - redirect to login if not logged in
requireUser();

// Get current user ID (guaranteed to exist since requireUser() passed)
$currentUserId = getCurrentUserId();

// Variable to store success or error messages
$message = '';

// Check if the form was submitted
// $_SERVER['REQUEST_METHOD'] === 'POST' means form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data from $_POST array
    // $_POST contains all form field values
    $title = $_POST['title'];             // Item title/name
    $description = $_POST['description']; // Detailed description
    $location = $_POST['location'];       // Where item was lost
    $contact = $_POST['contact'];         // Email for contact
    
    // Validate that all required fields are filled
    // empty() checks if a variable is empty
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $message = 'Please fill in all fields';
    } else {
        
        // Handle image upload
        // Images are optional, so we initialize as null
        $imageName = null;
        
        // Check if an image was uploaded
        // $_FILES contains information about uploaded files
        // error == 0 means upload was successful
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            
            // Directory where images will be stored
            $uploadsDir = 'uploads/';
            
            // Create uploads directory if it doesn't exist
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir);
            }
            
            // Get the file extension (jpg, png, etc.)
            // pathinfo() extracts information from the file path
            // PATHINFO_EXTENSION gets just the extension
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            // List of allowed image types for security
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
            
            // Check if uploaded file type is allowed
            // in_array() checks if a value exists in an array
            if (in_array($imageFileType, $allowedTypes)) {
                // Create a unique filename using uniqid() to avoid conflicts
                // uniqid() generates a unique ID based on current time
                $imageName = uniqid() . '.' . $imageFileType;
                
                // Move uploaded file from temporary location to uploads directory
                // move_uploaded_file() moves the file to permanent location
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadsDir . $imageName);
            }
        }
        
    // Prepare data for insertion by escaping special characters
    // mysqli_real_escape_string() prevents SQL injection attacks
    // It escapes characters like quotes that could break the SQL query
    $title = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);
    $location = mysqli_real_escape_string($conn, $location);
    $contact = mysqli_real_escape_string($conn, $contact);
    $imageName = mysqli_real_escape_string($conn, $imageName);

    // Get user ID (guaranteed to exist since user must be logged in)
    $userIdValue = "'$currentUserId'";

    // Build SQL INSERT query
    // This adds a new row to the items table and includes user_id
    $sql = "INSERT INTO items (user_id, title, description, type, location, contact, image) 
        VALUES ($userIdValue, '$title', '$description', 'lost', '$location', '$contact', '$imageName')";
        
        // Optional debug logging to help diagnose NULL user_id issues
        $enableDebugLog = true; // set to false to disable
        if ($enableDebugLog) {
            if (!is_dir('logs')) {
                mkdir('logs', 0755, true);
            }
            $debug = [];
            $debug[] = "---- " . date('Y-m-d H:i:s') . " ----";
            $debug[] = 'PAGE: report_lost.php';
            $debug[] = 'SESSION user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL');
            $debug[] = 'SESSION username: ' . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NULL');
            $debug[] = 'computed userIdValue: ' . $userIdValue;
            $debug[] = 'SQL: ' . $sql;
            $debug[] = 'mysqli_error: ' . mysqli_error($conn);
            file_put_contents('logs/upload_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND | LOCK_EX);
        }

        // Execute the query
        // mysqli_query() runs the SQL command
        if (mysqli_query($conn, $sql)) {
            $message = 'Lost item reported successfully!';
            // Clear form variables so form appears empty after submission
            $title = $description = $location = $contact = '';
        } else {
            // If query failed, show error message
            $message = 'Error: ' . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="./assets/logo.webp" alt="Lost & Found Logo">
                <h1>University Lost & Found</h1>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="report_lost.php" class="active">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <?php if (isUserLoggedIn()): ?>
                        <li><a href="user_dashboard.php">My Dashboard</a></li>
                        <li><a href="user_dashboard.php?logout=1">Logout</a></li>
                    <?php else: ?>
                        <li><a href="user_login.php">Login</a></li>
                        <li><a href="user_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Display message if any -->
        <?php if ($message != ''): ?>
            <div class="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Report Lost Item Form -->
        <div class="form-container">
            <h2>📢 Report a Lost Item</h2>
            <p style="text-align: center; color: var(--color-light); margin-bottom: 2rem;">
                Lost something on campus? Fill out this form with as much detail as possible to help others identify and return your item.
            </p>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="title">Item Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           placeholder="e.g., Black iPhone 13, Blue Backpack, Silver Watch"
                           value="<?php echo isset($title) ? $title : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea id="description" 
                              name="description" 
                              placeholder="Provide a detailed description including color, brand, size, unique features, or any identifying marks..."
                              required><?php echo isset($description) ? $description : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Last Known Location *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           placeholder="e.g., Library 2nd Floor, Student Center Cafeteria, Engineering Building Room 205"
                           value="<?php echo isset($location) ? $location : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="contact">Your Contact Information *</label>
                    <input type="email" 
                           id="contact" 
                           name="contact" 
                           placeholder="your.email@university.edu"
                           value="<?php echo isset($contact) ? $contact : ''; ?>"
                           required>
                    <small style="color: var(--color-medium-light); font-size: 0.875rem;">
                        We recommend using your university email address. This will be visible to people who might have found your item.
                    </small>
                </div>

                <div class="form-group">
                    <label for="image">Upload Image (Optional)</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <small style="color: var(--color-medium-light); font-size: 0.875rem;">
                        Adding a photo greatly increases the chances of recovering your item. Accepted formats: JPG, JPEG, PNG, GIF (Max 5MB)
                    </small>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn">📢 Submit Lost Item Report</button>
                    <a href="index.php" class="btn btn-secondary" style="margin-left: 1rem;">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="form-container">
            <h2>💡 Tips for Reporting Lost Items</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                <div>
                    <h4 style="color: var(--accent-error); margin-bottom: 1rem;">🔍 Be Specific</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Include brand names and model numbers</li>
                        <li>Mention unique scratches, stickers, or markings</li>
                        <li>Describe the exact color and size</li>
                        <li>Note any accessories or cases</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: var(--accent-error); margin-bottom: 1rem;">📍 Location Details</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Be as specific as possible about where you lost it</li>
                        <li>Include building names and room numbers</li>
                        <li>Mention the approximate time you lost it</li>
                        <li>Consider retracing your steps</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: var(--accent-error); margin-bottom: 1rem;">📞 Stay Reachable</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Use an email you check regularly</li>
                        <li>Respond quickly to potential matches</li>
                        <li>Check the portal frequently for updates</li>
                        <li>Update your report if you find the item</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Lost Items -->
        <div class="form-container">
            <h2>🔍 Recently Reported Lost Items</h2>
            <p style="text-align: center; color: var(--color-light); margin-bottom: 1.5rem;">
                Check if someone else has already reported finding your item
            </p>
            
            <?php
            // Get recent lost items from database for display
            // This shows users what others have recently reported
            $sql = "SELECT * FROM items WHERE type = 'lost' ORDER BY created_at DESC LIMIT 3";
            
            // Execute the query
            $result = mysqli_query($conn, $sql);
            
            // Fetch all results as an array
            $recentLostItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
            ?>
            
            <?php if (count($recentLostItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($recentLostItems as $item): ?>
                    <div class="item-card">
                        <span class="item-type lost">❌ Lost</span>
                        
                        <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php endif; ?>
                        
                        <h3><?php echo $item['title']; ?></h3>
                        <p><strong>Description:</strong> <?php echo substr($item['description'], 0, 80) . '...'; ?></p>
                        <p><strong>📍 Location:</strong> <?php echo $item['location']; ?></p>
                        
                        <div class="item-meta">
                            <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="items.php?filter=lost" class="btn btn-secondary">View All Lost Items</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--color-light); font-style: italic;">No lost items reported yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>
