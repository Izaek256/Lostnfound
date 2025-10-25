<?php
require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

$conn = connectDB();

// Get filter parameters
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build simple query
$sql = "SELECT i.*, u.username FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE 1=1";

if (!empty($type) && in_array($type, ['lost', 'found'])) {
    $sql .= " AND i.type = '$type'";
}

if (!empty($search)) {
    $sql .= " AND (i.title LIKE '%$search%' OR i.description LIKE '%$search%' OR i.location LIKE '%$search%')";
}

$sql .= " ORDER BY i.created_at DESC";

$result = mysqli_query($conn, $sql);
$items = [];

while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

// Get simple statistics
$stats_sql = "SELECT COUNT(*) as total FROM items";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$lost_sql = "SELECT COUNT(*) as lost_count FROM items WHERE type = 'lost'";
$lost_result = mysqli_query($conn, $lost_sql);
$lost_stats = mysqli_fetch_assoc($lost_result);

$found_sql = "SELECT COUNT(*) as found_count FROM items WHERE type = 'found'";
$found_result = mysqli_query($conn, $found_sql);
$found_stats = mysqli_fetch_assoc($found_result);

echo json_encode([
    'success' => true,
    'items' => $items,
    'count' => count($items),
    'stats' => [
        'total' => $stats['total'],
        'lost_count' => $lost_stats['lost_count'],
        'found_count' => $found_stats['found_count']
    ]
]);

mysqli_close($conn);
?>
