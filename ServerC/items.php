<?php
/**
 * Server B - View Items Page
 * 
 * This page displays all items and fetches data from Server A
 */

require_once 'config.php';

// Get filter and search parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get data from ServerA API instead of direct database connection
$api_response = makeAPIRequest(SERVERA_URL . '/get_all_items.php', [
    'type' => $filter !== 'all' ? $filter : '',
    'search' => $search
], 'GET', ['return_json' => true]);

// Initialize default data
$items = [];
$stats = [
    'total' => 0,
    'lost_count' => 0,
    'found_count' => 0
];

// Process API response
if (is_array($api_response) && isset($api_response['success']) && $api_response['success']) {
    $items = $api_response['items'] ?? [];
    $stats = $api_response['stats'] ?? $stats;
} else {
    // Handle API error
    error_log('ServerA API error: ' . json_encode($api_response));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
    <link rel="stylesheet" href="assets/style.css">
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
                    <li><a href="items.php" class="active">View Items</a></li>
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
        <div class="server-info">
            <h3>🖥️ Server B - Browse Items</h3>
            <p>Data synchronized from Server A (Main Backend)</p>
        </div>

        <!-- Search and Filter Section -->
        <section class="form-container">
            <h2>🔍 Search & Filter Items</h2>
            
            <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label for="search">Search Items</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="🔍 Search by title, description, or location...">
                </div>
                
                <div class="form-group" style="min-width: 150px;">
                    <label for="filter">Filter by Type</label>
                    <select id="filter" name="filter">
                        <option value="all" <?php if($filter == 'all') echo 'selected'; ?>>All Items</option>
                        <option value="lost" <?php if($filter == 'lost') echo 'selected'; ?>>Lost Items</option>
                        <option value="found" <?php if($filter == 'found') echo 'selected'; ?>>Found Items</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Apply Filters</button>
            </form>
        </section>

        <!-- Statistics -->
        <section class="form-container">
            <h2>📊 Current Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #667eea;"><?php echo $stats['total']; ?></h3>
                    <p>Total Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #dc3545;"><?php echo $stats['lost_count']; ?></h3>
                    <p>Lost Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #28a745;"><?php echo $stats['found_count']; ?></h3>
                    <p>Found Items</p>
                </div>
            </div>
        </section>

        <!-- Items Display -->
        <section class="items-container">
            <div class="items-header">
                <h2>📋 All Items</h2>
                <div>Showing <?php echo count($items); ?> of <?php echo $stats['total']; ?> items</div>
            </div>
            
            <?php if (count($items) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card">
                            <div class="item-card-header">
                                <span class="item-type <?php echo $item['type']; ?>">
                                    <?php echo $item['type'] === 'lost' ? '🔴 Lost' : '🟢 Found'; ?>
                                </span>
                                
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo getImageUrl($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         class="item-image"
                                         onclick="openImageModal('<?php echo getImageUrl($item['image']); ?>', '<?php echo htmlspecialchars($item['title']); ?>')">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <span>📷</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-card-body">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="9" y1="3" x2="9" y2="21"></line>
                                    </svg>
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                                
                                <div class="item-card-section">
                                    <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                                </div>
                                
                                <div class="item-card-section">
                                    <div class="item-detail">
                                        <svg class="item-detail-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <div class="item-detail-content">
                                            <strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="item-detail">
                                        <svg class="item-detail-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        <div class="item-detail-content">
                                            <strong>Contact:</strong> <a href="mailto:<?php echo htmlspecialchars($item['contact']); ?>" style="color: var(--primary);"><?php echo htmlspecialchars($item['contact']); ?></a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="item-meta">
                                    <p>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <h3>No items found</h3>
                    <?php if ($search != '' || $filter != 'all'): ?>
                        <p>Try adjusting your search criteria or <a href="items.php">view all items</a></p>
                    <?php else: ?>
                        <p>Be the first to <a href="report_lost.php">report a lost item</a> or <a href="report_found.php">report a found item</a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); cursor: pointer;" onclick="closeImageModal()">
        <img id="modalImage" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%; object-fit: contain;">
        <p id="modalCaption" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; text-align: center; font-size: 1.2rem;"></p>
        <span style="position: absolute; top: 20px; right: 35px; color: white; font-size: 40px; font-weight: bold; cursor: pointer;" onclick="closeImageModal()">&times;</span>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="assets/script.js"></script>
    <script>
        function openImageModal(imageSrc, title) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalCaption').textContent = title;
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
