<?php
/**
 * Server C - Report Lost Item Page
 */

require_once 'config.php';

// Require user to be logged in
requireUser();

$message = '';
$title = '';
$description = '';
$location = '';
$contact = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';
    
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $message = 'Please fill in all required fields';
    } else {
        $user_id = getCurrentUserId();
        $image_filename = null;
        
        // Handle simple file upload
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
        
        $conn = connectDB();
        $sql = "INSERT INTO items (user_id, title, description, type, location, contact, image, created_at) 
                VALUES ('$user_id', '$title', '$description', 'lost', '$location', '$contact', '$image_filename', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $message = '📢 Lost item reported successfully! Your listing is now live and people who find items can contact you directly.';
            // Clear form data on success
            $title = $description = $location = $contact = '';
        } else {
            $message = 'Failed to report lost item';
        }
        
        mysqli_close($conn);
    }
}

// Get database connection for recent items display
$conn = connectDB();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
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
                        <?php if (isCurrentUserAdmin()): ?>
                            <li><a href="admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
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
                Lost something on campus? Create a detailed listing to help others identify and return your item. The more details you provide, the better your chances of recovery.
            </p>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateLostForm();">
                <div class="form-group">
                    <label for="title">Item Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           placeholder="e.g., Black iPhone 13, Blue Backpack, Silver Watch"
                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea id="description" 
                              name="description" 
                              placeholder="Describe your item in detail: color, brand, size, unique features, scratches, stickers, or any identifying marks. Include any personal items inside (without revealing sensitive info)..."
                              required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Last Known Location *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           placeholder="e.g., Library 2nd Floor Study Area, Student Center Cafeteria, Engineering Building Room 205"
                           value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="contact">Your Contact Information *</label>
                    <input type="email" 
                           id="contact" 
                           name="contact" 
                           placeholder="your.email@university.edu"
                           value="<?php echo isset($contact) ? htmlspecialchars($contact) : htmlspecialchars(getCurrentUserEmail()); ?>"
                           required>
                    <small style="color: var(--color-medium-light); font-size: 0.875rem;">
                        We recommend using your university email address. People who find items will use this to contact you.
                    </small>
                </div>

                <div class="form-group">
                    <label for="image">Upload Image (Optional)</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <small style="color: var(--color-medium-light); font-size: 0.875rem;">
                        If you have a photo of the item (from before you lost it), it can help with identification. Accepted formats: JPG, JPEG, PNG, GIF
                    </small>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-warning">📢 Submit Lost Item Report</button>
                    <a href="index.php" class="btn btn-secondary" style="margin-left: 1rem;">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="form-container">
            <h2>💡 Tips for Reporting Lost Items</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                <div>
                    <h4 style="color: var(--accent-warning); margin-bottom: 1rem;">🔍 Be Specific</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Include brand names and model numbers</li>
                        <li>Mention unique scratches, stickers, or wear</li>
                        <li>Describe the case, cover, or accessories</li>
                        <li>Note any personal items inside (wallet contents, etc.)</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: var(--accent-warning); margin-bottom: 1rem;">📍 Location Details</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Be as specific as possible about where you lost it</li>
                        <li>Include the time and date if you remember</li>
                        <li>Mention nearby landmarks or room numbers</li>
                        <li>Consider retracing your steps</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: var(--accent-warning); margin-bottom: 1rem;">🔒 Stay Safe</h4>
                    <ul style="padding-left: 1.5rem; color: var(--color-light);">
                        <li>Don't share sensitive personal information</li>
                        <li>Meet in public places for item pickup</li>
                        <li>Verify the person has your actual item</li>
                        <li>Consider involving campus security</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Lost Items -->
        <div class="form-container">
            <h2>📢 Recently Reported Lost Items</h2>
            <p style="text-align: center; color: var(--color-light); margin-bottom: 1.5rem;">
                See what others have lost recently - maybe you've found one of these items!
            </p>
            
            <?php
            // Get recent lost items from database for display
            if ($conn) {
                $sql = "SELECT * FROM items WHERE type = 'lost' ORDER BY created_at DESC LIMIT 3";
                $result = mysqli_query($conn, $sql);
                $recentLostItems = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
            } else {
                $recentLostItems = [];
            }
            ?>
            
            <?php if (count($recentLostItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($recentLostItems as $item): ?>
                    <div class="item-card">
                        <span class="item-type lost">📢 Lost</span>
                        
                        <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars(substr($item['description'], 0, 80) . '...'); ?></p>
                        <p><strong>📍 Last seen:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                        
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

        <!-- What to Do After Reporting -->
        <div class="form-container">
            <h2>📋 What Happens Next?</h2>
            <div style="background: rgba(255, 152, 0, 0.2); padding: 1.5rem; border-radius: 10px; margin-top: 1.5rem;">
                <ol style="padding-left: 1.5rem; color: var(--color-light); line-height: 1.8;">
                    <li><strong>Your report is now live</strong> - People who find items can search and contact you</li>
                    <li><strong>Check your email regularly</strong> - Finders will contact you directly</li>
                    <li><strong>Keep looking</strong> - Check campus lost & found offices and retrace your steps</li>
                    <li><strong>Verify ownership</strong> - Be prepared to describe your item in detail</li>
                    <li><strong>Update us</strong> - Let us know when your item is found so we can remove the listing</li>
                </ol>
            </div>
        </div>

        <!-- Additional Resources -->
        <div class="form-container">
            <h2>🏢 Campus Lost & Found Offices</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1.5rem; border-radius: 10px;">
                    <h4 style="color: var(--accent-primary); margin-bottom: 1rem;">📚 Library</h4>
                    <p style="color: var(--color-light); font-size: 0.9rem;">
                        Main Library Information Desk<br>
                        <strong>Hours:</strong> Mon-Fri 8AM-10PM<br>
                        <strong>Location:</strong> Ground Floor
                    </p>
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1.5rem; border-radius: 10px;">
                    <h4 style="color: var(--accent-primary); margin-bottom: 1rem;">🏢 Student Center</h4>
                    <p style="color: var(--color-light); font-size: 0.9rem;">
                        Student Services Office<br>
                        <strong>Hours:</strong> Mon-Fri 9AM-5PM<br>
                        <strong>Location:</strong> 2nd Floor, Room 205
                    </p>
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.1); padding: 1.5rem; border-radius: 10px;">
                    <h4 style="color: var(--accent-primary); margin-bottom: 1rem;">🚔 Campus Security</h4>
                    <p style="color: var(--color-light); font-size: 0.9rem;">
                        Security Office<br>
                        <strong>Hours:</strong> 24/7<br>
                        <strong>Emergency:</strong> Call Campus Security
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
    <script src="script.js"></script>
    <script>
    function validateLostForm() {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const location = document.getElementById('location').value.trim();
        const contact = document.getElementById('contact').value.trim();
        const image = document.getElementById('image').files[0];
        
        if (!title || !description || !location || !contact) {
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(contact)) {
            alert('Please enter a valid email address.');
            return false;
        }
        
        // If image is provided, validate it
        if (image) {
            // Validate image file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(image.type)) {
                alert('Please upload a valid image file (JPG, JPEG, PNG, or GIF).');
                return false;
            }
            
            // Validate image file size (max 5MB)
            if (image.size > 5 * 1024 * 1024) {
                alert('Image file size must be less than 5MB.');
                return false;
            }
        }
        
        return true;
    }
    </script>
</body>
</html>
