<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            // Modified query to remove status check since your table doesn't have that column
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login timestamp
                $updateStmt = $pdo->prepare("UPDATE Users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Add JavaScript redirect as a fallback
                echo "<script>window.location.href = 'index.php';</script>";
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Art Marketplace</title>
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
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: linear-gradient(135deg, #2b5876 0%, #4e4376 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 1.5rem;
            text-align: center;
            font-size: 24px;
            font-weight: 500;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            color: #2c3e50;
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
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
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
            margin-bottom: 8px;
        }
        .text-decoration-none {
            color: #4e4376;
            transition: all 0.3s ease;
        }
        .text-decoration-none:hover {
            color: #2b5876;
        }
        .text-center a {
            color: #4e4376;
            font-weight: 500;
        }
        .text-center a:hover {
            color: #2b5876;
        }
        .register-text {
            color: #6c757d;
        }
        .register-text a {
            color: #1e90ff;
            text-decoration: none;
            font-weight: 500;
        }
        .register-text a:hover {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body class="bg-image">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header text-center bg-dark text-white py-3">
                        <h3 class="mb-0">Login</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                            <hr>
                            <p class="mb-1 register-text">Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
