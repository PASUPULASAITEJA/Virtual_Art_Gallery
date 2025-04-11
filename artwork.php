<?php
session_start();
require_once 'config.php';

// Get artwork ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$artwork_id = intval($_GET['id']);

// Fetch artwork details
$stmt = $pdo->prepare("
    SELECT a.*, u.username as artist_name, u.profile_picture as artist_picture,
           GROUP_CONCAT(c.category_name) as categories
    FROM Artworks a
    JOIN Users u ON a.artist_id = u.id
    LEFT JOIN Artwork_Categories ac ON a.id = ac.artwork_id
    LEFT JOIN Categories c ON ac.category_id = c.id
    WHERE a.id = ?
    GROUP BY a.id
");
$stmt->execute([$artwork_id]);
$artwork = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artwork) {
    header("Location: index.php");
    exit();
}

// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.profile_picture
    FROM Reviews r
    JOIN Users u ON r.user_id = u.id
    WHERE r.artwork_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$artwork_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
if (count($reviews) > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = $total_rating / count($reviews);
}

// Check if user has favorited this artwork
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT 1 FROM Favorites WHERE user_id = ? AND artwork_id = ?");
    $stmt->execute([$_SESSION['user_id'], $artwork_id]);
    $is_favorited = (bool)$stmt->fetchColumn();
    
    // Check if user has already rated this artwork
    $stmt = $pdo->prepare("SELECT 1 FROM Reviews WHERE user_id = ? AND artwork_id = ?");
    $stmt->execute([$_SESSION['user_id'], $artwork_id]);
    $user_rated = (bool)$stmt->fetchColumn();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } elseif (!isset($_SESSION['user_id'])) {
        $error = "You must be logged in to perform this action.";
    } else {
        // CSRF token valid, now process form actions
        if (isset($_POST['review'])) {
            $rating = intval($_POST['rating']);
            $review_text = trim($_POST['review_text']);

            if ($rating < 1 || $rating > 5) {
                $error = "Rating must be between 1 and 5.";
            } elseif (empty($review_text)) {
                $error = "Review text cannot be empty.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO Reviews (user_id, artwork_id, rating, review_text, created_at) 
                                         VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$_SESSION['user_id'], $artwork_id, $rating, $review_text]);
                    $success = "Review submitted successfully.";
                    
                    // Refresh the page to see the new review
                    header("Location: artwork.php?id=" . $artwork_id);
                    exit();
                } catch (PDOException $e) {
                    $error = "Failed to submit review: " . $e->getMessage();
                }
            }
        }

        if (isset($_POST['quick_rating'])) {
            $rating = intval($_POST['quick_rating']);
            if ($rating < 1 || $rating > 5) {
                $error = "Rating must be between 1 and 5.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO Reviews (user_id, artwork_id, rating, created_at) 
                                         VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$_SESSION['user_id'], $artwork_id, $rating]);
                    $success = "Rating submitted.";
                    
                    // Refresh the page to see the new rating
                    header("Location: artwork.php?id=" . $artwork_id);
                    exit();
                } catch (PDOException $e) {
                    $error = "Failed to submit rating: " . $e->getMessage();
                }
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
    <title><?php echo htmlspecialchars($artwork['title']); ?> - Art Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        #reviewSection {
            transition: background-color 0.3s;
        }
        #reviewSection:hover {
            background-color: #f8f9fa;
        }
        .rating-stars {
            cursor: pointer;
        }
        .rating-stars i:hover,
        .rating-stars i.active {
            color: #ffc107 !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary ">
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="upload_artwork.php">Upload Artwork</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Artwork Image -->
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                     class="img-fluid rounded" 
                     alt="<?php echo htmlspecialchars($artwork['title']); ?>">
            </div>
            
            <!-- Artwork Details -->
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($artwork['title']); ?></h1>
                
                <div class="d-flex align-items-center mb-3">
                    <img src="<?php echo htmlspecialchars($artwork['artist_picture']); ?>" 
                         class="rounded-circle me-2" 
                         style="width: 40px; height: 40px; object-fit: cover;"
                         alt="Artist">
                    <div>
                        <p class="mb-0">By <?php echo htmlspecialchars($artwork['artist_name']); ?></p>
                        <small class="text-muted">
                            Posted <?php echo date('F j, Y', strtotime($artwork['uploaded_at'])); ?>
                        </small>
                    </div>
                </div>
                
                <p class="lead"><?php echo htmlspecialchars($artwork['description']); ?></p>
                
                <p class="h4 mb-3">$<?php echo number_format($artwork['price'], 2); ?></p>
                
                <div class="mb-3">
                    <span class="badge bg-secondary me-1">
                        <?php echo str_replace(',', '</span><span class="badge bg-secondary me-1">', htmlspecialchars($artwork['categories'])); ?>
                    </span>
                </div>
                
                <div class="d-flex gap-2 mb-4">
                    <?php if($artwork['status'] === 'available' && isset($_SESSION['user_id']) && $artwork['artist_id'] !== $_SESSION['user_id']): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal">
                            Purchase
                        </button>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-outline-danger favorite-btn" data-artwork-id="<?php echo $artwork['id']; ?>">
                            <?php echo $is_favorited ? '<i class="bi bi-heart-fill"></i>' : '<i class="bi bi-heart"></i>'; ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews Section (Clickable) -->

<div id="reviewSection" class="mt-4" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#reviewsModal">
    <h3 class="slide-in text-primary">Reviews</h3>
    <div class="mb-3">
        <span class="h4" style="color: #28a745;">0.0</span> <!-- Green color for rating -->
        <div class="d-inline-block" style="color: #ffc107;"> <!-- Yellow for stars -->
            <i class="bi bi-star"></i>
            <i class="bi bi-star"></i>
            <i class="bi bi-star"></i>
            <i class="bi bi-star"></i>
            <i class="bi bi-star"></i>
        </div>
        <span class="text-muted" style="color: #6c757d;">(0 reviews)</span> <!-- Gray text -->
    </div>
</div>
<button class="btn btn-outline-primary mb-3" data-bs-toggle="modal" data-bs-target="#reviewModal">Write a Review</button>


    <!-- Purchase Modal -->
    <div class="modal fade" id="purchaseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase Artwork</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to purchase this artwork for $<?php echo number_format($artwork['price'], 2); ?>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="process_purchase.php" method="POST">
                        <input type="hidden" name="artwork_id" value="<?php echo $artwork['id']; ?>">
                        <button type="submit" class="btn btn-primary">Confirm Purchase</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Submission Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="reviewForm">
                    <div class="modal-body">
                        <!-- Add CSRF token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <input type="hidden" name="review" value="1">
                        
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="">Select a rating</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                            <div class="invalid-feedback">Please select a rating</div>
                        </div>
                        <div class="mb-3">
                            <label for="review_text" class="form-label">Review</label>
                            <textarea class="form-control" id="review_text" name="review_text" 
                                    rows="3" required maxlength="1000" 
                                    data-bs-toggle="tooltip" title="Maximum 1000 characters"></textarea>
                            <div class="invalid-feedback">Please enter a review (max 1000 characters)</div>
                            <small class="text-muted">Characters remaining: <span id="charCount">1000</span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reviews Detail Modal -->
    <div class="modal fade" id="reviewsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">All Reviews for <?php echo htmlspecialchars($artwork['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach($reviews as $review): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?php echo htmlspecialchars($review['profile_picture']); ?>" 
                                             class="rounded-circle me-2" 
                                             style="width: 32px; height: 32px; object-fit: cover;"
                                             alt="Reviewer">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($review['username']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-warning mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'] ?: 'No review text provided.')); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No reviews yet.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animations.js"></script>
    <script>
        // Handle favorite button clicks
        document.querySelector('.favorite-btn')?.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default behavior
            const artworkId = this.dataset.artworkId;
            console.log('Favoriting artwork ID:', artworkId); // Debug
            
            const buttonElement = this; // Save reference to the button
            
            // Create form data
            const formData = new FormData();
            formData.append('artwork_id', artworkId);
            
            // Try with form data instead of JSON
            fetch('add_favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status); // Debug
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data); // Debug
                if(data.success) {
                    buttonElement.innerHTML = data.isFavorite ? '<i class="bi bi-heart-fill"></i>' : '<i class="bi bi-heart"></i>';
                } else {
                    console.error('Error:', data.error);
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error: ' + error.message);
            });
        });

        // Handle quick rating stars
        const stars = document.querySelectorAll('#quickRatingStars i');
const ratingInput = document.getElementById('quickRatingInput');
const submitBtn = document.getElementById('submitRatingBtn');

stars.forEach((star, index) => {
    star.addEventListener('click', function () {
        const rating = this.dataset.value;

        // Toggle off if same rating is clicked again
        if (ratingInput.value === rating) {
            ratingInput.value = '';
            stars.forEach(s => s.classList.remove('active'));
            submitBtn.disabled = true;
            return;
        }

        ratingInput.value = rating;

        stars.forEach(s => s.classList.remove('active'));
        for (let i = 0; i < rating; i++) {
            stars[i].classList.add('active');
        }

        submitBtn.disabled = false;
    });
});

function handleQuickRatingSubmit(event) {
    if (!ratingInput.value) {
        alert('Please select a rating before submitting.');
        event.preventDefault(); // Prevent form submission
        return false;
    }
    return true;
}


        // Add this JavaScript after the Bootstrap script
        document.addEventListener('DOMContentLoaded', function() {
            // Character counter for review text
            const reviewText = document.getElementById('review_text');
            const charCount = document.getElementById('charCount');
            
            reviewText?.addEventListener('input', function() {
                const remaining = 1000 - this.value.length;
                charCount.textContent = remaining;
                if (remaining < 0) {
                    charCount.classList.add('text-danger');
                } else {
                    charCount.classList.remove('text-danger');
                }
            });

            // Form validation
            const reviewForm = document.getElementById('reviewForm');
            reviewForm?.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                this.classList.add('was-validated');
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>