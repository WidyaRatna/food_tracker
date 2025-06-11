<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    try {
        $db->beginTransaction();
        
        // Delete related records first
        $query = "DELETE FROM food_logs WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $query = "DELETE FROM daily_reports WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Delete user
        $query = "DELETE FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $db->commit();
        $success = "User berhasil dihapus!";
    } catch (Exception $e) {
        $db->rollback();
        $error = "Gagal menghapus user: " . $e->getMessage();
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE username LIKE :search OR email LIKE :search";
    $params[':search'] = "%$search%";
}

// Count total users
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $limit);

// Get users
$query = "SELECT * FROM users $where_clause ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Admin Panel</title>
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
                <h1>Kelola Users</h1>
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

                <!-- Search and Actions -->
                <div class="content-actions">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Cari username atau email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="users1.php" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Tinggi (cm)</th>
                                <th>Berat (kg)</th>
                                <th>Gender</th>
                                <th>Umur</th>
                                <th>Goal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['height_cm']; ?></td>
                                <td><?php echo $user['weight_cm']; ?></td>
                                <td><?php echo ucfirst($user['gender']); ?></td>
                                <td><?php echo $user['age']; ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $user['goal'])); ?></td>
                                <td>
                                    <a href="user-detail1.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                                    <a href="users1.php?delete=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">« Sebelumnya</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> 
                        (Total: <?php echo $total_users; ?> users)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">Selanjutnya »</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
