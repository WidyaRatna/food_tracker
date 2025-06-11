<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkAdminLogin();

$database = new Database();
$db = $database->getConnection();

// Handle add/edit admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['admin_name'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $phone = trim($_POST['admin_phone'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $status = $_POST['admin_status'] ?? 'active';
    $admin_id = $_POST['admin_id'] ?? '';
    
    if (!empty($name) && !empty($email)) {
        try {
            if (!empty($admin_id)) {
                // Update admin
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE admins SET admin_name = :name, admin_email = :email, admin_phone = :phone, admin_password = :password, admin_status = :status WHERE admin_id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $query = "UPDATE admins SET admin_name = :name, admin_email = :email, admin_phone = :phone, admin_status = :status WHERE admin_id = :id";
                    $stmt = $db->prepare($query);
                }
                $stmt->bindParam(':id', $admin_id);
                $success = "Admin berhasil diupdate!";
            } else {
                // Add new admin
                if (empty($password)) {
                    $error = "Password wajib diisi untuk admin baru!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO admins (admin_name, admin_email, admin_phone, admin_password, admin_status) VALUES (:name, :email, :phone, :password, :status)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                    $success = "Admin berhasil ditambahkan!";
                }
            }
            
            if (!isset($error)) {
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
            }
            
        } catch (Exception $e) {
            $error = "Gagal menyimpan admin: " . $e->getMessage();
        }
    } else {
        $error = "Nama dan email wajib diisi!";
    }
}

// Handle delete admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $admin_id = $_GET['delete'];
    $current_admin = getAdminData();
    
    if ($admin_id == $current_admin['admin_id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri!";
    } else {
        try {
            $query = "UPDATE admins SET admin_deleted_at = NOW() WHERE admin_id = :admin_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
            $success = "Admin berhasil dihapus!";
        } catch (Exception $e) {
            $error = "Gagal menghapus admin: " . $e->getMessage();
        }
    }
}

// Get admin for editing
$edit_admin = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $admin_id = $_GET['edit'];
    $query = "SELECT * FROM admins WHERE admin_id = :id AND admin_deleted_at IS NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $admin_id);
    $stmt->execute();
    $edit_admin = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all admins
$query = "SELECT * FROM admins WHERE admin_deleted_at IS NULL ORDER BY admin_created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_data = getAdminData();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Admin Panel</title>
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
                <li><a href="food-logs1.php">Food Logs</a></li>
                <li><a href="reports1.php">Reports</a></li>
                <li><a href="admins1.php" class="active">Kelola Admin</a></li>
                <li><a href="logout1.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Kelola Admin</h1>
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

                <!-- Add/Edit Admin Form -->
                <div class="form-section">
                    <h2><?php echo $edit_admin ? 'Edit Admin' : 'Tambah Admin Baru'; ?></h2>
                    <form method="POST" class="admin-form">
                        <?php if ($edit_admin): ?>
                            <input type="hidden" name="admin_id" value="<?php echo $edit_admin['admin_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_name">Nama Admin:</label>
                                <input type="text" id="admin_name" name="admin_name" required 
                                       value="<?php echo $edit_admin ? htmlspecialchars($edit_admin['admin_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_email">Email:</label>
                                <input type="email" id="admin_email" name="admin_email" required 
                                       value="<?php echo $edit_admin ? htmlspecialchars($edit_admin['admin_email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_phone">No. Telepon:</label>
                                <input type="text" id="admin_phone" name="admin_phone" 
                                       value="<?php echo $edit_admin ? htmlspecialchars($edit_admin['admin_phone']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_status">Status:</label>
                                <select id="admin_status" name="admin_status" required>
                                    <option value="active" <?php echo ($edit_admin && $edit_admin['admin_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($edit_admin && $edit_admin['admin_status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Password <?php echo $edit_admin ? '(kosongkan jika tidak ingin mengubah)' : ''; ?>:</label>
                            <input type="password" id="admin_password" name="admin_password" 
                                   <?php echo !$edit_admin ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_admin ? 'Update Admin' : 'Tambah Admin'; ?>
                            </button>
                            <?php if ($edit_admin): ?>
                                <a href="admins1.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Admins Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Status</th>
                                <th>Main Admin</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['admin_id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['admin_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['admin_email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['admin_phone']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $admin['admin_status']; ?>">
                                        <?php echo ucfirst($admin['admin_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $admin['admin_main'] ? 'Ya' : 'Tidak'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($admin['admin_created_at'])); ?></td>
                                <td>
                                    <a href="admins1.php?edit=<?php echo $admin['admin_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <?php if ($admin['admin_id'] != $admin_data['admin_id']): ?>
                                        <a href="admins1.php?delete=<?php echo $admin['admin_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus admin ini?')">Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>

    <style>
        .admin-form {
            max-width: 600px;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</body>
</html>
