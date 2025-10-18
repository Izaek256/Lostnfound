<?php
/**
 * Edit Item Page
 * 
 * Allows users to edit their own posted items
 */

require_once 'db.php';
require_once 'user_config.php';

// Require user to be logged in
requireUser();

$userId = getCurrentUserId();
$message = '';
$messageType = '';

// Get item ID from URL
if (!isset($_GET['id'])) {
    header('Location: user_dashboard.php');
    exit();
}

$itemId = mysqli_real_escape_string($conn, $_GET['id']);

// Get item details and verify ownership
$sql = "SELECT * FROM items WHERE id = '$itemId' AND user_id = '$userId'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    header('Location: user_dashboard.php');
    exit();
}

$item = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } else {
        // Handle new image upload
        $imageName = $item['image']; // Keep existing image by default
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadsDir = 'uploads/';
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($imageFileType, $allowedTypes)) {
                // Delete old image if exists
                if ($item['image'] && file_exists($uploadsDir . $item['image'])) {
                    unlink($uploadsDir . $item['image']);
                }
                
                // Upload new image
                $imageName = uniqid() . '.' . $imageFileType;
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadsDir . $imageName);
            }
        }
        
        $imageName = mysqli_real_escape_string($conn, $imageName);
        
        // Update item in database
        $sql = "UPDATE items 
                SET title = '$title', 
                    description = '$description', 
                    location = '$location', 
                    contact = '$contact', 
                    image = '$imageName' 
                WHERE id = '$itemId' AND user_id = '$userId'";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Item updated successfully!';
            $messageType = 'success';
            // Refresh item data
            $item['title'] = $title;
            $item['description'] = $description;
            $item['location'] = $location;
            $item['contact'] = $contact;
            $item['image'] = $imageName;
        } else {
            $message = 'Error updating item: ' . mysqli_error($conn);
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
    <title>Edit Item - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🎓 University Lost & Found</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <li><a href="user_dashboard.php">My Dashboard</a></li>
                    <li><a href="?logout=1" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($message != ''): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>✏️ Edit Item</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
                Update your item details
            </p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Item Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($item['title']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" 
                              name="description" 
                              required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           value="<?php echo htmlspecialchars($item['location']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Information *</label>
                    <input type="email" 
                           id="contact" 
                           name="contact" 
                           value="<?php echo htmlspecialchars($item['contact']); ?>"
                           required>
                </div>

                <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                    <div class="form-group">
                        <label>Current Image</label>
                        <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="Current image" 
                             style="max-width: 300px; border-radius: 8px; border: 1px solid var(--border);">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="image">Upload New Image (Optional)</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <small style="color: var(--text-light); font-size: 0.875rem;">
                        Leave empty to keep current image
                    </small>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn">💾 Save Changes</button>
                    <a href="user_dashboard.php" class="btn btn-secondary" style="margin-left: 1rem;">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
</body>
</html>
