<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Get user's artworks
    $stmt = $pdo->prepare("
        SELECT a.*, 
               COUNT(DISTINCT f.user_id) as favorite_count,
               COUNT(DISTINCT r.id) as review_count,
               AVG(r.rating) as avg_rating
        FROM Artworks a
        LEFT JOIN Favorites f ON a.id = f.artwork_id
        LEFT JOIN Reviews r ON a.id = r.artwork_id
        WHERE a.artist_id = ?
        GROUP BY a.id
        ORDER BY a.uploaded_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $artworks = $stmt->fetchAll();

    // Get user's purchases
    $stmt = $pdo->prepare("
        SELECT t.*, a.title, a.image_path, u.username as seller_name
        FROM Transactions t
        JOIN Artworks a ON t.artwork_id = a.id
        JOIN Users u ON a.artist_id = u.id
        WHERE t.buyer_id = ?
        ORDER BY t.transaction_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $purchases = $stmt->fetchAll();

    // Get user's sales
    $stmt = $pdo->prepare("
        SELECT t.*, a.title, a.image_path, u.username as buyer_name
        FROM Transactions t
        JOIN Artworks a ON t.artwork_id = a.id
        JOIN Users u ON t.buyer_id = u.id
        WHERE a.artist_id = ?
        ORDER BY t.transaction_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sales = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $location = htmlspecialchars(trim($_POST['location']));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Handle profile picture upload
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                $target_dir = "uploads/profiles/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $target_file = $target_dir . time() . '_' . basename($_FILES["profile_picture"]["name"]);
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    $profile_picture = $target_file;
                } else {
                    $error = "Failed to upload profile picture.";
                }
            } else {
                $error = "Invalid file type for profile picture.";
            }
        }

        if (!isset($error)) {
            try {
                $stmt = $pdo->prepare("UPDATE Users SET username = ?, email = ?, profile_picture = ?, location = ? WHERE id = ?");
                $stmt->execute([$username, $email, $profile_picture, $location, $_SESSION['user_id']]);
                $_SESSION['username'] = $username;
                header("Location: profile.php");
                exit();
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Art Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="animations.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="d-flex align-items-center">
                <a class="navbar-brand" href="index.php">Art Marketplace</a>
                <ul class="navbar-nav ms-3">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload_artwork.php">Upload Artwork</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
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

    <div class="container mt-4">
        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?: 'uploads/profiles/default.jpg'); ?>" 
                             class="rounded-circle mb-3 profile-picture" 
                             alt="Profile Picture">
                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                        <?php if(!empty($user['location'])): ?>
                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt-fill"></i> 
                                <?php echo htmlspecialchars($user['location']); ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'] ?? '1970-01-01')); ?></p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            Edit Profile
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabs for Artworks, Purchases, and Sales -->
            <div class="col-md-8 mt-4 profile-tabs">
                <div class="nav-container">
                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="artworks-tab" data-bs-toggle="tab" href="#artworks">
                                <i class="bi bi-palette me-2"></i>My Artworks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="purchases-tab" data-bs-toggle="tab" href="#purchases">
                                <i class="bi bi-bag me-2"></i>Purchases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="sales-tab" data-bs-toggle="tab" href="#sales">
                                <i class="bi bi-graph-up me-2"></i>Sales
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="tab-content mt-4">
                    <!-- Artworks Tab -->
                    <div class="tab-pane fade show active" id="artworks">
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach($artworks as $artwork): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                             class="card-img-top" 
                                             style="height: 200px; object-fit: cover;"
                                             alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Posted <?php echo date('F j, Y', strtotime($artwork['uploaded_at'])); ?>
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                <strong>Price:</strong> $<?php echo number_format($artwork['price'], 2); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="bi bi-heart-fill text-danger"></i> <?php echo $artwork['favorite_count']; ?>
                                                    <i class="bi bi-star-fill text-warning ms-2"></i> 
                                                    <?php echo number_format($artwork['avg_rating'], 1); ?>
                                                    (<?php echo $artwork['review_count']; ?>)
                                                </div>
                                                <span class="badge bg-<?php echo $artwork['status'] === 'available' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($artwork['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <a href="artwork.php?id=<?php echo $artwork['id']; ?>" class="btn btn-primary btn-sm">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Purchases Tab -->
                    <div class="tab-pane fade" id="purchases">
                        <?php foreach($purchases as $purchase): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <img src="<?php echo htmlspecialchars($purchase['image_path']); ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?php echo htmlspecialchars($purchase['title']); ?>">
                                        </div>
                                        <div class="col-md-9">
                                            <h5 class="card-title"><?php echo htmlspecialchars($purchase['title']); ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Purchased from <?php echo htmlspecialchars($purchase['seller_name']); ?>
                                                    on <?php echo date('F j, Y', strtotime($purchase['transaction_date'])); ?>
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                <strong>Amount:</strong> $<?php echo number_format($purchase['amount'], 2); ?>
                                            </p>
                                            <a href="artwork.php?id=<?php echo $purchase['artwork_id']; ?>" 
                                               class="btn btn-primary btn-sm">View Artwork</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Sales Tab -->
                    <div class="tab-pane fade" id="sales">
                        <?php foreach($sales as $sale): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <img src="<?php echo htmlspecialchars($sale['image_path']); ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?php echo htmlspecialchars($sale['title']); ?>">
                                        </div>
                                        <div class="col-md-9">
                                            <h5 class="card-title"><?php echo htmlspecialchars($sale['title']); ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Sold to <?php echo htmlspecialchars($sale['buyer_name']); ?>
                                                    on <?php echo date('F j, Y', strtotime($sale['transaction_date'])); ?>
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                <strong>Amount:</strong> $<?php echo number_format($sale['amount'], 2); ?>
                                            </p>
                                            <a href="artwork.php?id=<?php echo $sale['artwork_id']; ?>" 
                                               class="btn btn-primary btn-sm">View Artwork</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($user['location']); ?>" 
                                   placeholder="e.g., City, Country">
                        </div>
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animations.js"></script>
</body>
</html>