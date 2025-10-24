<?php
/**
 * Server B - Update Item API
 * 
 * This API handles updating existing items
 */

require_once '../config.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (!isset($input['id']) || !isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: id, user_id']);
    exit();
}

// Connect to database
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Check if item exists and user owns it
$sql = "SELECT * FROM items WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $input['id'], $input['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found or access denied']);
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Build update query
$update_fields = [];
$params = [];
$types = "";

if (isset($input['title'])) {
    $update_fields[] = "title = ?";
    $params[] = $input['title'];
    $types .= "s";
}

if (isset($input['description'])) {
    $update_fields[] = "description = ?";
    $params[] = $input['description'];
    $types .= "s";
}

if (isset($input['type']) && in_array($input['type'], ['lost', 'found'])) {
    $update_fields[] = "type = ?";
    $params[] = $input['type'];
    $types .= "s";
}

if (isset($input['location'])) {
    $update_fields[] = "location = ?";
    $params[] = $input['location'];
    $types .= "s";
}

if (isset($input['contact'])) {
    $update_fields[] = "contact = ?";
    $params[] = $input['contact'];
    $types .= "s";
}

if (empty($update_fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit();
}

$params[] = $input['id'];
$types .= "i";

$sql = "UPDATE items SET " . implode(", ", $update_fields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Item updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update item']);
}

$stmt->close();
$conn->close();
?>
