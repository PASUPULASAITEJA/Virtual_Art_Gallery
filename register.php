<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user';
    
    // Handle profile picture upload
    $profile_picture = '';
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . time() . '_' . basename($_FILES["profile_picture"]["name"]);
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $target_file;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, role, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $password, $role, $profile_picture]);
        header("Location: login.php");
        exit();
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Art Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        .bg-image {
            background: url('img-1.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
        }
        .bg-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            width: 100%;
            max-width: 350px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: linear-gradient(135deg, #2b5876 0%, #4e4376 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 1.2rem;
            text-align: center;
            font-size: 22px;
            font-weight: 500;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        .form-control:focus {
            background: white;
            border-color: #4e4376;
            box-shadow: none;
            transform: translateY(-2px);
            color: #2c3e50;
        }
        .form-control::placeholder {
            color: #6c757d;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2b5876 0%, #4e4376 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 0.5rem;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #4e4376 0%, #2b5876 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 67, 118, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }
        .text-center a {
            color: #1e90ff;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .text-center a:hover {
            color: #007bff;
        }
        .login-text {
            color: #6c757d;
        }
        .mb-3 {
            margin-bottom: 0.8rem !important;
        }
    </style>
</head>
<body class="bg-image">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg mx-auto">
                    <div class="card-header">
                        <h3 class="mb-0">Register</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                            
                            <div class="d-grid mt-3">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0 login-text" style="font-size: 0.9rem;">Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animations.js"></script>
</body>
</html>