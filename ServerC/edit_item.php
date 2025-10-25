<?php
/**
 * Server C - Edit Item Page
 */

require_once 'config.php';

// Require user to be logged in
requireUser();

$message = '';
$messageType = '';
$item = null;

// Get item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if ($item_id <= 0) {
    $message = 'Invalid item ID provided.';
    $messageType = 'error';
    // Don't redirect immediately, show error instead
    // header('Location: user_dashboard.php');
    // exit();
}

// Get current user ID
$current_user_id = getCurrentUserId();

// Get all items and find the specific one
$result = makeAPICall('get_items');
$all_items = $result['items'] ?? [];

foreach ($all_items as $i) {
    if ($i['id'] == $item_id && $i['user_id'] == $current_user_id) {
        $item = $i;
        break;
    }
}

// If API failed or item not found, try direct database access
if (!$item) {
    $conn = getDBConnection();
    if ($conn) {
        $sql = "SELECT * FROM items WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $item_id, $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $item = $result->fetch_assoc();
            }
            
            $stmt->close();
        }
        $conn->close();
    }
}

if (!$item && $item_id > 0) {
    $message = 'Item not found or you do not have permission to edit this item.';
    $messageType = 'error';
    // Don't redirect immediately, show error instead
    // header('Location: user_dashboard.php');
    // exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$item) {
        $message = 'Cannot update: Item not found or no permission.';
        $messageType = 'error';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($type) || empty($location) || empty($contact)) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } elseif (!in_array($type, ['lost', 'found'])) {
        $message = 'Invalid item type.';
        $messageType = 'error';
    } else {
        // Handle image upload if provided
        $image_filename = $item['image']; // Keep existing image by default
        
        if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../ServerB/uploads/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['new_image']['type'], $allowed_types)) {
                $message = 'Invalid image type. Please upload JPEG, PNG, GIF, or WebP images.';
                $messageType = 'error';
            } elseif ($_FILES['new_image']['size'] > $max_size) {
                $message = 'Image too large. Maximum size is 5MB.';
                $messageType = 'error';
            } else {
                // Generate unique filename
                $extension = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
                $image_filename = uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $image_filename;
                
                if (!move_uploaded_file($_FILES['new_image']['tmp_name'], $upload_path)) {
                    $message = 'Failed to upload image.';
                    $messageType = 'error';
                    $image_filename = $item['image']; // Revert to original
                } else {
                    // Delete old image if it exists and is not default
                    if ($item['image'] && $item['image'] !== 'default_item.jpg') {
                        $old_image_path = $upload_dir . $item['image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                }
            }
        }
        
        // Update item in database if no errors
        if (!isset($message) || $messageType !== 'error') {
            $conn = getDBConnection();
            if ($conn) {
                $update_sql = "UPDATE items SET title = ?, description = ?, type = ?, location = ?, contact = ?, image = ? WHERE id = ? AND user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if ($update_stmt) {
                    $update_stmt->bind_param("ssssssii", $title, $description, $type, $location, $contact, $image_filename, $item_id, $current_user_id);
                    
                    if ($update_stmt->execute()) {
                        $affected_rows = $update_stmt->affected_rows;
                        if ($affected_rows > 0) {
                            $message = 'Item updated successfully!';
                            $messageType = 'success';
                            
                            // Refresh item data
                            $item['title'] = $title;
                            $item['description'] = $description;
                            $item['type'] = $type;
                            $item['location'] = $location;
                            $item['contact'] = $contact;
                            $item['image'] = $image_filename;
                        } else {
                            $message = 'No changes were made to the item (or item not found).';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Error executing update query: ' . $update_stmt->error;
                        $messageType = 'error';
                    }
                    $update_stmt->close();
                } else {
                    $message = 'Error preparing update query: ' . $conn->error;
                    $messageType = 'error';
                }
                $conn->close();
            } else {
                $message = 'Database connection failed.';
                $messageType = 'error';
            }
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
                        <li><a href="admin_dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="?logout=1" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($message != ''): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : ($messageType === 'error' ? 'alert-error' : 'alert-info'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>✏️ Edit Item</h2>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="user_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>

            <?php if ($item): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Item Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="lost" <?php echo $item['type'] === 'lost' ? 'selected' : ''; ?>>Lost Item</option>
                        <option value="found" <?php echo $item['type'] === 'found' ? 'selected' : ''; ?>>Found Item</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Information</label>
                    <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($item['contact']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="image">Current Image</label>
                    <?php if ($item['image'] && file_exists('../ServerB/uploads/' . $item['image'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <img src="../ServerB/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="Current item image" 
                                 style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                    <label for="new_image">Upload New Image (optional)</label>
                    <input type="file" id="new_image" name="new_image" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Item</button>
                    <a href="user_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        <?php echo $message ?: 'No item to edit.'; ?>
                    </p>
                    <a href="user_dashboard.php" class="btn">← Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
    
    <script src="script.js"></script>
</body>
</html>
