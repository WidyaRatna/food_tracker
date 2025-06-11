<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Handle add/edit food
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $calories = floatval($_POST['calories'] ?? 0);
    $protein = floatval($_POST['protein'] ?? 0);
    $carbs = floatval($_POST['carbs'] ?? 0);
    $food_id = $_POST['food_id'] ?? '';
    
    if (!empty($name) && $calories > 0) {
        try {
            if (!empty($food_id)) {
                // Update food
                $query = "UPDATE foods SET name = :name, calories = :calories, protein = :protein, carbs = :carbs WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $food_id);
                $success = "Food berhasil diupdate!";
            } else {
                // Add new food
                $query = "INSERT INTO foods (name, calories, protein, carbs) VALUES (:name, :calories, :protein, :carbs)";
                $stmt = $db->prepare($query);
                $success = "Food berhasil ditambahkan!";
            }
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':calories', $calories);
            $stmt->bindParam(':protein', $protein);
            $stmt->bindParam(':carbs', $carbs);
            $stmt->execute();
            
        } catch (Exception $e) {
            $error = "Gagal menyimpan food: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi semua field dengan benar!";
    }
}

// Handle delete food
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $food_id = $_GET['delete'];
    
    try {
        // Check if food is used in food_logs
        $query = "SELECT COUNT(*) as count FROM food_logs WHERE food_id = :food_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':food_id', $food_id);
        $stmt->execute();
        $usage_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($usage_count > 0) {
            $error = "Food tidak dapat dihapus karena masih digunakan dalam food logs!";
        } else {
            $query = "DELETE FROM foods WHERE id = :food_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':food_id', $food_id);
            $stmt->execute();
            $success = "Food berhasil dihapus!";
        }
    } catch (Exception $e) {
        $error = "Gagal menghapus food: " . $e->getMessage();
    }
}

// Get food for editing
$edit_food = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $food_id = $_GET['edit'];
    $query = "SELECT * FROM foods WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $food_id);
    $stmt->execute();
    $edit_food = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all foods with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE name LIKE :search";
    $params[':search'] = "%$search%";
}

// Count total foods
$count_query = "SELECT COUNT(*) as total FROM foods $where_clause";
$stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_foods = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_foods / $limit);

// Get foods
$query = "SELECT * FROM foods $where_clause ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Foods - Admin Panel</title>
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
                <li><a href="foods1.php" class="active">Kelola Foods</a></li>
                <li><a href="food-logs1.php">Food Logs</a></li>
                <li><a href="reports1.php">Reports</a></li>
                <li><a href="admins1.php">Kelola Admin</a></li>
                <li><a href="logout1.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Kelola Foods</h1>
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

                <!-- Add/Edit Food Form -->
                <div class="form-section">
                    <h2><?php echo $edit_food ? 'Edit Food' : 'Tambah Food Baru'; ?></h2>
                    <form method="POST" class="food-form">
                        <?php if ($edit_food): ?>
                            <input type="hidden" name="food_id" value="<?php echo $edit_food['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Nama Food:</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo $edit_food ? htmlspecialchars($edit_food['name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="calories">Kalori (per 100g):</label>
                                <input type="number" id="calories" name="calories" step="0.1" required 
                                       value="<?php echo $edit_food ? $edit_food['calories'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="protein">Protein (g per 100g):</label>
                                <input type="number" id="protein" name="protein" step="0.1" required 
                                       value="<?php echo $edit_food ? $edit_food['protein'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="carbs">Karbohidrat (g per 100g):</label>
                                <input type="number" id="carbs" name="carbs" step="0.1" required 
                                       value="<?php echo $edit_food ? $edit_food['carbs'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_food ? 'Update Food' : 'Tambah Food'; ?>
                            </button>
                            <?php if ($edit_food): ?>
                                <a href="foods1.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Search and Actions -->
                <div class="content-actions">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Cari nama food..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="foods1.php" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Foods Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Food</th>
                                <th>Kalori</th>
                                <th>Protein (g)</th>
                                <th>Karbohidrat (g)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($foods as $food): ?>
                            <tr>
                                <td><?php echo $food['id']; ?></td>
                                <td><?php echo htmlspecialchars($food['name']); ?></td>
                                <td><?php echo $food['calories']; ?></td>
                                <td><?php echo $food['protein']; ?></td>
                                <td><?php echo $food['carbs']; ?></td>
                                <td>
                                    <a href="foods1.php?edit=<?php echo $food['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="foods1.php?delete=<?php echo $food['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Yakin ingin menghapus food ini?')">Hapus</a>
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
                        (Total: <?php echo $total_foods; ?> foods)
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
