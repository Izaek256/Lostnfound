<?php
/**
 * API: Session Status
 * 
 * GET /api/session_status.php
 * 
 * Returns current authenticated session information without requiring credentials.
 * Response:
 * - success: boolean
 * - user_id: int|null
 * - username: string|null
 * - email: string|null
 * - is_admin: int|null (0/1)
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(['error' => 'Method not allowed'], 405);
}

$logged_in = isset($_SESSION['user_id']);

$data = [
    'success' => $logged_in,
];

if ($logged_in) {
    $data['user_id'] = $_SESSION['user_id'];
    $data['username'] = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    $data['email'] = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
    $data['is_admin'] = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;
}

sendJSONResponse($data);
