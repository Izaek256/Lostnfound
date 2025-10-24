<?php
/**
 * API: Get Items
 * 
 * GET /api/get_items.php
 * 
 * Parameters (optional):
 * - filter: string (all, lost, found)
 * - search: string
 * - limit: int
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

$conn = getDBConnection();

// Get parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

// Build query
$sql = "SELECT * FROM items WHERE 1=1";

if ($filter !== 'all') {
    $filter = $conn->real_escape_string($filter);
    $sql .= " AND type = '$filter'";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (title LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%')";
}

$sql .= " ORDER BY created_at DESC";

if ($limit > 0) {
    $sql .= " LIMIT $limit";
}

$result = $conn->query($sql);

if (!$result) {
    sendJSONResponse(['error' => 'Database query failed: ' . $conn->error], 500);
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Get statistics
$statsSql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items";

$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

sendJSONResponse([
    'success' => true,
    'items' => $items,
    'stats' => $stats
]);

$conn->close();
?>
