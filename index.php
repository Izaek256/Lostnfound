<?php
/**
 * Lost and Found Portal - Homepage
 * 
 * This is the main landing page of the portal.
 * It displays:
 * - Navigation menu
 * - Portal statistics (total items, lost items, found items)
 * - Recent items (last 6 items posted)
 * - Information about how the portal works
 * - Helpful tips for users
 */

// Include the database connection file
// require_once ensures the file is included only once
require_once 'db.php';

// Include user functions
require_once 'user_config.php';

// Get the 6 most recent items from the database
// ORDER BY created_at DESC means newest items first
// LIMIT 6 means only get 6 items
$sql = "SELECT * FROM items ORDER BY created_at DESC LIMIT 6";

// Execute the query using mysqli_query()
$result = mysqli_query($conn, $sql);

// Fetch all results as an associative array
// mysqli_fetch_all() gets all rows at once
// MYSQLI_ASSOC means use column names as array keys
$recentItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get statistics for the dashboard
// This query counts total items and separates them by type
$sql = "SELECT 
    COUNT(*) as total,                                                    -- Total count of all items
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,       -- Count only lost items
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count      -- Count only found items
    FROM items";

// Execute the statistics query
$result = mysqli_query($conn, $sql);

// Fetch a single row (the statistics)
// mysqli_fetch_assoc() returns one row as an associative array
$stats = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Lost and Found Portal</title>
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
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <?php if (isUserLoggedIn()): ?>
                        <li><a href="user_dashboard.php">My Dashboard</a></li>
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
        <!-- Hero Section -->
        <section class="hero">
            <h2>University Lost and Found Portal</h2>
            <p>Help reunite lost items with their owners. Report lost items, browse found items, and help build a stronger campus community.</p>
            
            <!-- Quick Action Buttons -->
            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="report_lost.php" class="btn">📢 Report Lost Item</a>
                <a href="report_found.php" class="btn btn-success">🔍 Report Found Item</a>
                <a href="items.php" class="btn btn-secondary">👀 Browse All Items</a>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="form-container">
            <h2>📊 Portal Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;"><?php echo $stats['total']; ?></h3>
                    <p style="color: #666;">Total Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #dc3545; margin-bottom: 0.5rem;"><?php echo $stats['lost_count']; ?></h3>
                    <p style="color: #666;">Lost Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #28a745; margin-bottom: 0.5rem;"><?php echo $stats['found_count']; ?></h3>
                    <p style="color: #666;">Found Items</p>
                </div>
            </div>
        </section>

        <!-- Recent Items Section -->
        <?php if (count($recentItems) > 0): ?>
        <section class="items-container">
            <div class="items-header">
                <h2>🕒 Recent Items</h2>
                <a href="items.php" class="btn btn-secondary">View All Items</a>
            </div>
            
            <div class="items-grid">
                <?php foreach ($recentItems as $item): ?>
                <div class="item-card">
                    <span class="item-type <?php echo $item['type']; ?>">
                        <?php echo $item['type'] === 'lost' ? '❌ Lost' : '✅ Found'; ?>
                    </span>
                    
                    <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="item-image">
                    <?php endif; ?>
                    
                    <h3><?php echo $item['title']; ?></h3>
                    <p><strong>Description:</strong> <?php echo substr($item['description'], 0, 100); ?><?php if(strlen($item['description']) > 100) echo '...'; ?></p>
                    <p><strong>📍 Location:</strong> <?php echo $item['location']; ?></p>
                    <p><strong>📧 Contact:</strong> <?php echo $item['contact']; ?></p>
                    
                    <div class="item-meta">
                        <p><strong>Posted:</strong> <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- How It Works Section -->
        <section class="form-container">
            <h2>❓ How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📢</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Report Lost Items</h3>
                    <p>Lost something on campus? Create a detailed report with description, location, and contact information to help others identify and return your item.</p>
                </div>
                
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Report Found Items</h3>
                    <p>Found an item? Help reunite it with its owner by posting details about what you found and where you found it.</p>
                </div>
                
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🤝</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Connect & Reunite</h3>
                    <p>Browse through lost and found items, use the search feature, and contact item owners or finders to arrange returns.</p>
                </div>
            </div>
        </section>

        <!-- Tips Section -->
        <section class="form-container">
            <h2>💡 Tips for Success</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">📝 Be Descriptive</h4>
                    <ul style="padding-left: 1.5rem; color: #666;">
                        <li>Include specific details about the item</li>
                        <li>Mention unique characteristics or markings</li>
                        <li>Add the exact location where lost/found</li>
                        <li>Upload a clear photo if possible</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">⚡ Act Quickly</h4>
                    <ul style="padding-left: 1.5rem; color: #666;">
                        <li>Report items as soon as possible</li>
                        <li>Check the portal regularly for updates</li>
                        <li>Respond promptly to contact attempts</li>
                        <li>Update your post if item is recovered</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>
