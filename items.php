<?php
/**
 * View All Items Page
 * 
 * This page displays all lost and found items with:
 * - Search functionality (search by title, description, or location)
 * - Filter functionality (show all, only lost, or only found items)
 * - Statistics display
 * - Clickable images for enlarged view
 * 
 * Users can search and filter to find specific items.
 */

// Include database connection
require_once 'db.php';

// Include user functions
require_once 'user_config.php';

// Get filter parameter from URL (default is 'all')
// $_GET contains URL parameters
$filter = 'all';
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// Get search term from URL (default is empty)
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Build SQL query dynamically based on filters
// Start with a base query that selects everything
$sql = "SELECT * FROM items WHERE 1=1"; // 1=1 is always true, makes it easy to add conditions

// Add filter condition if user selected specific type
if ($filter != 'all') {
    // Escape the filter value to prevent SQL injection
    $filter = mysqli_real_escape_string($conn, $filter);
    $sql .= " AND type = '$filter'";
}

// Add search condition if user entered a search term
if ($search != '') {
    // Escape search term to prevent SQL injection
    $search = mysqli_real_escape_string($conn, $search);
    // Search in title, description, and location using LIKE
    // % is a wildcard that matches any characters
    $sql .= " AND (title LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%')";
}

// Add ORDER BY to show newest items first
$sql .= " ORDER BY created_at DESC";

// Execute the query and get results
$result = mysqli_query($conn, $sql);

// Fetch all matching items
$items = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get statistics for the display
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items";
    
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Items - University Lost and Found</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
    <link rel="stylesheet" href="style.css">
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
        <!-- Display error if any -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Items Container -->
        <div class="items-container">
            <!-- Header with Search and Filters -->
            <div class="items-header">
                <div>
                    <h2>📋 All Items</h2>
                    <p id="results-count" style="color: #666; margin: 0;">
                        Showing <?php echo count($items); ?> of <?php echo $stats['total']; ?> items
                    </p>
                </div>
                
                <!-- Search and Filter Controls -->
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <input type="text" 
                               id="search" 
                               name="search" 
                               placeholder="🔍 Search items..." 
                               value="<?php echo $search; ?>"
                               style="min-width: 200px;">
                        
                        <select id="filter" name="filter">
                            <option value="all" <?php if($filter == 'all') echo 'selected'; ?>>All Items</option>
                            <option value="lost" <?php if($filter == 'lost') echo 'selected'; ?>>Lost Items (<?php echo $stats['lost_count']; ?>)</option>
                            <option value="found" <?php if($filter == 'found') echo 'selected'; ?>>Found Items (<?php echo $stats['found_count']; ?>)</option>
                        </select>
                        
                        <button type="submit" class="btn" style="padding: 0.5rem 1rem;">Apply Filters</button>
                        
                        <?php if ($search != '' || $filter != 'all'): ?>
                            <a href="items.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear Filters</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; flex: 1; min-width: 150px; text-align: center;">
                    <strong style="color: #667eea; font-size: 1.2rem;"><?php echo $stats['total']; ?></strong>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Items</p>
                </div>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; flex: 1; min-width: 150px; text-align: center;">
                    <strong style="color: #dc3545; font-size: 1.2rem;"><?php echo $stats['lost_count']; ?></strong>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;">Lost Items</p>
                </div>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; flex: 1; min-width: 150px; text-align: center;">
                    <strong style="color: #28a745; font-size: 1.2rem;"><?php echo $stats['found_count']; ?></strong>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;">Found Items</p>
                </div>
            </div>

            <!-- Items Grid -->
            <?php if (count($items) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-card-header">
                            <span class="item-type <?php echo $item['type']; ?>">
                                <?php echo $item['type'] === 'lost' ? '🔴 Lost' : '🟢 Found'; ?>
                            </span>
                            
                            <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="item-image"
                                     onclick="openImageModal('uploads/<?php echo htmlspecialchars($item['image']); ?>', '<?php echo htmlspecialchars($item['title']); ?>')">
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
                                        <strong>Contact:</strong><br>
                                        <a href="mailto:<?php echo htmlspecialchars($item['contact']); ?>" 
                                           style="color: var(--primary); text-decoration: none; word-break: break-all;"><?php echo htmlspecialchars($item['contact']); ?></a>
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
                                <p>
                                    <strong>ID:</strong> #<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Items Found -->
                <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">No Items Found</h3>
                    
                    <?php if ($search != '' || $filter != 'all'): ?>
                        <p style="color: #666; margin-bottom: 2rem;">
                            No items match your current search criteria. Try adjusting your filters or search terms.
                        </p>
                        <a href="items.php" class="btn btn-secondary">View All Items</a>
                    <?php else: ?>
                        <p style="color: #666; margin-bottom: 2rem;">
                            No items have been reported yet. Be the first to help build our campus community!
                        </p>
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                            <a href="report_lost.php" class="btn">Report Lost Item</a>
                            <a href="report_found.php" class="btn btn-success">Report Found Item</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Help Section -->
        <div class="form-container">
            <h2>❓ Need Help?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔍</div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">Can't Find Your Item?</h4>
                    <p style="color: #666; margin-bottom: 1rem;">Try different search terms or check back regularly. New items are added daily.</p>
                    <a href="report_lost.php" class="btn">Report Your Lost Item</a>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📧</div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">Contact Item Owners</h4>
                    <p style="color: #666; margin-bottom: 1rem;">Click on email addresses to contact item owners or finders directly.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🤝</div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">Found Something?</h4>
                    <p style="color: #666; margin-bottom: 1rem;">Help reunite items with their owners by reporting what you've found.</p>
                    <a href="report_found.php" class="btn btn-success">Report Found Item</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); cursor: pointer;" onclick="closeImageModal()">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%;">
            <img id="modalImage" style="max-width: 100%; max-height: 100%; border-radius: 10px;">
            <p id="modalCaption" style="color: white; text-align: center; margin-top: 1rem; font-size: 1.1rem;"></p>
        </div>
        <span style="position: absolute; top: 20px; right: 30px; color: white; font-size: 2rem; cursor: pointer;" onclick="closeImageModal()">&times;</span>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        // Image modal functionality
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

        // Real-time search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const filterSelect = document.getElementById('filter');
            
            if (searchInput && filterSelect) {
                let searchTimeout;
                
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        filterItems();
                    }, 300);
                });
                
                filterSelect.addEventListener('change', function() {
                    filterItems();
                });
            }
        });

        function filterItems() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const filterType = document.getElementById('filter').value;
            const itemCards = document.querySelectorAll('.item-card');
            let visibleCount = 0;
            
            itemCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p:nth-of-type(2)').textContent.toLowerCase();
                const location = card.querySelector('p:nth-of-type(3)').textContent.toLowerCase();
                const type = card.querySelector('.item-type').textContent.toLowerCase();
                
                const matchesSearch = searchTerm === '' || 
                                    title.includes(searchTerm) || 
                                    description.includes(searchTerm) ||
                                    location.includes(searchTerm);
                
                const matchesFilter = filterType === 'all' || type.includes(filterType);
                
                if (matchesSearch && matchesFilter) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update results count
            const resultsCount = document.getElementById('results-count');
            if (resultsCount) {
                resultsCount.textContent = `Showing ${visibleCount} of <?php echo $stats['total']; ?> items`;
            }
        }
    </script>
</body>
</html>
