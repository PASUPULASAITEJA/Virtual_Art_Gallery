<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please log in to make a purchase']);
    exit;
}

// Check if artwork_id is provided
if (!isset($_POST['artwork_id'])) {
    echo json_encode(['success' => false, 'error' => 'Artwork ID is required']);
    exit;
}

$artwork_id = $_POST['artwork_id'];
$buyer_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Get artwork details and verify it's still available for auction
    $stmt = $pdo->prepare("SELECT * FROM Artworks WHERE id = ? AND status = 'auction' AND (auction_end_time IS NULL OR auction_end_time > NOW())");
    $stmt->execute([$artwork_id]);
    $artwork = $stmt->fetch();

    if (!$artwork) {
        throw new Exception('Artwork is no longer available for purchase');
    }

    // Create transaction record
    $stmt = $pdo->prepare("INSERT INTO Transactions (artwork_id, buyer_id, amount, status, transaction_date) 
                          VALUES (?, ?, ?, 'completed', NOW())");
    $stmt->execute([$artwork_id, $buyer_id, $artwork['price']]);

    // Generate certificate number
    $certificate_number = 'CERT-' . strtoupper(uniqid()) . '-' . date('Ymd');

    // Create certificate
    $stmt = $pdo->prepare("INSERT INTO Certificates (artwork_id, buyer_id, certificate_number) 
                          VALUES (?, ?, ?)");
    $stmt->execute([$artwork_id, $buyer_id, $certificate_number]);

    // Update artwork status to sold and change owner
    $stmt = $pdo->prepare("UPDATE Artworks SET status = 'sold', owner_id = ? WHERE id = ?");
    $stmt->execute([$buyer_id, $artwork_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'certificate_number' => $certificate_number]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 