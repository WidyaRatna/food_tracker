<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Daily statistics
$query = "SELECT 
    DATE(fl.log_date) as date,
    COUNT(DISTINCT fl.user_id) as active_users,
    COUNT(fl.id) as total_logs,
    ROUND(AVG((fl.grams / 100) * f.calories), 2) as avg_calories
    FROM food_logs fl 
    JOIN foods f ON fl.food_id = f.id 
    WHERE fl.log_date BETWEEN :start_date AND :end_date
    GROUP BY DATE(fl.log_date)
    ORDER BY date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top foods
$query = "SELECT 
    f.name,
    COUNT(fl.id) as log_count,
    ROUND(SUM(fl.grams), 2) as total_grams
    FROM food_logs fl 
    JOIN foods f ON fl.food_id = f.id 
    WHERE fl.log_date BETWEEN :start_date AND :end_date
    GROUP BY f.id, f.name
    ORDER BY log_count DESC
    LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$top_foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// User activity
$query = "SELECT 
    u.username,
    COUNT(fl.id) as log_count,
    ROUND(AVG((fl.grams / 100) * f.calories), 2) as avg_daily_calories
    FROM users u
    LEFT JOIN food_logs fl ON u.id = fl.user_id AND fl.log_date BETWEEN :start_date AND :end_date
    LEFT JOIN foods f ON fl.food_id = f.id
    GROUP BY u.id, u.username
    HAVING log_count > 0
    ORDER BY log_count DESC
    LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$user_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard1.php">Dashboard</a></li>
                <li><a href="users1.php">Kelola Users</a></li>
                <li><a href="foods1.php">Kelola Foods</a></li>
                <li><a href="food-logs1.php">Food Logs</a></li>
                <li><a href="reports1.php" class="active">Reports</a></li>
                <li><a href="admins1.php">Kelola Admin</a></li>
                <li><a href="logout1.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Reports & Analytics</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_data['admin_name']); ?></span>
                </div>
            </header>

            <div class="content-body">
                <!-- Date Range Filter -->
                <div class="content-actions">
                    <form method="GET" class="search-form">
                        <label>Dari:</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                        <label>Sampai:</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>

                <!-- Charts Section -->
                <?php if (!empty($daily_stats) || !empty($top_foods)): ?>
                <div class="reports-grid">
                    <?php if (!empty($daily_stats)): ?>
                    <div class="chart-container">
                        <h3>Aktivitas Harian</h3>
                        <canvas id="dailyChart"></canvas>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($top_foods)): ?>
                    <div class="chart-container">
                        <h3>Top 10 Foods</h3>
                        <canvas id="foodsChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Tables Section -->
                <div class="reports-tables">
                    <!-- Daily Statistics -->
                    <div class="report-section">
                        <h3>Statistik Harian</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>User Aktif</th>
                                        <th>Total Logs</th>
                                        <th>Rata-rata Kalori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($daily_stats)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 2rem;">
                                            Tidak ada data untuk periode ini
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($daily_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo $stat['date']; ?></td>
                                            <td><?php echo $stat['active_users']; ?></td>
                                            <td><?php echo $stat['total_logs']; ?></td>
                                            <td><?php echo $stat['avg_calories'] ?? 0; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Foods -->
                    <div class="report-section">
                        <h3>Top 10 Foods</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Food</th>
                                        <th>Jumlah Log</th>
                                        <th>Total Gram</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_foods)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 2rem;">
                                            Tidak ada data untuk periode ini
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_foods as $food): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($food['name']); ?></td>
                                            <td><?php echo $food['log_count']; ?></td>
                                            <td><?php echo $food['total_grams']; ?>g</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- User Activity -->
                    <div class="report-section">
                        <h3>Top 10 User Aktif</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Jumlah Log</th>
                                        <th>Rata-rata Kalori Harian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($user_activity)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 2rem;">
                                            Tidak ada data untuk periode ini
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($user_activity as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo $user['log_count']; ?></td>
                                            <td><?php echo $user['avg_daily_calories'] ?? 0; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        // Only create charts if data exists
        <?php if (!empty($daily_stats)): ?>
        // Daily Activity Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = <?php echo json_encode(array_reverse($daily_stats)); ?>;
        
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => item.date),
                datasets: [{
                    label: 'User Aktif',
                    data: dailyData.map(item => item.active_users),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Total Logs',
                    data: dailyData.map(item => item.total_logs),
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($top_foods)): ?>
        // Top Foods Chart
        const foodsCtx = document.getElementById('foodsChart').getContext('2d');
        const foodsData = <?php echo json_encode($top_foods); ?>;
        
        new Chart(foodsCtx, {
            type: 'bar',
            data: {
                labels: foodsData.map(item => item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name),
                datasets: [{
                    label: 'Jumlah Log',
                    data: foodsData.map(item => item.log_count),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .chart-container h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .reports-tables {
            display: grid;
            gap: 2rem;
        }

        .report-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .report-section h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
