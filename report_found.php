<?php
/**
 * Report Found Item Page
 * 
 * This page allows users to report items they have found.
 * It handles:
 * - Displaying the report form
 * - Processing form submissions
 * - Uploading images
 * - Inserting data into the database
 * - Showing recent found items for reference
 */

// Include database connection
require_once 'db.php';

// Include user functions to check if user is logged in
require_once 'user_config.php';

// Get current user ID if logged in (NULL if not logged in)
$currentUserId = getCurrentUserId();

// Variable to store success or error messages
$message = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data from $_POST array
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $contact = $_POST['contact'];
    
    // Validate that all required fields are filled
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $message = 'Please fill in all fields';
    } else {
        
        // Handle image upload (same process as report_lost.php)
        $imageName = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadDir = 'uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir);
            }
            
            // Get file extension
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            // Only allow certain image formats for security
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($imageFileType, $allowedTypes)) {
                // Generate unique filename
                $imageName = uniqid() . '.' . $imageFileType;
                // Move file to uploads directory
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
            }
        }
        
        // Escape data to prevent SQL injection
        $title = mysqli_real_escape_string($conn, $title);
        $description = mysqli_real_escape_string($conn, $description);
        $location = mysqli_real_escape_string($conn, $location);
        $contact = mysqli_real_escape_string($conn, $contact);
        $imageName = mysqli_real_escape_string($conn, $imageName);
        
        // Get user ID for logged in users (NULL for guest posts)
        $userIdValue = $currentUserId ? "'$currentUserId'" : 'NULL';
        
        // Insert into database with type='found'
        // Note: The only difference from lost items is the 'found' type
        $sql = "INSERT INTO items (user_id, title, description, type, location, contact, image) 
                VALUES ($userIdValue, '$title', '$description', 'found', '$location', '$contact', '$imageName')";
        
        // Optional debug logging to help diagnose NULL user_id issues
        $enableDebugLog = true; // set to false to disable
        if ($enableDebugLog) {
            if (!is_dir('logs')) {
                mkdir('logs', 0755, true);
            }
            $debug = [];
            $debug[] = "---- " . date('Y-m-d H:i:s') . " ----";
            $debug[] = 'PAGE: report_found.php';
            $debug[] = 'SESSION user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL');
            $debug[] = 'SESSION username: ' . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NULL');
            $debug[] = 'computed userIdValue: ' . $userIdValue;
            $debug[] = 'SQL: ' . $sql;
            $debug[] = 'mysqli_error: ' . mysqli_error($conn);
            file_put_contents('logs/upload_debug.log', implode("\n", $debug) . "\n\n", FILE_APPEND | LOCK_EX);
        }

        // Execute query
        if (mysqli_query($conn, $sql)) {
            $message = 'Found item reported successfully!';
            // Clear form variables
            $title = $description = $location = $contact = '';
        } else {
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
    <title>Report Found Item - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🎓 University Lost & Found</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php" class="active">Report Found</a></li>
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

        <!-- Report Found Item Form -->
        <div class="form-container">
            <h2>🔍 Report a Found Item</h2>
            <p style="text-align: center; color: var(--color-light); margin-bottom: 2rem;">
                Found something on campus? Help reunite it with its owner by providing detailed information about the item and where you found it.
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
                              placeholder="Provide a detailed description including color, brand, size, unique features, or any identifying marks. Be specific but avoid sharing personal information found on the item..."
                              required><?php echo isset($description) ? $description : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Where You Found It *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           placeholder="e.g., Library 2nd Floor Study Area, Student Center Lost & Found Desk, Engineering Building Hallway"
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
                        We recommend using your university email address. The item owner will use this to contact you for pickup arrangements.
                    </small>
                </div>

                <div class="form-group">
                    <label for="image">Upload Image (Optional but Recommended)</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <small style="color: var(--color-medium-light); font-size: 0.875rem;">
                        A photo helps owners quickly identify their items. Please avoid showing personal information like names, addresses, or phone numbers. Accepted formats: JPG, JPEG, PNG, GIF (Max 5MB)
                    </small>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success">🔍 Submit Found Item Report</button>
                    <a href="index.php" class="btn btn-secondary" style="margin-left: 1rem;">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="form-container">
            <h2>💡 Tips for Reporting Found Items</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                <div>
                    <h4 style="color: var(--accent-success); margin-bottom: 1rem;">🔒 Protect Privacy</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Don't share personal info found on items</li>
                        <li>Avoid showing ID cards, phone numbers, or addresses</li>
                        <li>Describe the item without revealing sensitive details</li>
                        <li>Let the owner prove ownership through description</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: var(--accent-success); margin-bottom: 1rem;">📸 Photo Guidelines</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Take clear, well-lit photos</li>
                        <li>Show distinctive features or markings</li>
                        <li>Cover or blur any personal information</li>
                        <li>Multiple angles can be helpful</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: var(--accent-success); margin-bottom: 1rem;">🤝 Safe Handoff</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Meet in public, well-lit areas</li>
                        <li>Consider campus security or lost & found office</li>
                        <li>Ask the owner to describe the item first</li>
                        <li>Verify ownership before handing over</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Found Items -->
        <div class="form-container">
            <h2>✅ Recently Reported Found Items</h2>
            <p style="text-align: center; color: var(--color-light); margin-bottom: 1.5rem;">
                See what others have found recently - maybe someone found your lost item!
            </p>
            
            <?php
            // Get recent found items from database for display
            // This helps users see what others have found recently
            $sql = "SELECT * FROM items WHERE type = 'found' ORDER BY created_at DESC LIMIT 3";
            
            // Execute the query
            $result = mysqli_query($conn, $sql);
            
            // Fetch all results
            $recentFoundItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
            ?>
            
            <?php if (count($recentFoundItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($recentFoundItems as $item): ?>
                    <div class="item-card">
                        <span class="item-type found">✅ Found</span>
                        
                        <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php endif; ?>
                        
                        <h3><?php echo $item['title']; ?></h3>
                        <p><strong>Description:</strong> <?php echo substr($item['description'], 0, 80) . '...'; ?></p>
                        <p><strong>📍 Found at:</strong> <?php echo $item['location']; ?></p>
                        
                        <div class="item-meta">
                            <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="items.php?filter=found" class="btn btn-secondary">View All Found Items</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--color-light); font-style: italic;">No found items reported yet.</p>
            <?php endif; ?>
        </div>

        <!-- What to Do After Reporting -->
        <div class="form-container">
            <h2>📋 What Happens Next?</h2>
            <div style="background: rgba(0, 77, 64, 0.3); padding: 1.5rem; border-radius: 10px; margin-top: 1.5rem;">
                <ol style="padding-left: 1.5rem; color: var(--color-light); line-height: 1.8;">
                    <li><strong>Your report is now live</strong> - Item owners can search and find your listing</li>
                    <li><strong>Check your email regularly</strong> - Potential owners will contact you directly</li>
                    <li><strong>Verify ownership</strong> - Ask them to describe the item before meeting</li>
                    <li><strong>Arrange safe pickup</strong> - Meet in public areas or use campus lost & found</li>
                    <li><strong>Update the community</strong> - Let us know when the item is successfully returned</li>
                </ol>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>
