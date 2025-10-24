<?php
/**
 * API: Update Item
 * 
 * POST /api/update_item.php
 * 
 * Parameters:
 * - item_id: int
 * - title: string
 * - description: string
 * - location: string
 * - contact: string
 * - image: file (optional)
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

// Verify ownership
$sql = "SELECT * FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    sendJSONResponse(['error' => 'Item not found or access denied'], 404);
}

$item = $result->fetch_assoc();

// Get form data
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$location = $_POST['location'] ?? '';
$contact = $_POST['contact'] ?? '';

// Validate required fields
if (empty($title) || empty($description) || empty($location) || empty($contact)) {
    sendJSONResponse(['error' => 'All fields are required'], 400);
}

// Handle image upload (optional)
$imageName = $item['image']; // Keep existing image by default

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $uploadsDir = '../uploads/';
    $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($imageFileType, $allowedTypes)) {
        // Delete old image
        if ($item['image'] && file_exists($uploadsDir . $item['image'])) {
            unlink($uploadsDir . $item['image']);
        }

        // Upload new image
        $imageName = uniqid() . '.' . $imageFileType;
        $targetFile = $uploadsDir . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            sendJSONResponse(['error' => 'Failed to upload image'], 500);
        }
    } else {
        sendJSONResponse(['error' => 'Only JPG, JPEG, PNG & GIF files are allowed'], 400);
    }
}

// Prepare data for database
$title = $conn->real_escape_string($title);
$description = $conn->real_escape_string($description);
$location = $conn->real_escape_string($location);
$contact = $conn->real_escape_string($contact);
$imageName = $conn->real_escape_string($imageName);

// Update database
$sql = "UPDATE items 
        SET title = '$title', 
            description = '$description', 
            location = '$location', 
            contact = '$contact', 
            image = '$imageName' 
        WHERE id = '$item_id' AND user_id = '$user_id'";

if ($conn->query($sql) === TRUE) {
    sendJSONResponse([
        'success' => true,
        'message' => 'Item updated successfully!'
    ]);
} else {
    sendJSONResponse(['error' => 'Error updating item: ' . $conn->error], 500);
}

$conn->close();
?>
