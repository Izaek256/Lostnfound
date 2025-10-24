<?php
/**
 * Server B - Report Found Item API Endpoint
 * 
 * This endpoint handles found item reporting requests for Server B
 */

require_once 'config.php';

// Log that the endpoint was accessed
error_log("Server B: report_found.php accessed via " . $_SERVER['REQUEST_METHOD'] . " method");

// Return simple JSON response indicating the endpoint is working
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests, indicate that found item reporting would be processed
    echo json_encode([
        'server' => 'Server B',
        'endpoint' => 'report_found',
        'status' => 'Processing found item report',
        'method' => 'POST',
        'message' => 'Found item report received. Processing would happen here.',
        'received_data' => array_keys($_POST),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // For GET requests or others, just indicate the endpoint is accessible
    echo json_encode([
        'server' => 'Server B',
        'endpoint' => 'report_found',
        'status' => 'Ready to receive found item reports',
        'method' => $_SERVER['REQUEST_METHOD'],
        'message' => 'This is an API endpoint for reporting found items.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

exit();
?>
<?php
/**
 * Server B - Report Found Item Page
 * 
 * This page allows users to report found items and sends data to Server A
 */

require_once 'config.php';

// Check if user is logged in
requireUser();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare form data for API call
    $formData = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'type' => 'found',
        'location' => $_POST['location'] ?? '',
        'contact' => $_POST['contact'] ?? '',
        'image' => $_FILES['image'] ?? null
    ];
    
    // Validate required fields
    if (empty($formData['title']) || empty($formData['description']) || empty($formData['location']) || empty($formData['contact'])) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
        $message = 'Please upload an image';
        $messageType = 'error';
    } else {
        // Make API call to Server A
        $response = makeAPICall('add_item', $formData, 'POST');
        
        if (isset($response['success']) && $response['success']) {
            $message = $response['message'];
            $messageType = 'success';
            
            // Clear form data on success
            $_POST = [];
        } else {
            $message = $response['error'] ?? 'Failed to report found item. Please try again.';
            $messageType = 'error';
        }
    }
}

// Get recent found items for display
$recentItemsData = makeAPICall('get_items', ['filter' => 'found', 'limit' => 3], 'GET');
$recentFoundItems = [];

if (isset($recentItemsData['success']) && $recentItemsData['success']) {
    $recentFoundItems = $recentItemsData['items'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
    <link rel="stylesheet" href="../ServerC/style.css">
    <style>
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
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="title">Item Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                           placeholder="e.g., Black iPhone 13, Blue Backpack" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" 
                              name="description" 
                              rows="4" 
                              placeholder="Provide detailed description including brand, color, size, unique features, etc. Avoid sharing personal information found on the item." 
                              required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Found Location *</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                           placeholder="e.g., Library Building, Room 205, Cafeteria" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="contact">Your Contact Email *</label>
                    <input type="email" 
                           id="contact" 
                           name="contact" 
                           value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : getCurrentUserEmail(); ?>"
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
                            
                            <?php if ($item['image'] && file_exists('../ServerA/uploads/' . $item['image'])): ?>
                                <img src="../ServerA/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
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

    <script src="script.js"></script>
</body>
</html>
