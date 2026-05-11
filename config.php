<?php
session_start();

$host     = 'localhost';
$dbname   = 'accumulii';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

/**
 * Returns true if the current request has an authenticated session.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects to login.php if the user is not authenticated.
 * Call at the top of any page that requires a logged-in session.
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>
