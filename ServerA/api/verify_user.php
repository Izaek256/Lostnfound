<?php
require_once '../config.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        echo "Please fill all fields";
        exit;
    }
    
    $conn = connectDB();
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            echo "success";
        } else {
            echo "Invalid password";
        }
    } else {
        echo "User not found";
    }
    
    mysqli_close($conn);
}
?>
