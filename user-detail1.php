<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: users1.php');
    exit();
}

// Get user details
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users1.php');
    exit();
}

// Get user's food logs
$query = "SELECT fl.*, f.name as food_name, f.calories, f.protein, f.carbs,
          ROUND((fl.grams / 100) * f.calories, 2) as total_calories,
          ROUND((fl.grams / 100) * f.protein, 2) as total_protein,
          ROUND((fl.grams / 100) * f.carbs, 2) as total_carbs
          FROM food_logs fl 
          JOIN foods f ON fl.food_id = f.id 
          WHERE fl.user_id = :user_id 
          ORDER BY fl.log_date DESC, fl.id DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$food_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$query = "SELECT 
          COUNT(fl.id) as total_logs,
          COUNT(DISTINCT fl.log_date) as active_days,
          ROUND(AVG((fl.grams / 100) * f.calories), 2) as avg_calories_per_log
          FROM food_logs fl 
          JOIN foods f ON fl.food_id = f.id 
          WHERE fl.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User - Admin Panel</title>
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
                <li><a href="dashboard1.php">Dashboard</a></li>
                <li><a href="users1.php" class="active">Kelola Users</a></li>
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
                <h1>Detail User: <?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_data['admin_name']); ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="user-detail-grid">
                    <!-- User Information -->
                    <div class="user-info-card">
                        <h3>Informasi User</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Username:</label>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Tinggi:</label>
                                <span><?php echo $user['height_cm']; ?> cm</span>
                            </div>
                            <div class="info-item">
                                <label>Berat:</label>
                                <span><?php echo $user['weight_cm']; ?> kg</span>
                            </div>
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?php echo ucfirst($user['gender']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Umur:</label>
                                <span><?php echo $user['age']; ?> tahun</span>
                            </div>
                            <div class="info-item">
                                <label>Activity Level:</label>
                                <span><?php echo ucfirst(str_replace('_', ' ', $user['activity_level'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Goal:</label>
                                <span><?php echo ucfirst(str_replace('_', ' ', $user['goal'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- User Statistics -->
                    <div class="user-stats-card">
                        <h3>Statistik</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $stats['total_logs'] ?? 0; ?></div>
                                <div class="stat-label">Total Logs</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $stats['active_days'] ?? 0; ?></div>
                                <div class="stat-label">Hari Aktif</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $stats['avg_calories_per_log'] ?? 0; ?></div>
                                <div class="stat-label">Rata-rata Kalori</div>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Limits -->
                    <div class="user-limits-card">
                        <h3>Target Harian</h3>
                        <div class="limits-grid">
                            <div class="limit-item">
                                <label>Kalori:</label>
                                <span><?php echo $user['daily_calorie_limit']; ?></span>
                            </div>
                            <div class="limit-item">
                                <label>Protein:</label>
                                <span><?php echo $user['daily_protein_limit']; ?>g</span>
                            </div>
                            <div class="limit-item">
                                <label>Karbohidrat:</label>
                                <span><?php echo $user['daily_carb_limit']; ?>g</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Food Logs -->
                <div class="recent-logs-section">
                    <h3>Food Logs Terbaru (20 terakhir)</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Food</th>
                                    <th>Gram</th>
                                    <th>Kalori</th>
                                    <th>Protein</th>
                                    <th>Karbo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($food_logs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem;">
                                        User belum memiliki food logs
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($food_logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['log_date']; ?></td>
                                        <td><?php echo htmlspecialchars($log['food_name']); ?></td>
                                        <td><?php echo $log['grams']; ?>g</td>
                                        <td><?php echo $log['total_calories']; ?></td>
                                        <td><?php echo $log['total_protein']; ?>g</td>
                                        <td><?php echo $log['total_carbs']; ?>g</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Actions -->
                <div class="user-actions">
                    <a href="users1.php" class="btn btn-secondary">Kembali ke Daftar Users</a>
                    <a href="food-logs1.php?search=<?php echo urlencode($user['username']); ?>" class="btn btn-primary">Lihat Semua Logs</a>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>

    <style>
        .user-detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .user-info-card, .user-stats-card, .user-limits-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-info-card h3, .user-stats-card h3, .user-limits-card h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-item label {
            font-weight: 600;
            color: #666;
            font-size: 0.875rem;
        }

        .info-item span {
            color: #333;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #666;
        }

        .limits-grid {
            display: grid;
            gap: 1rem;
        }

        .limit-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .limit-item label {
            font-weight: 600;
            color: #666;
        }

        .limit-item span {
            font-weight: 600;
            color: #333;
        }

        .recent-logs-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .recent-logs-section h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .user-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .user-detail-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .user-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
