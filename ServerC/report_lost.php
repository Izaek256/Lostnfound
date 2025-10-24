<?php
/**
 * Server C - Report Lost Item Page
 */

require_once 'config.php';

// Require user to be logged in
requireUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';
    
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $error = 'Please fill in all fields';
    } else {
        $data = [
            'title' => $title,
            'description' => $description,
            'type' => 'lost',
            'location' => $location,
            'contact' => $contact,
            'user_id' => getCurrentUserId()
        ];
        
        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $data['image'] = new CURLFile($_FILES['image']['tmp_name'], $_FILES['image']['type'], $_FILES['image']['name']);
        }
        
        $result = makeAPICall('add_item', $data, 'POST');
        
        if (isset($result['success']) && $result['success']) {
            $success = 'Lost item reported successfully!';
        } else {
            $error = $result['error'] ?? 'Failed to report lost item';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - Lost & Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="assets/logo.webp" alt="Lost & Found Logo">
                <h1>Lost & Found Portal</h1>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="items.php">Browse Items</a>
                <a href="report_found.php">Report Found</a>
                <a href="user_dashboard.php">Dashboard</a>
                <a href="?logout=1">Logout</a>
            </nav>
        </header>
        
        <main>
            <div class="form-container">
                <h2>Report Lost Item</h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Item Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Last Known Location:</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact">Contact Information:</label>
                        <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars(getCurrentUserEmail()); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Item Image (optional):</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Report Lost Item</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
