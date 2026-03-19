<?php
require_once 'config.php';

$network = $_GET['network'] ?? 'all';
$bundles = [];

if ($network !== 'all') {
    $bundles = getAllBundles($network);
} else {
    $bundles = array_merge(
        getAllBundles('MTN'),
        getAllBundles('Telecel'),
        getAllBundles('AT_Ishare'),
        getAllBundles('AT_Bigtime')
    );
}

// Get user role for pricing
$userRole = 'user';
if (isLoggedIn()) {
    $user = getCurrentUser();
    $userRole = $user['role'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Bundles - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="canvas-container"></div>

    <nav class="glass-nav">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-text"><?php echo SITE_NAME; ?></span>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="bundles.php" class="active">Bundles</a>
                <a href="services.php">Services</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <?php if (isLoggedIn()): ?>
                    <a href="wallet.php">Wallet</a>
                    <a href="my-orders.php">My Orders</a>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="signup.php" class="signup-btn">Sign Up</a>
                <?php endif; ?>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <main class="bundles-page">
        <h1 class="page-title">Choose Your <span>Data Bundle</span></h1>
        
        <!-- Network Tabs -->
        <div class="network-tabs">
            <a href="bundles.php?network=all" class="tab-btn <?php echo $network === 'all' ? 'active' : ''; ?>">All Networks</a>
            <a href="bundles.php?network=MTN" class="tab-btn <?php echo $network === 'MTN' ? 'active' : ''; ?>">MTN</a>
            <a href="bundles.php?network=Telecel" class="tab-btn <?php echo $network === 'Telecel' ? 'active' : ''; ?>">Telecel</a>
            <a href="bundles.php?network=AT_Ishare" class="tab-btn <?php echo $network === 'AT_Ishare' ? 'active' : ''; ?>">AirtelTigo</a>
        </div>

        <!-- Bundles Grid -->
        <div class="bundles-grid">
            <?php if (empty($bundles)): ?>
                <p class="no-bundles">No bundles available for this network.</p>
            <?php else: ?>
                <?php foreach ($bundles as $bundle): ?>
                    <?php
                    $price = ($userRole === 'agent') ? $bundle['agent_price'] : $bundle['user_price'];
                    $networkClass = '';
                    switch($bundle['network']) {
                        case 'MTN':
                            $networkClass = 'mtn';
                            break;
                        case 'Telecel':
                            $networkClass = 'telecel';
                            break;
                        case 'AT_Ishare':
                        case 'AT_Bigtime':
                            $networkClass = 'airtel';
                            break;
                    }
                    ?>
                    <div class="bundle-card glass-card <?php echo $networkClass; ?>" data-id="<?php echo $bundle['id']; ?>">
                        <div class="bundle-header">
                            <span class="network-badge <?php echo $networkClass; ?>"><?php echo $bundle['network']; ?></span>
                        </div>
                        
                        <h3 class="bundle-name"><?php echo $bundle['data_amount']; ?></h3>
                        
                        <div class="price-container">
                            <span class="price-label">Price</span>
                            <span class="bundle-price">₵<?php echo number_format($price, 2); ?></span>
                        </div>
                        
                        <div class="bundle-actions">
                            <?php if (isLoggedIn()): ?>
                                <button class="buy-btn" onclick="buyNow(<?php echo $bundle['id']; ?>)">
                                    <span>Buy Now</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            <?php else: ?>
                                <a href="login.php?redirect=bundles.php" class="buy-btn">
                                    <span>Login to Buy</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Ghana's fastest-growing data bundle platform.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Networks</h4>
                <ul>
                    <li><a href="bundles.php?network=MTN">MTN Bundles</a></li>
                    <li><a href="bundles.php?network=Telecel">Telecel Bundles</a></li>
                    <li><a href="bundles.php?network=AT_Ishare">AirtelTigo Bundles</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three-background.js"></script>
    <script src="js/main.js"></script>
    <script>
        function buyNow(bundleId) {
            sessionStorage.setItem('selectedBundle', bundleId);
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>
