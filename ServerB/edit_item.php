<?php
/**
 * Server B - Edit Item Page
 * 
 * This page allows users to edit their items and sends data to Server A
 */

require_once 'config.php';

// Check if user is logged in
requireUser();

$itemId = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

if ($itemId <= 0) {
    header('Location: user_dashboard.php');
    exit();
}

// Get item details from Server A
$conn = getDBConnection();
$item = null;

if ($conn) {
    $userId = getCurrentUserId();
    $sql = "SELECT * FROM items WHERE id = '$itemId' AND user_id = '$userId'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        header('Location: user_dashboard.php');
        exit();
    }
    $conn->close();
} else {
    // If no direct DB connection, redirect to dashboard
    header('Location: user_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare form data for API call
    $formData = [
        'item_id' => $itemId,
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'location' => $_POST['location'] ?? '',
        'contact' => $_POST['contact'] ?? '',
        'image' => $_FILES['image'] ?? null
    ];
    
    // Validate required fields
    if (empty($formData['title']) || empty($formData['description']) || empty($formData['location']) || empty($formData['contact'])) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } else {
        // Make API call to Server A
        $response = makeAPICall('update_item', $formData, 'POST');
        
        if (isset($response['success']) && $response['success']) {
            $message = $response['message'];
            $messageType = 'success';
            
            // Update local item data
            $item['title'] = $formData['title'];
            $item['description'] = $formData['description'];
            $item['location'] = $formData['location'];
            $item['contact'] = $formData['contact'];
        } else {
            $message = $response['error'] ?? 'Failed to update item. Please try again.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
    <link rel="stylesheet" href="style.css">
    <style>
        .server-info {
            background: #e8f5e8;
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
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <li><a href="user_dashboard.php">My Dashboard</a></li>
                    <?php if (isCurrentUserAdmin()): ?>
                        <li><a href="../ServerA/admin_dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="user_dashboard.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="server-info">
            <h3>🖥️ Server B - Edit Item</h3>
            <p>Changes will be saved to Server A (Main Backend)</p>
        </div>

        <!-- Edit Form -->
        <section class="form-container">
            <h2>✏️ Edit Item</h2>
            <p>Update your item details below.</p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="title">Item Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($item['title']); ?>"
                           placeholder="e.g., Black iPhone 13, Blue Backpack" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" 
                              name="description" 
                              rows="4" 
                              placeholder="Provide detailed description including brand, color, size, unique features, etc." 
                              required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           value="<?php echo htmlspecialchars($item['location']); ?>"
                           placeholder="e.g., Library Building, Room 205, Cafeteria" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="contact">Contact Email *</label>
                    <input type="email" 
                           id="contact" 
                           name="contact" 
                           value="<?php echo htmlspecialchars($item['contact']); ?>"
                           placeholder="your.email@university.edu" 
                           required>
                </div>
                
                <!-- Current Image Display -->
                <?php if ($item['image'] && file_exists('../ServerA/uploads/' . $item['image'])): ?>
                    <div class="form-group">
                        <label>Current Image</label>
                        <img src="../ServerA/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="Current image" 
                             style="max-width: 300px; border-radius: 8px; border: 1px solid #ddd;">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="image">New Image (Optional)</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <small>Upload a new photo to replace the current one (JPG, PNG, GIF only)</small>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn">💾 Update Item</button>
                    <a href="user_dashboard.php" class="btn btn-secondary">❌ Cancel</a>
                </div>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>
