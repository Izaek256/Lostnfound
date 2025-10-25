<?php
require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        echo "error|Please fill all fields";
        exit;
    }
    
    $conn = connectDB();
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            echo "success|{$user['id']}|{$user['username']}|{$user['email']}|{$user['is_admin']}";
        } else {
            echo "error|Invalid password";
        }
    } else {
        echo "error|User not found";
    }
    
    mysqli_close($conn);
}
?>
