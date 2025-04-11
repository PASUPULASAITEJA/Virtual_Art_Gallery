<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get categories for the form
$stmt = $pdo->query("SELECT * FROM Categories");
$categories = $stmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_ids = isset($_POST['categories']) ? $_POST['categories'] : [];
    
    // Handle image upload
    $image_path = '';
    if(isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] == 0) {
        $target_dir = "uploads/artworks/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . time() . '_' . basename($_FILES["artwork_image"]["name"]);
        if (move_uploaded_file($_FILES["artwork_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert artwork
        $stmt = $pdo->prepare("INSERT INTO Artworks (title, description, price, image_path, artist_id, owner_id, status, auction_end_time, uploaded_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $auction_end_time = ($_POST['status'] == 'auction') ? $_POST['auction_end_time'] : null;
        $stmt->execute([$title, $description, $price, $image_path, $_SESSION['user_id'], $_SESSION['user_id'], $_POST['status'], $auction_end_time]);
        $artwork_id = $pdo->lastInsertId();
        
        // Insert artwork categories
        if (!empty($category_ids)) {
            $stmt = $pdo->prepare("INSERT INTO Artwork_Categories (artwork_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $category_id) {
                $stmt->execute([$artwork_id, $category_id]);
            }
        }
        
        $pdo->commit();
        header("Location: artwork.php?id=" . $artwork_id);
        exit();
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Upload failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Artwork - Art Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Art Marketplace</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload_artwork.php">Upload Artwork</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Upload New Artwork</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Price ($)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="categories" class="form-label">Categories</label>
                                <select multiple class="form-select" id="categories" name="categories[]">
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple categories</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="artwork_image" class="form-label">Artwork Image</label>
                                <input type="file" class="form-control" id="artwork_image" name="artwork_image" accept="image/*" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Artwork Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="auction">Auction</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="auctionEndTimeGroup" style="display: none;">
                                <label for="auction_end_time" class="form-label">Auction End Time</label>
                                <input type="datetime-local" class="form-control" id="auction_end_time" name="auction_end_time">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Upload Artwork</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animations.js"></script>
    <script>
        document.getElementById('status').addEventListener('change', function() {
            const auctionEndTimeGroup = document.getElementById('auctionEndTimeGroup');
            if (this.value === 'auction') {
                auctionEndTimeGroup.style.display = 'block';
                document.getElementById('auction_end_time').required = true;
            } else {
                auctionEndTimeGroup.style.display = 'none';
                document.getElementById('auction_end_time').required = false;
            }
        });
    </script>
</body>
</html> 