<?php
/**
 * ItemsServer API - Delete Item
 * 
 * Deletes an item from the database (only if user owns it)
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
$is_admin = $_POST['is_admin'] ?? 0;  // Flag to indicate if admin is deleting

if (empty($item_id)) {
    sendJSONResponse(['error' => 'Missing item ID'], 400);
}

try {
    $conn = connectDB();
    
    $item_id = mysqli_real_escape_string($conn, $item_id);
    
    // Check if item exists
    $check_sql = "SELECT image FROM items WHERE id = '$item_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) === 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Item not found'], 404);
    }
    
    $item = mysqli_fetch_assoc($check_result);
    
    // If user_id is provided and not admin, verify ownership
    if (!empty($user_id) && $is_admin == 0) {
        $user_id = mysqli_real_escape_string($conn, $user_id);
        $ownership_check = "SELECT id FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
        $ownership_result = mysqli_query($conn, $ownership_check);
        
        if (mysqli_num_rows($ownership_result) === 0) {
            mysqli_close($conn);
            sendJSONResponse(['error' => 'Item not found or access denied'], 404);
        }
    }
    
    // Delete the item
    $delete_sql = "DELETE FROM items WHERE id = '$item_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        mysqli_close($conn);
        
        // Return item details including image for Frontend to clean up locally
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
