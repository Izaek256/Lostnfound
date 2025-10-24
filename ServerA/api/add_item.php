<?php
/**
 * API: Add New Item
 * 
 * POST /api/add_item.php
 * 
 * Parameters:
 * - title: string
 * - description: string
 * - type: string (lost, found)
 * - location: string
 * - contact: string
 * - image: file (multipart/form-data)
 * 
 * Returns:
 * - success: boolean
 * - message: string
 * - item_id: int (if successful)
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

// Get form data
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$type = $_POST['type'] ?? '';
$location = $_POST['location'] ?? '';
$contact = $_POST['contact'] ?? '';
$user_id = $_SESSION['user_id'];

// Validate required fields
if (empty($title) || empty($description) || empty($location) || empty($contact)) {
    sendJSONResponse(['error' => 'All fields are required'], 400);
}

if (!in_array($type, ['lost', 'found'])) {
    sendJSONResponse(['error' => 'Invalid item type'], 400);
}

// Handle image upload
if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
    sendJSONResponse(['error' => 'Image upload required'], 400);
}

$uploadsDir = '../uploads/';
$imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

if (!in_array($imageFileType, $allowedTypes)) {
    sendJSONResponse(['error' => 'Only JPG, JPEG, PNG & GIF files are allowed'], 400);
}

$imageName = uniqid() . '.' . $imageFileType;
$targetFile = $uploadsDir . $imageName;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
    sendJSONResponse(['error' => 'Failed to upload image'], 500);
}

// Prepare data for database
$title = $conn->real_escape_string($title);
$description = $conn->real_escape_string($description);
$location = $conn->real_escape_string($location);
$contact = $conn->real_escape_string($contact);
$imageName = $conn->real_escape_string($imageName);

// Insert into database
$sql = "INSERT INTO items (user_id, title, description, type, location, contact, image) 
        VALUES ('$user_id', '$title', '$description', '$type', '$location', '$contact', '$imageName')";

if ($conn->query($sql) === TRUE) {
    $item_id = $conn->insert_id;
    sendJSONResponse([
        'success' => true,
        'message' => ucfirst($type) . ' item reported successfully!',
        'item_id' => $item_id
    ]);
} else {
    // Delete uploaded file if database insert failed
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }
    sendJSONResponse(['error' => 'Error saving item: ' . $conn->error], 500);
}

$conn->close();
?>
