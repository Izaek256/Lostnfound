<?php
/**
 * Server C - Report Found Item Page
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
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
        $message = 'Please upload an image of the found item';
    } else {
        $user_id = getCurrentUserId();
        $image_filename = null;
        
        // Handle file upload
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
        
        // Call ServerA API to add item
        $response = makeAPIRequest(SERVERA_URL . '/add_item.php', [
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'type' => 'found',
            'location' => $location,
            'contact' => $contact,
            'image_filename' => $image_filename
        ], 'POST', ['return_json' => true]);
        
        // Parse JSON response
        if (is_array($response) && isset($response['success']) && $response['success']) {
            $message = '✅ Found item reported successfully! 
            The item owner will be able to find your listing and contact you directly.';
            // Clear form data on success
            $title = $description = $location = $contact = '';
        } else {
            $message = isset($response['error']) ? $response['error'] : 'Failed to report found item';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/style.css">
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
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php" class="active">Report Found</a></li>
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
                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea id="description" 
                              name="description" 
                              placeholder="Provide a detailed description including color, brand, size, unique features, or any identifying marks. Be specific but avoid sharing personal information found on the item..."
                              required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Where You Found It *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           placeholder="e.g., Library 2nd Floor Study Area, Student Center Lost & Found Desk, Engineering Building Hallway"
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
                        We recommend using your university email address. The item owner will use this to contact you for pickup arrangements.
                    </small>
                </div>

                <div class="form-group">
                    <label for="image">Upload Image *</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*"
                           required>
                    <small style="color: var(--color-medium-light); font-size: 0.875rem;">
                        A photo is required to help owners identify their items. Accepted formats: JPG, JPEG, PNG, GIF
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
            if ($conn) {
                $sql = "SELECT * FROM items WHERE type = 'found' ORDER BY created_at DESC LIMIT 3";
                $result = mysqli_query($conn, $sql);
                $recentFoundItems = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
            } else {
                $recentFoundItems = [];
            }
            ?>
            
            <?php if (count($recentFoundItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($recentFoundItems as $item): ?>
                    <div class="item-card">
                        <span class="item-type found">✅ Found</span>
                        
                        <?php if ($item['image']): ?>
                            <img src="<?php echo getImageUrl($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars(substr($item['description'], 0, 80) . '...'); ?></p>
                        <p><strong>📍 Found at:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                        
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
    <script src="assets/script.js"></script>
    <script>
    function validateForm() {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const location = document.getElementById('location').value.trim();
        const contact = document.getElementById('contact').value.trim();
        const image = document.getElementById('image').files[0];
        
        if (!title || !description || !location || !contact) {
            alert('Please fill in all required fields.');
            return false;
        }
        
        if (!image) {
            alert('Please upload an image of the found item.');
            return false;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(contact)) {
            alert('Please enter a valid email address.');
            return false;
        }
        
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
        
        return true;
    }
    </script>
</body>
</html>
