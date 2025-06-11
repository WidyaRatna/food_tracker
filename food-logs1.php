<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Handle delete food log
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $log_id = $_GET['delete'];
    
    try {
        $query = "DELETE FROM food_logs WHERE id = :log_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':log_id', $log_id);
        $stmt->execute();
        $success = "Food log berhasil dihapus!";
    } catch (Exception $e) {
        $error = "Gagal menghapus food log: " . $e->getMessage();
    }
}

// Get food logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$where_clause = 'WHERE 1=1';
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (u.username LIKE :search OR f.name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_filter)) {
    $where_clause .= " AND fl.log_date = :date";
    $params[':date'] = $date_filter;
}

// Count total logs
$count_query = "SELECT COUNT(*) as total FROM food_logs fl 
                JOIN users u ON fl.user_id = u.id 
                JOIN foods f ON fl.food_id = f.id 
                $where_clause";
$stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_logs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_logs / $limit);

// Get food logs
$query = "SELECT fl.*, u.username, f.name as food_name, f.calories, f.protein, f.carbs,
          ROUND((fl.grams / 100) * f.calories, 2) as total_calories,
          ROUND((fl.grams / 100) * f.protein, 2) as total_protein,
          ROUND((fl.grams / 100) * f.carbs, 2) as total_carbs
          FROM food_logs fl 
          JOIN users u ON fl.user_id = u.id 
          JOIN foods f ON fl.food_id = f.id 
          $where_clause 
          ORDER BY fl.log_date DESC, fl.id DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$food_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Logs - Admin Panel</title>
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
                <li><a href="users1.php">Kelola Users</a></li>
                <li><a href="foods1.php">Kelola Foods</a></li>
                <li><a href="food-logs1.php" class="active">Food Logs</a></li>
                <li><a href="reports1.php">Reports</a></li>
                <li><a href="admins1.php">Kelola Admin</a></li>
                <li><a href="logout1.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Food Logs</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_data['admin_name']); ?></span>
                </div>
            </header>

            <div class="content-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="content-actions">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Cari username atau food..." value="<?php echo htmlspecialchars($search); ?>">
                        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if (!empty($search) || !empty($date_filter)): ?>
                            <a href="food-logs1.php" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Food Logs Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Food</th>
                                <th>Gram</th>
                                <th>Kalori</th>
                                <th>Protein (g)</th>
                                <th>Karbo (g)</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($food_logs)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem;">
                                    Tidak ada data food logs
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($food_logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['food_name']); ?></td>
                                    <td><?php echo $log['grams']; ?>g</td>
                                    <td><?php echo $log['total_calories']; ?></td>
                                    <td><?php echo $log['total_protein']; ?></td>
                                    <td><?php echo $log['total_carbs']; ?></td>
                                    <td><?php echo $log['log_date']; ?></td>
                                    <td>
                                        <a href="food-logs1.php?delete=<?php echo $log['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus log ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" class="btn btn-secondary">« Sebelumnya</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> 
                        (Total: <?php echo $total_logs; ?> logs)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>" class="btn btn-secondary">Selanjutnya »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
