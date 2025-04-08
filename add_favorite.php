<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Debug logging
error_log("Received request to add_favorite.php with method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set'));
error_log("Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Get artwork_id from various possible sources
$artwork_id = null;

// Check request method and get artwork_id accordingly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For application/json content type
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        $jsonInput = file_get_contents('php://input');
        error_log("JSON input: " . $jsonInput);
        if (!empty($jsonInput)) {
            $data = json_decode($jsonInput, true);
            if (is_array($data) && isset($data['artwork_id'])) {
                $artwork_id = $data['artwork_id'];
            }
        }
    } else {
        // Regular POST data
        if (isset($_POST['artwork_id'])) {
            $artwork_id = $_POST['artwork_id'];
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET request
    if (isset($_GET['artwork_id'])) {
        $artwork_id = $_GET['artwork_id'];
    }
}

// Log the extracted artwork_id
error_log("Extracted artwork_id: " . ($artwork_id ?? 'null'));

// Validate artwork_id
if ($artwork_id === null) {
    echo json_encode(['success' => false, 'error' => 'Missing artwork_id']);
    exit();
}

$artwork_id = intval($artwork_id);

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT 1 FROM Favorites WHERE user_id = ? AND artwork_id = ?");
    $stmt->execute([$_SESSION['user_id'], $artwork_id]);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM Favorites WHERE user_id = ? AND artwork_id = ?");
        $stmt->execute([$_SESSION['user_id'], $artwork_id]);
        $result = ['success' => true, 'isFavorite' => false];
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO Favorites (user_id, artwork_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $artwork_id]);
        $result = ['success' => true, 'isFavorite' => true];
    }
    
    // Log success
    error_log("Favorite operation success: " . json_encode($result));
    echo json_encode($result);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log($error);
    echo json_encode(['success' => false, 'error' => $error]);
}
?>
