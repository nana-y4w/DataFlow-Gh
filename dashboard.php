
<?php
require_once '../config.php';
requireAdmin();

$user = getCurrentUser();
$unreadNotifications = getUnreadNotificationCount($user['id']);

// Get dashboard statistics
$stats = [];

// Total users
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$stats['total_users'] = $stmt->fetch()['total'];

// Total agents
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'agent'");
$stats['total_agents'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetch()['total'];

// Pending orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'placed' OR status = 'processing'");
$stats['pending_orders'] = $stmt->fetch()['total'];

// Delivered orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered'");
$stats['delivered_orders'] = $stmt->fetch()['total'];

// Failed orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'failed'");
$stats['failed_orders'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $db->query("SELECT SUM(price) as total FROM orders WHERE status = 'delivered'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?: 0;

// Today's revenue
$stmt = $db->query("SELECT SUM(price) as total FROM orders WHERE status = 'delivered' AND DATE(created_at) = CURDATE()");
$stats['today_revenue'] = $stmt->fetch()['total'] ?: 0;

// Total wallet balance
$stmt = $db->query("SELECT SUM(wallet_balance) as total FROM users");
$stats['total_wallet'] = $stmt->fetch()['total'] ?: 0;

// Recent orders
$stmt = $db->query("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recentOrders = $stmt->fetchAll();

// Recent users
$stmt = $db->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// Network distribution
$stmt = $db->query("
    SELECT network, COUNT(*) as count, SUM(price) as revenue 
    FROM orders 
    WHERE status = 'delivered' 
    GROUP BY network 
    ORDER BY count DESC
");
$networkStats = $stmt->fetchAll();

// Monthly revenue for chart
$stmt = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           COUNT(*) as orders, 
           SUM(price) as revenue 
    FROM orders 
    WHERE status = 'delivered' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlyStats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Admin-specific styles */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--glass-border);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            padding: 2rem 0;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .sidebar-header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .admin-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .sidebar-nav {
            padding: 2rem 1rem;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            padding-left: 0.5rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            color: var(--text);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            margin: 0.2rem 0;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }
        
        /* Top Bar */
        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .notification-badge {
            position: relative;
        }
        
        .badge-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
        }
        
        .profile-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--mtn-yellow), var(--telecel-red), var(--airtel-red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background: rgba(255,255,255,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .stat-change {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--success);
        }
        
        /* Charts */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            padding: 1.5rem;
        }
        
        .chart-card h3 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .chart-container {
            height: 300px;
        }
        
        /* Recent Sections */
        .recent-section {
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-header h2 {
            font-size: 1.3rem;
        }
        
        .view-all {
            color: var(--mtn-yellow);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .recent-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-delivered { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .status-processing { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .status-placed { background: rgba(59, 130, 246, 0.2); color: var(--info); }
        .status-failed { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        
        .action-btn {
            padding: 0.3rem 0.8rem;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 5px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 0.2rem;
        }
        
        .action-btn:hover {
            background: var(--mtn-yellow);
            color: black;
        }
        
        .action-btn.danger:hover {
            background: var(--danger);
            color: white;
        }
        
        /* Network stats */
        .network-list {
            margin-top: 1rem;
        }
        
        .network-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .network-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .network-color.mtn { background: var(--mtn-yellow); }
        .network-color.telecel { background: var(--telecel-red); }
        .network-color.airtel { background: var(--airtel-red); }
        
        .network-info {
            flex: 1;
        }
        
        .network-name {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .network-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .recent-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- 3D Background -->
    <div id="canvas-container"></div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="logo"><?php echo SITE_NAME; ?></div>
                <span class="admin-badge">ADMIN PANEL</span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="analytics.php" class="nav-item">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <a href="users.php" class="nav-item">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="bundles.php" class="nav-item">
                        <i class="fas fa-database"></i> Bundles
                    </a>
                    <a href="orders.php" class="nav-item">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                    <a href="payments.php" class="nav-item">
                        <i class="fas fa-money-bill-wave"></i> Payments
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Communication</div>
                    <a href="broadcast.php" class="nav-item">
                        <i class="fas fa-bullhorn"></i> Broadcast
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="../auth.php?action=logout" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Top Bar -->
            <div class="admin-topbar">
                <div class="page-title">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    Dashboard
                </div>

                <div class="admin-actions">
                    <div class="notification-badge">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="badge-count"><?php echo $unreadNotifications; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-profile">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Administrator</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    </div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> <?php echo $stats['total_agents']; ?> Agents
                    </div>
                </div>

                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                    </div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-change">
                        <i class="fas fa-check"></i> <?php echo $stats['delivered_orders']; ?> Delivered
                    </div>
                </div>

                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    </div>
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-change">
                        <i class="fas fa-hourglass-half"></i> Awaiting Processing
                    </div>
                </div>

                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value">₵<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    </div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-change">
                        <i class="fas fa-calendar"></i> ₵<?php echo number_format($stats['today_revenue'], 2); ?> Today
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-row">
                <div class="chart-card glass-card">
                    <h3>Revenue Overview (Last 6 Months)</h3>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                <div class="chart-card glass-card">
                    <h3>Network Distribution</h3>
                    <div class="chart-container">
                        <canvas id="networkChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders and Users -->
            <div class="recent-grid">
                <!-- Recent Orders -->
                <div class="recent-section">
                    <div class="section-header">
                        <h2>Recent Orders</h2>
                        <a href="orders.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-container glass-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Network</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars(explode(' ', $order['full_name'])[0]); ?></td>
                                    <td><?php echo $order['network']; ?></td>
                                    <td>₵<?php echo number_format($order['price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="recent-section">
                    <div class="section-header">
                        <h2>Recent Users</h2>
                        <a href="users.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-container glass-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $userItem): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($userItem['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($userItem['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $userItem['role'] === 'agent' ? 'status-delivered' : 'status-placed'; ?>">
                                            <?php echo ucfirst($userItem['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d', strtotime($userItem['created_at'])); ?></td>
                                    <td>
                                        <button class="action-btn" onclick="viewUser(<?php echo $userItem['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Network Statistics -->
            <div class="recent-section">
                <h2>Network Performance</h2>
                <div class="glass-card" style="padding: 1.5rem;">
                    <div class="network-list">
                        <?php foreach ($networkStats as $network): ?>
                        <div class="network-item">
                            <span class="network-color <?php 
                                echo strtolower($network['network']) === 'mtn' ? 'mtn' : 
                                    (strtolower($network['network']) === 'telecel' ? 'telecel' : 'airtel'); 
                            ?>"></span>
                            <div class="network-info">
                                <div class="network-name"><?php echo $network['network']; ?></div>
                                <div class="network-stats">
                                    <span><i class="fas fa-shopping-cart"></i> <?php echo $network['count']; ?> orders</span>
                                    <span><i class="fas fa-money-bill"></i> ₵<?php echo number_format($network['revenue'], 2); ?></span>
                                </div>
                            </div>
                            <div class="progress-bar" style="width: 100px;">
                                <div class="progress-fill" style="width: <?php echo ($network['count'] / $stats['delivered_orders']) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('show');
        }

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyStats, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (₵)',
                    data: <?php echo json_encode(array_column($monthlyStats, 'revenue')); ?>,
                    borderColor: '#ffcc00',
                    backgroundColor: 'rgba(255, 204, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Network Chart
        const networkCtx = document.getElementById('networkChart').getContext('2d');
        new Chart(networkCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($networkStats, 'network')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($networkStats, 'count')); ?>,
                    backgroundColor: ['#ffcc00', '#e30613', '#ed1c24', '#006b3f'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Placeholder functions for actions
        function viewOrder(id) {
            window.location.href = 'orders.php?view=' + id;
        }

        function viewUser(id) {
            window.location.href = 'users.php?view=' + id;
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../js/three-background.js"></script>
</body>
</html>
