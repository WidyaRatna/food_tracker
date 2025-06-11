<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total foods
$query = "SELECT COUNT(*) as total FROM foods";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_foods'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total food logs today
$query = "SELECT COUNT(*) as total FROM food_logs WHERE log_date = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_logs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total admins
$query = "SELECT COUNT(*) as total FROM admins WHERE admin_status = 'active' AND admin_deleted_at IS NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_admins'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent users
$query = "SELECT id, username, email, 'N/A' as join_date FROM users ORDER BY id DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Nutrition Tracker</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard1.php" class="active">Dashboard</a></li>
                <li><a href="users1.php">Kelola Users</a></li>
                <li><a href="foods1.php">Kelola Foods</a></li>
                <li><a href="food-logs1.php">Food Logs</a></li>
                <li><a href="reports1.php">Reports</a></li>
                <li><a href="admins1.php">Kelola Admin</a></li>
                <li><a href="logout1.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Dashboard</h1>
                <div class="admin-info">
                    <span>Selamat datang, <?php echo htmlspecialchars($admin_data['admin_name']); ?></span>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🍎</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_foods']; ?></h3>
                            <p>Total Foods</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['today_logs']; ?></h3>
                            <p>Logs Hari Ini</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">👨‍💼</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_admins']; ?></h3>
                            <p>Total Admins</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="dashboard-section">
                    <h2>Users Terbaru</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Tanggal Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['join_date'] ?? 'N/A'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
