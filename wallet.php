<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$walletBalance = getWalletBalance($user['id']);
$unreadNotifications = getUnreadNotificationCount($user['id']);

// Check for payment callback
$paymentStatus = $_GET['payment'] ?? '';
$paymentRef = $_GET['ref'] ?? '';
$paymentMsg = $_GET['msg'] ?? '';

// Get wallet transactions
$stmt = $db->query(
    "SELECT * FROM wallet_transactions 
     WHERE user_id = ? 
     ORDER BY created_at DESC LIMIT 20",
    [$user['id']]
);
$transactions = $stmt->fetchAll();

// Get topup fee percentage
$topupFeePercent = floatval(getSetting('topup_fee_percentage') ?: 3);

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM wallet_transactions WHERE user_id = ? AND type = 'credit' AND payment_status = 'completed'", [$user['id']]);
$totalTopups = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM wallet_transactions WHERE user_id = ? AND type = 'debit'", [$user['id']]);
$totalSpent = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM wallet_transactions WHERE user_id = ? AND type = 'credit' AND payment_status = 'pending'", [$user['id']]);
$pendingTransactions = $stmt->fetch()['total'];

// Check if Moolre is enabled
$moolreEnabled = isMoolreEnabled();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - <?php echo SITE_NAME; ?></title>
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
                <a href="wallet.php" class="active">Wallet</a>
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
        <h1 class="page-title">My Wallet</h1>

        <?php if ($paymentStatus === 'success'): ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Payment Successful!</strong><br>
                <?php echo htmlspecialchars($paymentMsg ?: 'Your wallet has been credited successfully.'); ?>
                <?php if ($paymentRef): ?>
                    <br>Reference: <?php echo htmlspecialchars($paymentRef); ?>
                <?php endif; ?>
            </div>
        <?php elseif ($paymentStatus === 'failed'): ?>
            <div class="alert alert-error">
                <strong><i class="fas fa-times-circle"></i> Payment Failed</strong><br>
                <?php echo htmlspecialchars($paymentMsg ?: 'Your payment was not successful. Please try again.'); ?>
            </div>
        <?php elseif ($paymentStatus === 'error'): ?>
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-triangle"></i> Payment Error</strong><br>
                <?php echo htmlspecialchars($paymentMsg ?: 'There was an error processing your payment.'); ?>
            </div>
        <?php endif; ?>

        <!-- Wallet Cards -->
        <div class="wallet-cards">
            <div class="wallet-card primary glass-card">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-label">Balance</div>
                <div class="card-value">₵<?php echo number_format($walletBalance, 2); ?></div>
            </div>
            <div class="wallet-card glass-card">
                <div class="card-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="card-label">Top-ups</div>
                <div class="card-value"><?php echo $totalTopups; ?></div>
            </div>
            <div class="wallet-card glass-card">
                <div class="card-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="card-label">Spent</div>
                <div class="card-value"><?php echo $totalSpent; ?></div>
            </div>
            <div class="wallet-card glass-card">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-label">Pending</div>
                <div class="card-value"><?php echo $pendingTransactions; ?></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="action-btn primary" onclick="openTopupModal()">
                <i class="fas fa-plus"></i> Top Up
            </button>
            <a href="buy-data.php" class="action-btn">
                <i class="fas fa-shopping-cart"></i> Buy Data
            </a>
            <a href="bulk-purchases.php" class="action-btn">
                <i class="fas fa-boxes"></i> Bulk Purchase
            </a>
        </div>

        <!-- Transaction History -->
        <h2 class="section-title">Transaction History</h2>

        <div class="transaction-list">
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No transactions yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $txn): ?>
                    <div class="transaction-item">
                        <div class="transaction-icon <?php echo $txn['type']; ?>">
                            <i class="fas fa-<?php echo $txn['type'] === 'credit' ? 'arrow-down' : 'arrow-up'; ?>"></i>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-title">
                                <?php echo htmlspecialchars($txn['description']); ?>
                            </div>
                            <div class="transaction-meta">
                                <?php echo date('M d, Y • h:i A', strtotime($txn['created_at'])); ?>
                                <?php if ($txn['reference_number']): ?>
                                    <br>Ref: <?php echo htmlspecialchars($txn['reference_number']); ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($txn['type'] === 'credit' && isset($txn['payment_status'])): ?>
                                <span class="transaction-status status-<?php echo $txn['payment_status']; ?>">
                                    <?php echo ucfirst($txn['payment_status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="transaction-amount <?php echo $txn['type']; ?>">
                                <?php echo $txn['type'] === 'credit' ? '+' : '-'; ?>₵<?php echo number_format($txn['amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Top-up Modal -->
    <div class="modal" id="topupModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Top Up Wallet</h2>
                <button class="modal-close" onclick="closeTopupModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!$moolreEnabled): ?>
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Payment Gateway Unavailable</strong><br>
                        The payment gateway is currently being configured. Please check back later or contact support.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong><i class="fas fa-mobile-alt"></i> Mobile Money Payment</strong><br>
                        Pay securely with MTN, Telecel, or AirtelTigo Mobile Money.
                    </div>

                    <form id="topupForm">
                        <div class="form-group">
                            <label class="form-label">Amount (₵)</label>
                            <input type="number" class="form-control" id="topupAmount" name="amount" 
                                   placeholder="Enter amount" min="1" step="0.01" required oninput="updateFee()">
                        </div>

                        <div class="fee-breakdown" id="feeBreakdown" style="display: none;">
                            <div class="fee-item">
                                <span>Amount:</span>
                                <span id="displayAmount">₵0.00</span>
                            </div>
                            <div class="fee-item">
                                <span>Fee (<?php echo $topupFeePercent; ?>%):</span>
                                <span id="displayFee">₵0.00</span>
                            </div>
                            <div class="fee-item total">
                                <span>Total to Pay:</span>
                                <span id="displayTotal">₵0.00</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary glass-btn" style="width: 100%;" id="submitBtn">
                            <i class="fas fa-mobile-alt"></i> Pay with Mobile Money
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
    <script>
        const topupFeePercent = <?php echo $topupFeePercent; ?>;

        function updateFee() {
            const amount = parseFloat(document.getElementById('topupAmount').value) || 0;
            
            if (amount > 0) {
                const fee = (amount * topupFeePercent) / 100;
                const total = amount + fee;
                
                document.getElementById('displayAmount').textContent = '₵' + amount.toFixed(2);
                document.getElementById('displayFee').textContent = '₵' + fee.toFixed(2);
                document.getElementById('displayTotal').textContent = '₵' + total.toFixed(2);
                document.getElementById('feeBreakdown').style.display = 'block';
            } else {
                document.getElementById('feeBreakdown').style.display = 'none';
            }
        }

        function openTopupModal() {
            document.getElementById('topupModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeTopupModal() {
            document.getElementById('topupModal').classList.remove('show');
            document.body.style.overflow = '';
            document.getElementById('topupForm').reset();
            document.getElementById('feeBreakdown').style.display = 'none';
        }

        document.getElementById('topupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const amount = parseFloat(document.getElementById('topupAmount').value);
            
            if (!amount || amount < 1) {
                alert('Please enter a valid amount (minimum ₵1)');
                return;
            }

            const btn = document.getElementById('submitBtn');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner"></div> Processing...';

            const fee = (amount * topupFeePercent) / 100;
            const total = amount + fee;
            const reference = 'BG' + Date.now().toString().substr(-10);

            try {
                const response = await fetch('moolre-initiate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: amount.toFixed(2),
                        fee: fee.toFixed(2),
                        total: total.toFixed(2),
                        reference: reference
                    })
                });

                const result = await response.json();

                if (result.success && result.payment_url) {
                    window.location.href = result.payment_url;
                } else {
                    alert('❌ ' + (result.message || 'Failed to generate payment link'));
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                alert('❌ An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTopupModal();
            }
        });
    </script>
</body>
</html>
