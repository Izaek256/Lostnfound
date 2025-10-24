<?php
/**
 * Server C - Report Found Item Page
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
            'type' => 'found',
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
            $success = 'Found item reported successfully!';
        } else {
            $error = $result['error'] ?? 'Failed to report found item';
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="assets/logo.webp" alt="Lost & Found Logo">
                <h1>Lost & Found Portal</h1>
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
            <div class="form-container">
                <h2>Report Found Item</h2>
                
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
                        <label for="location">Found Location:</label>
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
                    
                    <button type="submit" class="btn btn-primary">Report Found Item</button>
                </form>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>
