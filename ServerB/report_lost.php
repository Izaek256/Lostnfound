<?php
/**
 * Server B - Report Lost Item Page
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
        $message = 'Please upload an image of the lost item';
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
                VALUES ('$user_id', '$title', '$description', 'lost', '$location', '$contact', '$image_filename', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $message = '✅ Lost item reported successfully! Others will now be able to see your listing and help you find it.';
            // Clear form data on success
            $title = $description = $location = $contact = '';
        } else {
            $message = 'Failed to report lost item';
        }
        
        mysqli_close($conn);
    }
}

// Get recent lost items
$conn = connectDB();
$sql = "SELECT * FROM items WHERE type = 'lost' ORDER BY created_at DESC LIMIT 3";
$result = mysqli_query($conn, $sql);
$recentLostItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $recentLostItems[] = $row;
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
../ServerC/./assets/style.css"    <style>
        .server-info {
            background: #ffe8e8;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
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
                    <li><a href="report_lost.php" class="active">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <?php if (isUserLoggedIn()): ?>
                        <li><a href="../ServerC/user_dashboard.php">My Dashboard</a></li>
                        <?php if (isCurrentUserAdmin()): ?>
                            <li><a href="../ServerA/admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="../ServerC/user_dashboard.php?logout=1">Logout</a></li>
                    <?php else: ?>
                        <li><a href="../ServerC/user_login.php">Login</a></li>
                        <li><a href="../ServerC/user_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="server-info">
            <h3>🖥️ Server B - Report Lost Item</h3>
            <p>Data will be saved to the database</p>
        </div>

        <!-- Report Form -->
        <section class="form-container">
            <h2>📢 Report Lost Item</h2>
            <p>Provide detailed information about your lost item to help others identify and return it.</p>
            
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
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
                              placeholder="Provide detailed description including brand, color, size, unique features, etc." 
                              required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Last Seen Location *</label>
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
                    <small>Upload a clear photo of the item (JPG, PNG, GIF only)</small>
                </div>
                
                <button type="submit" class="btn">📢 Submit Lost Item Report</button>
            </form>
        </section>

        <!-- Recent Lost Items -->
        <?php if (count($recentLostItems) > 0): ?>
        <section class="form-container">
            <h2>🕒 Recent Lost Items</h2>
            <p>Check if someone else has already reported your lost item.</p>
            
            <div class="items-grid">
                <?php foreach ($recentLostItems as $item): ?>
                    <div class="item-card">
                        <div class="item-card-header">
                            <span class="item-type lost">🔴 Lost</span>
                            
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
                            <p><strong>Last seen:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                            <p><strong>Contact Owner:</strong> <a href="mailto:<?php echo htmlspecialchars($item['contact']); ?>"><?php echo htmlspecialchars($item['contact']); ?></a></p>
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
