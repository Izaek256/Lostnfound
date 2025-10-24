<?php
/**
 * Server B - Get Items API
 * 
 * This API retrieves all lost and found items
 */

require_once '../config.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Connect to database
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get filter parameters
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT i.*, u.username FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if (!empty($type) && in_array($type, ['lost', 'found'])) {
    $sql .= " AND i.type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare failed']);
    exit();
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'count' => count($items)
]);

$stmt->close();
$conn->close();
?>
