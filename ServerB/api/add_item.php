<?php
require_once '../config.php';

if ($_POST) {
    $user_id = $_POST['user_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $image_filename = $_POST['image_filename'] ?? null;
    
    // Simple validation
    if (empty($user_id) || empty($title) || empty($description) || empty($type) || empty($location) || empty($contact)) {
        echo "error|Please fill all fields";
        exit;
    }
    
    $conn = connectDB();
    
    $sql = "INSERT INTO items (user_id, title, description, type, location, contact, image, created_at) 
            VALUES ('$user_id', '$title', '$description', '$type', '$location', '$contact', '$image_filename', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        $item_id = mysqli_insert_id($conn);
        echo "success|$item_id";
    } else {
        echo "error|Failed to add item";
    }
    
    mysqli_close($conn);
}
?>
