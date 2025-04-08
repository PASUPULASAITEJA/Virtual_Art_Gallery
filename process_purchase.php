<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['artwork_id'])) {
    header("Location: index.php");
    exit();
}

$artwork_id = $_POST['artwork_id'];

try {
    $pdo->beginTransaction();
    
    // Get artwork details
    $stmt = $pdo->prepare("SELECT * FROM Artworks WHERE id = ? AND status = 'available' FOR UPDATE");
    $stmt->execute([$artwork_id]);
    $artwork = $stmt->fetch();
    
    if (!$artwork) {
        throw new Exception("Artwork not available");
    }
    
    if ($artwork['artist_id'] == $_SESSION['user_id']) {
        throw new Exception("Cannot purchase your own artwork");
    }
    
    // Create transaction
    $stmt = $pdo->prepare("INSERT INTO Transactions (artwork_id, buyer_id, amount, status, transaction_date) 
                          VALUES (?, ?, ?, 'completed', NOW())");
    $stmt->execute([$artwork_id, $_SESSION['user_id'], $artwork['price']]);
    
    // Update artwork status
    $stmt = $pdo->prepare("UPDATE Artworks SET status = 'sold' WHERE id = ?");
    $stmt->execute([$artwork_id]);
    
    // Create notification for seller
    $stmt = $pdo->prepare("INSERT INTO Notifications (user_id, message, is_read, created_at) 
                          VALUES (?, ?, false, NOW())");
    $message = "Your artwork '" . $artwork['title'] . "' has been purchased!";
    $stmt->execute([$artwork['artist_id'], $message]);
    
    $pdo->commit();
    header("Location: artwork.php?id=" . $artwork_id);
} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: artwork.php?id=" . $artwork_id);
}
?> 