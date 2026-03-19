<?php
require_once '../config.php';
requireAdmin();

$user = getCurrentUser();
$unreadNotifications = getUnreadNotificationCount($user['id']);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    
    $validStatuses = ['placed', 'processing', 'delivered', 'failed'];
    
    if (in_array($newStatus, $validStatuses)) {
        // Get order details
        $orderStmt = $db->query("SELECT o.*, u.email, u.full_name FROM orders o 
                                 JOIN users u ON o.user_id = u.id 
                                 WHERE o.id = ?", [$orderId]);
        $order = $orderStmt->fetch();
        
        if ($order) {
            $oldStatus = $order['status'];
            
            // Update order
            $db->query("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
            
            // Handle refund if failed
            if ($newStatus === 'failed' && $oldStatus !== 'failed') {
                updateWalletBalance($order['user_id'], $order['price']);
            }
            
            // Create notification
            createNotification(
                $order['user_id'],
                'Order Status Updated',
                "Your order #{$orderId} is now {$newStatus}",
                'order'
            );
            
            // Send email
            sendOrderStatusEmail(
                $order['email'],
                $order['full_name'],
                $orderId,
                $newStatus,
                $order['network'],
                $order['data_amount'],
                $order['phone_number']
            );
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $orderId = intval($_POST['order_id']);
    $db->query("DELETE FROM orders WHERE id = ?", [$orderId]);
}

// Filters
$search = $_GET['search'] ?? '';
$network = $_GET['network'] ?? '';
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';

// Build query
$sql = "SELECT o.*, u.full_name, u.email FROM orders o 
        JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (o.phone_number LIKE ? OR o.transaction_ref LIKE ? OR u.full_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($network) {
    $sql .= " AND o.network = ?";
    $params[] = $network;
}

if ($status) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

if ($date === 'today') {
    $sql .= " AND DATE(o.created_at) = CURDATE()";
} elseif ($date === 'week') {
    $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date === 'month') {
    $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->query($sql, $params);
$orders = $stmt->fetchAll();

// Get statistics
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM orders"); $stats['total'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'placed'"); $stats['placed'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'processing'"); $stats['processing'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered'"); $stats['delivered'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'failed'"); $stats['failed'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Admin styles (same as dashboard) */
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: var(--glass-bg); backdrop-filter: blur(10px); border-right: 1px solid var(--glass-border); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 100; padding: 2rem 0; }
        .sidebar-header { padding: 0 1.5rem 2rem; text-align: center; border-bottom: 1px solid var(--glass-border); }
        .sidebar-header .logo { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .admin-badge { display: inline-block; padding: 0.3rem 1rem; background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red)); border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
        .sidebar-nav { padding: 2rem 1rem; }
        .nav-section { margin-bottom: 2rem; }
        .nav-section-title { font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem; padding-left: 0.5rem; }
        .nav-item { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1rem; color: var(--text); text-decoration: none; border-radius: 10px; transition: all 0.3s; margin: 0.2rem 0; }
        .nav-item:hover { background: rgba(255,255,255,0.1); }
        .nav-item.active { background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red)); }
        .nav-item i { width: 20px; text-align: center; }
        .admin-main { flex: 1; margin-left: 260px; padding: 2rem; }
        .admin-topbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border); border-radius: 15px; margin-bottom: 2rem; }
        .page-title { font-size: 1.5rem; font-weight: 700; }
        .mobile-menu-btn { display: none; background: none; border: none; color: var(--text); font-size: 1.5rem; cursor: pointer; }
        .admin-profile { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 50px; }
        .profile-avatar { width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red)); display: flex; align-items: center; justify-content: center; font-weight: 600; }
        
        /* Stats bar */
        .stats-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-pill {
            padding: 0.5rem 1.5rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s;
        }
        
        .stat-pill:hover {
            border-color: var(--mtn-yellow);
        }
        
        .stat-pill.active {
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
            border-color: transparent;
        }
        
        .stat-pill .count {
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 50px;
            font-size: 0.8rem;
        }
        
        /* Filters */
        .filters-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text);
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--mtn-yellow);
        }
        
        .filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .filter-btn {
            padding: 0.8rem 1.5rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.primary {
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
            border: none;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Orders grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .order-card {
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .order-id {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--mtn-yellow);
        }
        
        .order-status-select {
            padding: 0.3rem 0.5rem;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            border-radius: 5px;
            color: var(--text);
            cursor: pointer;
        }
        
        .order-status-select option[value="placed"] { color: var(--info); }
        .order-status-select option[value="processing"] { color: var(--warning); }
        .order-status-select option[value="delivered"] { color: var(--success); }
        .order-status-select option[value="failed"] { color: var(--danger); }
        
        .customer-info {
            margin-bottom: 1rem;
        }
        
        .customer-name {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .customer-email {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .order-details {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 0.3rem 0;
            font-size: 0.9rem;
        }
        
        .order-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 1rem 0;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .order-actions button {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: rgba(59, 130, 246, 0.2);
            color: var(--info);
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .btn-view:hover { background: var(--info); color: white; }
        .btn-delete:hover { background: var(--danger); color: white; }
        
        @media (max-width: 1024px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-main { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .admin-sidebar.show { transform: translateX(0); }
            .orders-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <div class="admin-container">
        <!-- Sidebar (same as dashboard) -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="logo"><?php echo SITE_NAME; ?></div>
                <span class="admin-badge">ADMIN PANEL</span>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i> Analytics</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
                    <a href="bundles.php" class="nav-item"><i class="fas fa-database"></i> Bundles</a>
                    <a href="orders.php" class="nav-item active"><i class="fas fa-shopping-cart"></i> Orders</a>
                    <a href="payments.php" class="nav-item"><i class="fas fa-money-bill-wave"></i> Payments</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Communication</div>
                    <a href="broadcast.php" class="nav-item"><i class="fas fa-bullhorn"></i> Broadcast</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
                    <a href="../auth.php?action=logout" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <!-- Top Bar -->
            <div class="admin-topbar">
                <div class="page-title">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    Manage Orders
                </div>
                <div class="admin-profile">
                    <div class="profile-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
                    <div><?php echo htmlspecialchars($user['full_name']); ?></div>
                </div>
            </div>

            <!-- Status Stats -->
            <div class="stats-bar">
                <a href="?status=" class="stat-pill <?php echo !$status ? 'active' : ''; ?>">
                    All <span class="count"><?php echo $stats['total']; ?></span>
                </a>
                <a href="?status=placed" class="stat-pill <?php echo $status === 'placed' ? 'active' : ''; ?>">
                    Placed <span class="count"><?php echo $stats['placed']; ?></span>
                </a>
                <a href="?status=processing" class="stat-pill <?php echo $status === 'processing' ? 'active' : ''; ?>">
                    Processing <span class="count"><?php echo $stats['processing']; ?></span>
                </a>
                <a href="?status=delivered" class="stat-pill <?php echo $status === 'delivered' ? 'active' : ''; ?>">
                    Delivered <span class="count"><?php echo $stats['delivered']; ?></span>
                </a>
                <a href="?status=failed" class="stat-pill <?php echo $status === 'failed' ? 'active' : ''; ?>">
                    Failed <span class="count"><?php echo $stats['failed']; ?></span>
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" class="filters-bar">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by name, phone, reference..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="network">
                        <option value="">All Networks</option>
                        <option value="MTN" <?php echo $network === 'MTN' ? 'selected' : ''; ?>>MTN</option>
                        <option value="Telecel" <?php echo $network === 'Telecel' ? 'selected' : ''; ?>>Telecel</option>
                        <option value="AT_Ishare" <?php echo $network === 'AT_Ishare' ? 'selected' : ''; ?>>AT Ishare</option>
                        <option value="AT_Bigtime" <?php echo $network === 'AT_Bigtime' ? 'selected' : ''; ?>>AT Bigtime</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="date">
                        <option value="">All Time</option>
                        <option value="today" <?php echo $date === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $date === 'month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="filter-btn primary">Apply Filters</button>
                    <a href="orders.php" class="filter-btn">Clear</a>
                </div>
            </form>

            <!-- Orders Grid -->
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                <div class="order-card glass-card">
                    <div class="order-header">
                        <span class="order-id">#<?php echo $order['id']; ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="order-status-select" onchange="this.form.submit()">
                                <option value="placed" <?php echo $order['status'] === 'placed' ? 'selected' : ''; ?> style="color: var(--info);">📝 Placed</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?> style="color: var(--warning);">⚙️ Processing</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?> style="color: var(--success);">✅ Delivered</option>
                                <option value="failed" <?php echo $order['status'] === 'failed' ? 'selected' : ''; ?> style="color: var(--danger);">❌ Failed</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>

                    <div class="customer-info">
                        <div class="customer-name"><?php echo htmlspecialchars($order['full_name']); ?></div>
                        <div class="customer-email"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>

                    <div class="order-details">
                        <div class="detail-row">
                            <span>Network:</span>
                            <span><?php echo $order['network']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Bundle:</span>
                            <span><?php echo $order['data_amount']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Phone:</span>
                            <span><?php echo $order['phone_number']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Amount:</span>
                            <span style="color: var(--success);">₵<?php echo number_format($order['price'], 2); ?></span>
                        </div>
                    </div>

                    <div class="order-meta">
                        <span><i class="fas fa-receipt"></i> <?php echo $order['transaction_ref']; ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo timeAgo($order['created_at']); ?></span>
                    </div>

                    <div class="order-actions">
                        <button class="btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this order?')">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="delete_order" class="btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('show');
        }

        function viewOrder(id) {
            alert('View order details for #' + id + ' - This would open a modal or redirect');
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../js/three-background.js"></script>
</body>
</html>
