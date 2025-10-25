<?php
require_once '../config.php';

if ($_POST) {
    $user_id = getCurrentUserId();
    
    if (!$user_id) {
        echo "Please login first";
        exit;
    }
    
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    $contact = $_POST['contact'];
    $image_filename = null;
    
    // Simple validation
    if (empty($title) || empty($description) || empty($type) || empty($location) || empty($contact)) {
        echo "Please fill all fields";
        exit;
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_filename = uniqid() . '.' . $extension;
        $upload_path = $upload_dir . $image_filename;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            echo "Failed to upload image";
            exit;
        }
    }
    
    $conn = connectDB();
    
    $sql = "INSERT INTO items (user_id, title, description, type, location, contact, image, created_at) 
            VALUES ('$user_id', '$title', '$description', '$type', '$location', '$contact', '$image_filename', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Failed to add item";
    }
    
    mysqli_close($conn);
}
?>
