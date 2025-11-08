<?php
/**
 * ServerA API - Delete Item
 * 
 * Deletes an item from the database (only if user owns it)
 * ServerA is the only server with direct database access
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Only POST method is allowed'], 405);
}

$item_id = $_POST['id'] ?? '';
$user_id = $_POST['user_id'] ?? '';

if (empty($item_id) || empty($user_id)) {
    sendJSONResponse(['error' => 'Missing item ID or user ID'], 400);
}

try {
    $conn = connectDB();
    
    $item_id = mysqli_real_escape_string($conn, $item_id);
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    // Check if item exists and belongs to user
    $check_sql = "SELECT image FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) === 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Item not found or access denied'], 404);
    }
    
    $item = mysqli_fetch_assoc($check_result);
    
    // Delete the item
    $delete_sql = "DELETE FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        mysqli_close($conn);
        
        // Return item details including image for ServerC to clean up locally
        sendJSONResponse([
            'success' => true,
            'message' => 'Item deleted successfully',
            'image' => $item['image']
        ]);
    } else {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Failed to delete item'], 500);
    }
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
