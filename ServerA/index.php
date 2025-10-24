<?php
/**
 * Server A - Index Page
 * 
 * This is the main entry point for Server A
 */

require_once 'config.php';

// Redirect to admin login by default
header('Location: admin_login.php');
exit();
?>
