<?php
/**
 * Server B - Index API Endpoint
 * 
 * This endpoint indicates that Server B is running and ready to handle requests
 */

require_once 'config.php';

// Log that the endpoint was accessed
error_log("Server B: index.php accessed via " . $_SERVER['REQUEST_METHOD'] . " method");

// Return simple JSON response indicating the server is working
header('Content-Type: application/json');

echo json_encode([
    'server' => 'Server B',
    'status' => 'Running',
    'message' => '<div class="server-info"><h3>🖥️ Server B - Item Management</h3><p>Browse and manage lost & found items | Connected to shared database</p></div><script src="../ServerC/script.js"></script></body></html>',
    'timestamp' => date('Y-m-d H:i:s')
]);

exit();
?>
