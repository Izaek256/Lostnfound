<?php
/**
 * API: Get User's Items
 * 
 * GET /api/get_user_items.php
 * 
 * Returns:
 * - success: boolean
 * - items: array
 * - stats: object
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(['error' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJSONResponse(['error' => 'User not logged in'], 401);
}

$conn = getDBConnection();

$user_id = $_SESSION['user_id'];

// Get user's items
$sql = "SELECT * FROM items WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    sendJSONResponse(['error' => 'Database query failed: ' . $conn->error], 500);
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Get user statistics
$statsSql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items WHERE user_id = '$user_id'";

$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

sendJSONResponse([
    'success' => true,
    'items' => $items,
    'stats' => $stats
]);

$conn->close();
?>
