<?php
/**
 * API: Delete Item
 * 
 * POST /api/delete_item.php
 * 
 * Parameters:
 * - item_id: int
 * 
 * Returns:
 * - success: boolean
 * - message: string
 * - error: string (if failed)
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJSONResponse(['error' => 'User not logged in'], 401);
}

$conn = getDBConnection();

$item_id = (int)($_POST['item_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($item_id <= 0) {
    sendJSONResponse(['error' => 'Invalid item ID'], 400);
}

// Get item details (including image filename)
$sql = "SELECT image FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    sendJSONResponse(['error' => 'Item not found or access denied'], 404);
}

$item = $result->fetch_assoc();

// Delete image file if exists
if ($item['image'] && file_exists('../uploads/' . $item['image'])) {
    unlink('../uploads/' . $item['image']);
}

// Delete from database
$sql = "DELETE FROM items WHERE id = '$item_id' AND user_id = '$user_id'";

if ($conn->query($sql) === TRUE) {
    sendJSONResponse([
        'success' => true,
        'message' => 'Item deleted successfully!'
    ]);
} else {
    sendJSONResponse(['error' => 'Error deleting item: ' . $conn->error], 500);
}

$conn->close();
?>
