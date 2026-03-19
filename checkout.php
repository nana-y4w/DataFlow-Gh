<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$walletBalance = getWalletBalance($user['id']);

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bundleId = intval($_POST['bundle_id'] ?? 0);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    if (!$bundleId || !$phone) {
        $error = 'All fields are required';
    } elseif (!validatePhone($phone)) {
        $error = 'Invalid phone number format. Use: 024XXXXXXX';
    } else {
        $bundle = getBundleById($bundleId);
        if (!$bundle) {
            $error = 'Bundle not found';
        } else {
            $price = ($user['role'] === 'agent') ? $bundle['agent_price'] : $bundle['user_price'];
            
            if ($walletBalance < $price) {
                $error = 'Insufficient wallet balance. Please top up.';
            } else {
                // Process order
                $transactionRef = 'TXN' . date('Ymd') . rand(1000, 9999);
                
                $db->getConnection()->beginTransaction();
                
                try {
                    // Deduct from wallet
                    $deducted = deductWalletBalance($user['id'], $price);
                    if (!$deducted) {
                        throw new Exception('Failed to deduct balance');
                    }
                    
                    // Create order
                    $stmt = $db->query(
                        "INSERT INTO orders (user_id, bundle_id, phone_number, network, data_amount, price, status, gateway, transaction_ref, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, 'placed', 'web', ?, NOW())",
                        [$user['id'], $bundleId, $phone, $bundle['network'], $bundle['data_amount'], $price, $transactionRef]
                    );
                    
                    if (!$stmt) {
                        throw new Exception('Failed to create order');
                    }
                    
                    $orderId = $db->lastInsertId();
                    
                    // Create notification
                    createNotification(
                        $user['id'],
                        'Order Placed',
                        "Your order for {$bundle['data_amount']} has been placed. Reference: {$transactionRef}",
                        'order'
                    );
                    
                    $db->getConnection()->commit();
                    
                    // Redirect to success page
                    header("Location: my-orders.php?success=1");
                    exit();
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollBack();
                    $error = 'Order failed: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get bundle if selected
$selectedBundle = null;
$bundleId = $_GET['bundle_id'] ?? ($_POST['bundle_id'] ?? 0);
if ($bundleId) {
    $selectedBundle = getBundleById($bundleId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
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
                <a href="bundles.php">Bundles</a>
                <a href="wallet.php">Wallet</a>
                <a href="my-orders.php">My Orders</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <main class="checkout-page">
        <h1 class="page-title">Checkout</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$selectedBundle): ?>
            <div class="alert alert-warning">
                No bundle selected. <a href="bundles.php">Browse bundles</a>
            </div>
        <?php else: ?>
            <div class="checkout-grid">
                <div class="checkout-form glass-card">
                    <h2>Complete Your Purchase</h2>
                    
                    <div class="order-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-item">
                            <span>Network:</span>
                            <span><?php echo $selectedBundle['network']; ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Bundle:</span>
                            <span><?php echo $selectedBundle['data_amount']; ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Price:</span>
                            <span class="price">₵<?php echo number_format(($user['role'] === 'agent') ? $selectedBundle['agent_price'] : $selectedBundle['user_price'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Your Balance:</span>
                            <span>₵<?php echo number_format($walletBalance, 2); ?></span>
                        </div>
                    </div>

                    <form method="POST" class="purchase-form">
                        <input type="hidden" name="bundle_id" value="<?php echo $selectedBundle['id']; ?>">

                        <div class="form-group">
                            <label>Phone Number to Receive Data</label>
                            <input type="tel" name="phone" class="form-control" 
                                   placeholder="024XXXXXXX" required pattern="^0[0-9]{9}$"
                                   value="<?php echo $user['phone']; ?>">
                        </div>

                        <button type="submit" class="btn-primary glass-btn" <?php echo ($walletBalance < (($user['role'] === 'agent') ? $selectedBundle['agent_price'] : $selectedBundle['user_price'])) ? 'disabled' : ''; ?>>
                            <span>Place Order</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <div class="payment-info glass-card">
                    <h3>Payment Method</h3>
                    <p>This purchase will be deducted from your wallet balance.</p>
                    
                    <?php if ($walletBalance < (($user['role'] === 'agent') ? $selectedBundle['agent_price'] : $selectedBundle['user_price'])): ?>
                        <div class="insufficient-balance">
                            <p>Insufficient balance. Please top up your wallet.</p>
                            <a href="wallet.php" class="btn-primary glass-btn">Top Up Wallet</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Secure payments via wallet system.</p>
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
