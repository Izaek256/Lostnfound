<?php
require_once '../config.php';

if ($_POST) {
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
        echo "error|Missing item ID or user ID";
        exit;
    }
    
    $conn = connectDB();
    
    // Check if item exists and belongs to user
    $check_sql = "SELECT * FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) == 0) {
        echo "error|Item not found or access denied";
        exit;
    }
    
    // Update item
    $update_sql = "UPDATE items SET title = '$title', description = '$description', type = '$type', location = '$location', contact = '$contact', image = '$image_filename' WHERE id = '$item_id' AND user_id = '$user_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        echo "success|Item updated successfully";
    } else {
        echo "error|Failed to update item";
    }
    
    mysqli_close($conn);
}
?>
