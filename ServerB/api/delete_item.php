<?php
/**
 * Server B - Delete Item API
 * 
 * This API handles deleting items
 */

require_once '../config.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// If JSON input is empty, try to get from POST data
if (!$input && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
}

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No input data provided']);
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
$sql = "SELECT image FROM items WHERE id = ? AND user_id = ?";
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

// Delete the item
$sql = "DELETE FROM items WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $input['id'], $input['user_id']);

if ($stmt->execute()) {
    // Delete associated image file if it exists and is not default
    if ($item['image'] && $item['image'] !== 'default_item.jpg') {
        $image_path = '../uploads/' . $item['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Item deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete item']);
}

$stmt->close();
$conn->close();
?>
