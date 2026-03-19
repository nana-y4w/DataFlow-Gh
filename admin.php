<?php
require_once '../config.php';

// Redirect if already logged in as admin
if (isLoggedIn() && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-login-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header .subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .admin-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
            color: white;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .login-form {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--glass-shadow);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }
        
        .form-group .input-icon {
            position: relative;
        }
        
        .form-group .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--mtn-yellow);
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .back-link {
            margin-top: 1rem;
            text-align: center;
        }
        
        .back-link a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: var(--mtn-yellow);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
    </style>
</head>
<body>
    <!-- 3D Background -->
    <div id="canvas-container"></div>

    <div class="admin-login-body">
        <div class="login-container">
            <div class="login-header">
                <h1 class="glitch" data-text="<?php echo SITE_NAME; ?>"><?php echo SITE_NAME; ?></h1>
                <div class="subtitle">Administrator Portal</div>
                <span class="admin-badge">🔐 ADMIN ACCESS</span>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    Invalid email or password
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="../auth.php">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="redirect" value="admin/dashboard.php">
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="admin@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Access Dashboard
                </button>
            </form>

            <div class="back-link">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../js/three-background.js"></script>
</body>
</html>
