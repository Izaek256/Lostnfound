<?php
require_once '../config.php';

if ($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        echo "error|Please fill all fields";
        exit;
    }
    
    $conn = connectDB();
    
    // Check if user exists
    $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "error|Username or email already exists";
        exit;
    }
    
    // Hash password and insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, is_admin, created_at) VALUES ('$username', '$email', '$hashed_password', 0, NOW())";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        echo "success|$user_id|$username|$email|0";
    } else {
        echo "error|Registration failed";
    }
    
    mysqli_close($conn);
}
?>
