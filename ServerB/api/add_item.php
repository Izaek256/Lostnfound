<?php
/**
 * Server B - Add Item API
 * 
 * This API handles adding new lost/found items
 */

require_once '../config.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get input data - handle both JSON and form data
$input = [];

// Check if it's multipart/form-data (file upload)
if (isset($_FILES['image']) || !empty($_POST)) {
    $input = $_POST;
} else {
    // Try to get JSON data
    $json_input = json_decode(file_get_contents('php://input'), true);
    if ($json_input) {
        $input = $json_input;
    }
}

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided']);
    exit();
}

// Validate required fields
$required_fields = ['title', 'description', 'type', 'location', 'contact', 'user_id'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

// Validate type
if (!in_array($input['type'], ['lost', 'found'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type. Must be "lost" or "found"']);
    exit();
}

// Handle image upload
$image_filename = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_filename = uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $image_filename;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload image']);
        exit();
    }
} else {
    $image_filename = 'default_item.jpg';
}

// Connect to database
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Insert item
$sql = "INSERT INTO items (user_id, title, description, type, location, contact, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare failed']);
    exit();
}

$stmt->bind_param("issssss", 
    $input['user_id'],
    $input['title'],
    $input['description'],
    $input['type'],
    $input['location'],
    $input['contact'],
    $image_filename
);

if ($stmt->execute()) {
    $item_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Item added successfully',
        'item_id' => $item_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add item']);
}

$stmt->close();
$conn->close();
?>
