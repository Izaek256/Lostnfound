<?php
/**
 * ServerA API - Get Item by ID
 * 
 * Returns a specific item's details
 * This allows ServerC to retrieve item data without direct database access
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(['error' => 'Only GET method is allowed'], 405);
}

$item_id = $_GET['item_id'] ?? null;

if (!$item_id || !is_numeric($item_id)) {
    sendJSONResponse(['error' => 'Valid item_id parameter is required'], 400);
}

try {
    $conn = connectDB();
    
    $sql = "SELECT i.*, u.username FROM items i 
            LEFT JOIN users u ON i.user_id = u.id 
            WHERE i.id = '" . mysqli_real_escape_string($conn, $item_id) . "'";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    if (mysqli_num_rows($result) === 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Item not found'], 404);
    }
    
    $item = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    
    sendJSONResponse([
        'success' => true,
        'item' => $item
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
