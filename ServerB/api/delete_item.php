<?php
require_once '../config.php';

if ($_POST) {
    $item_id = $_POST['id'];
    $user_id = $_POST['user_id'];
    
    if (empty($item_id) || empty($user_id)) {
        echo "Missing item ID or user ID";
        exit;
    }
    
    $conn = connectDB();
    
    // Check if item exists and belongs to user
    $check_sql = "SELECT image FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) == 0) {
        echo "Item not found or access denied";
        exit;
    }
    
    $item = mysqli_fetch_assoc($check_result);
    
    // Delete the item
    $delete_sql = "DELETE FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        // Delete image file if exists
        if ($item['image'] && $item['image'] != 'default_item.jpg') {
            $image_path = '../uploads/' . $item['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        echo "success|Item deleted successfully";
    } else {
        echo "error|Failed to delete item";
    }
    
    mysqli_close($conn);
}
?>
