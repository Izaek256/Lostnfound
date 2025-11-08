<?php
/**
 * ServerA API - Add Item
 * 
 * Creates a new item in the database
 * ServerA is the only server with direct database access
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Only POST method is allowed'], 405);
}

$user_id = $_POST['user_id'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$type = $_POST['type'] ?? '';
$location = $_POST['location'] ?? '';
$contact = $_POST['contact'] ?? '';
$image_filename = $_POST['image_filename'] ?? null;

// Validation
if (empty($user_id) || empty($title) || empty($description) || empty($type) || empty($location) || empty($contact)) {
    sendJSONResponse(['error' => 'Please fill all required fields'], 400);
}

if (!in_array($type, ['lost', 'found'])) {
    sendJSONResponse(['error' => 'Invalid item type'], 400);
}

try {
    $conn = connectDB();
    
    // Escape inputs to prevent SQL injection
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $title = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);
    $type = mysqli_real_escape_string($conn, $type);
    $location = mysqli_real_escape_string($conn, $location);
    $contact = mysqli_real_escape_string($conn, $contact);
    $image_filename = $image_filename ? mysqli_real_escape_string($conn, $image_filename) : null;
    
    $sql = "INSERT INTO items (user_id, title, description, type, location, contact, image, created_at) 
            VALUES ('$user_id', '$title', '$description', '$type', '$location', '$contact', " . 
            ($image_filename ? "'$image_filename'" : "NULL") . ", NOW())";
    
    if (mysqli_query($conn, $sql)) {
        $item_id = mysqli_insert_id($conn);
        mysqli_close($conn);
        sendJSONResponse([
            'success' => true,
            'item_id' => $item_id,
            'message' => 'Item added successfully'
        ]);
    } else {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Failed to add item: ' . mysqli_error($conn)], 500);
    }
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
