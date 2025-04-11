<?php
session_start();
require_once 'config.php';

// Get categories
$stmt = $pdo->query("SELECT * FROM Categories");
$categories = $stmt->fetchAll();

// Filter by category
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'available';

// Build query
$query = "SELECT a.*, u.username as artist_name, c.category_name 
          FROM Artworks a 
          JOIN Users u ON a.artist_id = u.id 
          LEFT JOIN Artwork_Categories ac ON a.id = ac.artwork_id 
          LEFT JOIN Categories c ON ac.category_id = c.id 
          WHERE 1=1";

if ($category_filter) {
    $query .= " AND c.id = :category_id";
}
if ($status_filter) {
    if ($status_filter == 'auction') {
        // For auction items, only show those that haven't ended yet
        $query .= " AND a.status = 'auction' AND (a.auction_end_time IS NULL OR a.auction_end_time > NOW())";
    } else {
        $query .= " AND a.status = :status";
    }
}

$stmt = $pdo->prepare($query);
if ($category_filter) {
    $stmt->bindParam(':category_id', $category_filter);
}
if ($status_filter && $status_filter != 'auction') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$artworks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Art Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="d-flex align-items-center">
                <a class="navbar-brand" href="#">Art Marketplace</a>
                <ul class="navbar-nav ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'upload_artwork.php') ? 'active' : ''; ?>" href="upload_artwork.php">Upload Artwork</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>" href="profile.php">Profile</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form class="d-flex">
                    <select name="category" class="form-select me-2">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select me-2">
                        <option value="available" <?php echo ($status_filter == 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo ($status_filter == 'sold') ? 'selected' : ''; ?>>Sold</option>
                        <option value="auction" <?php echo ($status_filter == 'auction') ? 'selected' : ''; ?>>Auction</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
        </div>

        <!-- Artworks Grid -->
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach($artworks as $artwork): ?>
                <div class="col">
                    <div class="card h-100 animate-on-scroll-trigger">
                        <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">By <?php echo htmlspecialchars($artwork['artist_name']); ?></small>
                            </p>
                            <p class="card-text">
                                <?php echo htmlspecialchars(substr($artwork['description'], 0, 100)) . '...'; ?>
                            </p>
                            <p class="card-text">
                                <strong>Rs.<?php echo number_format($artwork['price']) ?></strong>
                            </p>
                            <?php if($artwork['status'] == 'auction' && $artwork['auction_end_time']): 
                                $end_time = strtotime($artwork['auction_end_time']);
                                $current_time = time();
                                if ($end_time > $current_time): ?>
                                <p class="card-text">
                                    <small class="text-muted">Auction ends: <?php echo date('M d, Y H:i', $end_time); ?></small>
                                </p>
                            <?php endif; endif; ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="artwork.php?id=<?php echo $artwork['id']; ?>" 
                                   class="btn btn-primary">View Details</a>
                                <?php if($artwork['status'] == 'auction' && $artwork['auction_end_time'] && strtotime($artwork['auction_end_time']) > time()): ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">
                                Category: <?php echo htmlspecialchars($artwork['category_name']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animations.js"></script>
    <script>
        // Add animation classes to elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in class to the cards
            document.querySelectorAll('.card').forEach(function(card, index) {
                // Stagger the animations slightly
                setTimeout(function() {
                    card.classList.add('fade-in');
                }, index * 100);
            });
            
            // Add slide-in animation to other elements
            document.querySelectorAll('.navbar, h1, h2, h3, .btn-primary').forEach(function(element) {
                element.classList.add('slide-in');
            });

            // Handle purchase button clicks
            document.querySelectorAll('.purchase-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const artworkId = this.dataset.artworkId;
                    const price = this.dataset.price;
                    
                    if (confirm(`Are you sure you want to purchase this artwork for $${price}?`)) {
                        // Create form data
                        const formData = new FormData();
                        formData.append('artwork_id', artworkId);
                        
                        fetch('purchase_auction.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(`Purchase successful!\nCertificate Number: ${data.certificate_number}`);
                                location.reload();
                            } else {
                                alert('Error: ' + data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while processing your purchase.');
                        });
                    }
                });
            });
        });
    </script>
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>