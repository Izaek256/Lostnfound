<?php
/**
 * ItemsServer API - Update Item
 * 
 * Updates an item in the database (only if user owns it)
 * ItemsServer is the only server with direct database access
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Only POST method is allowed'], 405);
}

$item_id = $_POST['id'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$type = $_POST['type'] ?? '';
$location = $_POST['location'] ?? '';
$contact = $_POST['contact'] ?? '';
$image_filename = $_POST['image_filename'] ?? null;

// Validation
if (empty($item_id) || empty($user_id)) {
    sendJSONResponse(['error' => 'Missing item ID or user ID'], 400);
}

if (!in_array($type, ['lost', 'found'])) {
    sendJSONResponse(['error' => 'Invalid item type'], 400);
}

try {
    $conn = connectDB();
    
    $item_id = mysqli_real_escape_string($conn, $item_id);
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    // Check if item exists and belongs to user
    $check_sql = "SELECT * FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) === 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Item not found or access denied'], 404);
    }
    
    // Escape inputs
    $title = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);
    $type = mysqli_real_escape_string($conn, $type);
    $location = mysqli_real_escape_string($conn, $location);
    $contact = mysqli_real_escape_string($conn, $contact);
    
    // Update item - only update image if provided
    if (!empty($image_filename)) {
        $image_filename = mysqli_real_escape_string($conn, $image_filename);
        $update_sql = "UPDATE items SET title = '$title', description = '$description', type = '$type', location = '$location', contact = '$contact', image = '$image_filename' WHERE id = '$item_id' AND user_id = '$user_id'";
    } else {
        $update_sql = "UPDATE items SET title = '$title', description = '$description', type = '$type', location = '$location', contact = '$contact' WHERE id = '$item_id' AND user_id = '$user_id'";
    }
    
    if (mysqli_query($conn, $update_sql)) {
        mysqli_close($conn);
        sendJSONResponse([
            'success' => true,
            'message' => 'Item updated successfully'
        ]);
    } else {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Failed to update item'], 500);
    }
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
