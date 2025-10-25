<?php
/**
 * ServerC Health Check Endpoint
 * Returns status of user interface server and database
 */
header('Content-Type: application/json');

// Direct database connection (avoid session_start from config.php)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$health = [
    'server' => 'ServerC',
    'status' => 'online',
    'database' => 'disconnected',
    'timestamp' => date('Y-m-d H:i:s'),
    'services' => []
];

// Check database connection
if ($conn && !$conn->connect_error) {
    $health['database'] = 'connected';
    
    // Check both tables
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $health['services']['users_table'] = $row['count'] . ' users';
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $health['services']['items_table'] = $row['count'] . ' items';
    }
}

// Check UI pages
$health['services']['user_dashboard'] = file_exists(__DIR__ . '/user_dashboard.php') ? 'active' : 'missing';
$health['services']['user_login'] = file_exists(__DIR__ . '/user_login.php') ? 'active' : 'missing';
$health['services']['report_lost'] = file_exists(__DIR__ . '/report_lost.php') ? 'active' : 'missing';
$health['services']['report_found'] = file_exists(__DIR__ . '/report_found.php') ? 'active' : 'missing';

// Check ServerA reachability (cross-server communication test)
$serverA_url = 'http://localhost/Lostnfound/ServerA/api/health.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverA_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$health['services']['serverA_connection'] = ($http_code == 200) ? 'reachable' : 'unreachable';

// Check ServerB reachability (cross-server communication test)
$serverB_url = 'http://localhost/Lostnfound/ServerB/api/health.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverB_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$health['services']['serverB_connection'] = ($http_code == 200) ? 'reachable' : 'unreachable';

echo json_encode($health);
