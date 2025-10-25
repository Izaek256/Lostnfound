<?php
/**
 * Server B - Report Found Item Page
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
            $upload_dir = 'uploads/';
            
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
                VALUES ('$user_id', '$title', '$description', 'found', '$location', '$contact', '$image_filename', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $message = '✅ Found item reported successfully! The item owner will be able to find your listing and contact you directly.';
            // Clear form data on success
            $title = $description = $location = $contact = '';
        } else {
            $message = 'Failed to report found item';
        }
        
        mysqli_close($conn);
    }
}

// Get recent found items
$conn = connectDB();
$sql = "SELECT * FROM items WHERE type = 'found' ORDER BY created_at DESC LIMIT 3";
$result = mysqli_query($conn, $sql);
$recentFoundItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $recentFoundItems[] = $row;
}
mysqli_close($conn)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
../ServerC/./assets/style.css"    <style>
        .server-info {
            background: #e8f5e8;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .privacy-tips {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
    </style>
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
                            <li><a href="../ServerA/admin_dashboard.php">Admin Panel</a></li>
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
        <div class="server-info">
            <h3>🖥️ Server B - Report Found Item</h3>
            <p>Data will be saved to Server A (Main Backend)</p>
        </div>

        <!-- Privacy Guidelines -->
        <div class="privacy-tips">
            <h3>🔒 Privacy Guidelines for Found Items</h3>
            <ul>
                <li><strong>Don't share personal information</strong> found on items (ID cards, phone numbers, addresses)</li>
                <li><strong>Avoid showing sensitive details</strong> in photos (cover or blur personal information)</li>
                <li><strong>Let the owner prove ownership</strong> through description rather than sharing their personal details</li>
                <li><strong>Meet in safe, public locations</strong> when returning items</li>
            </ul>
        </div>

        <!-- Report Form -->
        <section class="form-container">
            <h2>🔍 Report Found Item</h2>
            <p>Help reunite found items with their owners by providing detailed information.</p>
            
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="title">Item Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($title); ?>"
                           placeholder="e.g., Black iPhone 13, Blue Backpack" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" 
                              name="description" 
                              rows="4" 
                              placeholder="Provide detailed description including brand, color, size, unique features, etc. Avoid sharing personal information found on the item." 
                              required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Found Location *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           value="<?php echo htmlspecialchars($location); ?>"
                           placeholder="e.g., Library Building, Room 205, Cafeteria" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="contact">Your Contact Email *</label>
                    <input type="email" 
                           id="contact" 
                           name="contact" 
                           value="<?php echo !empty($contact) ? htmlspecialchars($contact) : getCurrentUserEmail(); ?>"
                           placeholder="your.email@university.edu" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="image">Item Photo *</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*" 
                           required>
                    <small>Upload a clear photo of the item (JPG, PNG, GIF only). Make sure to blur or cover any personal information visible on the item.</small>
                </div>
                
                <button type="submit" class="btn btn-success">🔍 Submit Found Item Report</button>
            </form>
        </section>

        <!-- Recent Found Items -->
        <?php if (count($recentFoundItems) > 0): ?>
        <section class="form-container">
            <h2>🕒 Recent Found Items</h2>
            <p>Check if the item you found has already been reported.</p>
            
            <div class="items-grid">
                <?php foreach ($recentFoundItems as $item): ?>
                    <div class="item-card">
                        <div class="item-card-header">
                            <span class="item-type found">🟢 Found</span>
                            
                            <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="item-image">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <span>📷</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-card-body">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <p><strong>Found at:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                            <p><strong>Contact Finder:</strong> <a href="mailto:<?php echo htmlspecialchars($item['contact']); ?>"><?php echo htmlspecialchars($item['contact']); ?></a></p>
                            <p><strong>Posted:</strong> <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="../ServerC/script.js"></script>
</body>
</html>
