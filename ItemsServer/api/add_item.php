<?php
/**
 * Add Item API
 * Creates a new item in the database
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

// Check if request is from browser directly (not AJAX)
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

// Enhanced browser detection - redirect to frontend if it looks like a direct browser request
$is_browser_request = (
    // Accept header contains text/html (browser requests HTML by default)
    (strpos($accept_header, 'text/html') !== false && strpos($accept_header, 'application/json') === false) &&
    // User agent indicates a browser
    (strpos($user_agent, 'Mozilla') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Safari') !== false || strpos($user_agent, 'Firefox') !== false || strpos($user_agent, 'Edge') !== false) &&
    // Not an AJAX request
    (empty($x_requested_with) || stripos($x_requested_with, 'XMLHttpRequest') === false)
);

if ($is_browser_request) {
    // Redirect to frontend report lost page
    $frontend_url = 'http://' . $_SERVER['SERVER_ADDR'] . '/Lostnfound/Frontend/report_lost.php';
    header("Location: $frontend_url");
    exit();
}

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
    
    // Escape inputs
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