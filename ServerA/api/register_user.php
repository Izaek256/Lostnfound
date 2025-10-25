<?php
require_once '../config.php';

if ($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        echo "Please fill all fields";
        exit;
    }
    
    $conn = connectDB();
    
    // Check if user exists
    $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "Username or email already exists";
        exit;
    }
    
    // Hash password and insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, is_admin, created_at) VALUES ('$username', '$email', '$hashed_password', 0, NOW())";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['user_email'] = $email;
        $_SESSION['is_admin'] = 0;
        echo "success";
    } else {
        echo "Registration failed";
    }
    
    mysqli_close($conn);
}
?>
