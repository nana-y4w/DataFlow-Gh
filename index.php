<?php
require_once 'config.php';

// Get bundles for display
$mtnBundles = getAllBundles('MTN');
$telecelBundles = getAllBundles('Telecel');
$airtelBundles = array_merge(
    getAllBundles('AT_Ishare'),
    getAllBundles('AT_Bigtime')
);

// Get statistics
$statsStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM orders WHERE status = 'delivered') as total_orders,
        (SELECT SUM(price) FROM orders WHERE status = 'delivered') as total_revenue
");
$stats = $statsStmt->fetch();

// Get testimonials from database (if you have a testimonials table)
$testimonials = [];
$testStmt = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6");
if ($testStmt) {
    $testimonials = $testStmt->fetchAll();
} else {
    // Fallback testimonials
    $testimonials = [
        ['name' => 'Kwame Asante', 'location' => 'Accra', 'rating' => 5, 'comment' => 'Best data prices in Ghana! Delivery is always instant.'],
        ['name' => 'Abena Mensah', 'location' => 'Kumasi', 'rating' => 5, 'comment' => 'The no-expiry Telecel bundle is perfect for me. Never loses data!'],
        ['name' => 'Yaw Ofori', 'location' => 'Takoradi', 'rating' => 5, 'comment' => 'Saved 20% on my monthly MTN bundle. Will definitely use again.'],
        ['name' => 'Esi Fynn', 'location' => 'Cape Coast', 'rating' => 5, 'comment' => 'Customer service is excellent. They helped me immediately.'],
        ['name' => 'Kojo Adams', 'location' => 'Tema', 'rating' => 4, 'comment' => 'Very reliable service. Always delivers within minutes.'],
        ['name' => 'Ama Serwaa', 'location' => 'Accra', 'rating' => 5, 'comment' => 'The wallet system is so convenient. Love it!']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Ghana's #1 Data Bundle Provider</title>
    <meta name="description" content="Buy cheap MTN, Telecel, and AirtelTigo data bundles in Ghana. Instant delivery, no expiry options, best prices.">
    <meta name="keywords" content="MTN bundles, Telecel bundles, AirtelTigo bundles, data bundles Ghana, cheap data">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- 3D Background -->
    <div id="canvas-container"></div>

    <!-- Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-text"><?php echo SITE_NAME; ?></span>
            </div>
            <div class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="bundles.php">Bundles</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="glitch" data-text="Cheapest Data in Ghana">Cheapest Data in <span>Ghana</span></h1>
                <p class="hero-subtitle">MTN • Telecel • AirtelTigo • Instant Delivery • No Expiry Options</p>
                
                <!-- Live Stats -->
                <div class="hero-stats">
                    <div class="stat-item glass-stat">
                        <span class="stat-number">₵<?php echo number_format($mtnBundles[0]['user_price'] ?? 2.50, 2); ?></span>
                        <span class="stat-label">Starting Price</span>
                    </div>
                    <div class="stat-item glass-stat">
                        <span class="stat-number">30sec</span>
                        <span class="stat-label">Delivery Time</span>
                    </div>
                    <div class="stat-item glass-stat">
                        <span class="stat-number"><?php echo number_format($stats['total_users'] ?? 0); ?>+</span>
                        <span class="stat-label">Happy Customers</span>
                    </div>
                </div>

                <div class="hero-buttons">
                    <a href="bundles.php" class="btn-primary glass-btn">
                        <span>Buy Data Now</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#testimonials" class="glass-btn">
                        <span>See Reviews</span>
                    </a>
                </div>
            </div>

            <!-- 3D Floating Cards -->
            <div class="hero-3d-cards">
                <div class="floating-card mtn-card">
                    <div style="font-size: 2rem;">MTN</div>
                </div>
                <div class="floating-card telecel-card">
                    <div style="font-size: 1.5rem;">Telecel</div>
                </div>
                <div class="floating-card airtel-card">
                    <div style="font-size: 1.2rem;">AirtelTigo</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Networks Section -->
    <section class="networks">
        <h2 class="section-title">Our <span>Networks</span></h2>
        <div class="networks-grid">
            <div class="network-card mtn glass-card">
                <div class="network-icon">MTN</div>
                <h3>MTN Ghana</h3>
                <ul>
                    <li><i class="fas fa-check"></i> Daily bundles from ₵<?php echo number_format($mtnBundles[0]['user_price'] ?? 2.50, 2); ?></li>
                    <li><i class="fas fa-check"></i> Weekly & Monthly plans</li>
                    <li><i class="fas fa-check"></i> 4G/5G ready</li>
                </ul>
                <a href="bundles.php?network=MTN" class="network-btn">View MTN Bundles →</a>
            </div>
            <div class="network-card telecel glass-card">
                <div class="network-icon">TEL</div>
                <h3>Telecel Ghana</h3>
                <ul>
                    <li><i class="fas fa-check"></i> No expiry bundles</li>
                    <li><i class="fas fa-check"></i> From ₵<?php echo number_format($telecelBundles[0]['user_price'] ?? 2.50, 2); ?></li>
                    <li><i class="fas fa-check"></i> 10GB+ monthly options</li>
                </ul>
                <a href="bundles.php?network=Telecel" class="network-btn">View Telecel Bundles →</a>
            </div>
            <div class="network-card airtel glass-card">
                <div class="network-icon">AIR</div>
                <h3>AirtelTigo</h3>
                <ul>
                    <li><i class="fas fa-check"></i> Best value daily</li>
                    <li><i class="fas fa-check"></i> From ₵<?php echo number_format($airtelBundles[0]['user_price'] ?? 2.00, 2); ?></li>
                    <li><i class="fas fa-check"></i> Night bundles available</li>
                </ul>
                <a href="bundles.php?network=AT_Ishare" class="network-btn">View AirtelTigo Bundles →</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Why Choose <span>Us</span></h2>
        <div class="features-grid">
            <div class="feature-card glass-card">
                <i class="fas fa-bolt"></i>
                <h3>Instant Delivery</h3>
                <p>Data delivered to your phone in under 30 seconds</p>
            </div>
            <div class="feature-card glass-card">
                <i class="fas fa-tags"></i>
                <h3>Best Prices</h3>
                <p>Save up to 30% compared to network prices</p>
            </div>
            <div class="feature-card glass-card">
                <i class="fas fa-infinity"></i>
                <h3>No Expiry Options</h3>
                <p>Data that never expires (Telecel only)</p>
            </div>
            <div class="feature-card glass-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Payments</h3>
                <p>Paystack & Moolre secure payments</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <h2 class="section-title">How It <span>Works</span></h2>
        <div class="steps-container">
            <div class="step glass-card">
                <div class="step-number">1</div>
                <i class="fas fa-mouse-pointer"></i>
                <h4>Choose Bundle</h4>
                <p>Select your network and data package</p>
            </div>
            <div class="step glass-card">
                <div class="step-number">2</div>
                <i class="fas fa-phone-alt"></i>
                <h4>Enter Number</h4>
                <p>Provide the phone number to receive data</p>
            </div>
            <div class="step glass-card">
                <div class="step-number">3</div>
                <i class="fas fa-mobile-alt"></i>
                <h4>Pay via Mobile Money</h4>
                <p>Secure payment via Paystack or Moolre</p>
            </div>
            <div class="step glass-card">
                <div class="step-number">4</div>
                <i class="fas fa-tachometer-alt"></i>
                <h4>Instant Delivery</h4>
                <p>Data sent to your phone in under 30 seconds</p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <h2 class="section-title">What Our <span>Customers Say</span></h2>
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $testimonial): ?>
            <div class="testimonial-card glass-card">
                <div class="stars">
                    <?php for($i = 0; $i < ($testimonial['rating'] ?? 5); $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                </div>
                <p>"<?php echo htmlspecialchars($testimonial['comment'] ?? $testimonial['message'] ?? ''); ?>"</p>
                <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                <div class="location"><?php echo htmlspecialchars($testimonial['location'] ?? 'Ghana'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Ghana's fastest-growing data bundle platform. Instant delivery, best prices.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="bundles.php">Data Bundles</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Networks</h4>
                <ul>
                    <li><a href="bundles.php?network=MTN">MTN Bundles</a></li>
                    <li><a href="bundles.php?network=Telecel">Telecel Bundles</a></li>
                    <li><a href="bundles.php?network=AT_Ishare">AirtelTigo Bundles</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact</h4>
                <p><i class="fas fa-phone"></i> <?php echo getSetting('site_phone') ?? '+233 24 123 4567'; ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo getSetting('site_email') ?? 'support@example.com'; ?></p>
                <p><i class="fas fa-map-marker-alt"></i> Accra, Ghana</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three-background.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
