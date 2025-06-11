<?php
// food_tracker.php - Aplikasi untuk melacak asupan makanan dan nutrisi harian

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Set zona waktu ke WIB (Waktu Indonesia Barat)
date_default_timezone_set('Asia/Jakarta');
$conn = new mysqli("localhost", "root", "", "web_login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+07:00'");

// Ambil data pengguna dari sesi
$username = $_SESSION['username'];
$user_query = "SELECT id, gender, age, height_cm, weight_cm, activity_level, goal FROM users WHERE username = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) {
    die("User not found. Please log in again. <a href='login.php'>Back to Login</a>");
}

$user_id = $user['id'];
$gender = $user['gender'];
$age = floatval($user['age']);
$height_cm = floatval($user['height_cm']);
$weight_cm = floatval($user['weight_cm']);
$activity_level = $user['activity_level'];
$goal = $user['goal'];
$stmt->close();
error_log("User ID: $user_id, Username: $username");

// Hitung BMR menggunakan Mifflin-St Jeor Equation
$bmr = ($gender === 'male') 
    ? (10 * $weight_cm + 6.25 * $height_cm - 5 * $age + 5)
    : (10 * $weight_cm + 6.25 * $height_cm - 5 * $age - 161);

// Hitung TDEE berdasarkan tingkat aktivitas
$activity_multipliers = [
    'sedentary' => 1.2,
    'lightly_active' => 1.375,
    'moderately_active' => 1.55,
    'very_active' => 1.725,
    'extremely_active' => 1.9
];
$tdee = $bmr * ($activity_multipliers[$activity_level] ?? 1.2);

// Sesuaikan TDEE berdasarkan tujuan
if ($goal === 'weight_loss') {
    $tdee *= 0.8; // Defisit kalori 20%
} elseif ($goal === 'bulking') {
    $tdee *= 1.1; // Surplus kalori 10%
}

// Tentukan batas nutrisi harian
$daily_calorie_limit = $tdee;
$daily_carb_limit = ($tdee * 0.4) / 4; // 40% dari TDEE, 4 kkal per gram karbohidrat

// Hitung batas protein berdasarkan tujuan dan berat badan
$protein_per_kg = [
    'weight_loss' => 1.2,
    'maintenance' => 0.8,
    'bulking' => 1.5
];
$daily_protein_limit = $weight_cm * ($protein_per_kg[$goal] ?? 0.8);

// Tangani penghapusan item makanan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $log_id = intval($_GET['id']);
    error_log("Mencoba menghapus log ID: $log_id untuk username: $username");
    
    $user_check_query = "SELECT u.id FROM users u JOIN food_logs fl ON u.id = fl.user_id WHERE u.username = ? AND fl.id = ?";
    $stmt = $conn->prepare($user_check_query);
    $stmt->bind_param("si", $username, $log_id);
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("Jumlah baris verifikasi: " . $result->num_rows);

    if ($result->num_rows > 0) {
        $delete_query = "DELETE FROM food_logs WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $log_id);
        $success = $stmt->execute();
        error_log("Penghapusan berhasil: " . ($success ? "Ya" : "Tidak"));

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success, 'message' => $success ? 'Item deleted successfully' : 'Failed to delete item']);
            $stmt->close();
            $conn->close();
            exit();
        }

        header("Location: food_tracker.php?deleted=true");
        exit();
    } else {
        error_log("Verifikasi gagal untuk ID: $log_id");
        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Item not found or permission denied']);
            $stmt->close();
            $conn->close();
            exit();
        }

        header("Location: food_tracker.php?error=permission");
        exit();
    }
    $stmt->close();
}

// Tangani penghapusan laporan
if (isset($_GET['delete_report'])) {
    $report_id = intval($_GET['delete_report']);
    $check_query = "SELECT id FROM daily_reports WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $delete_query = "DELETE FROM daily_reports WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $stmt->close();

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dihapus']);
            $conn->close();
            exit();
        }

        header("Location: food_tracker.php?report_deleted=true");
        exit();
    } else {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan atau izin ditolak']);
            $conn->close();
            exit();
        }

        header("Location: food_tracker.php?error=permission");
        exit();
    }
}

// Tangani penambahan item makanan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_food'])) {
    $food_name = trim($_POST['food_name']);
    $grams = floatval($_POST['gram']);
    $log_date = date('Y-m-d');

    $check_food_query = "SELECT id, calories, protein, carbs FROM foods WHERE name = ?";
    $stmt = $conn->prepare($check_food_query);
    $stmt->bind_param("s", $food_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $food = $result->fetch_assoc();
        $food_id = $food['id'];
    } else {
        if (isset($_POST['calories']) && isset($_POST['protein']) && isset($_POST['carbs'])) {
            $calories = floatval($_POST['calories']);
            $protein = floatval($_POST['protein']);
            $carbs = floatval($_POST['carbs']);

            $insert_food_query = "INSERT INTO foods (name, calories, protein, carbs) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_food_query);
            $stmt->bind_param("sddd", $food_name, $calories, $protein, $carbs);
            $stmt->execute();
            $food_id = $conn->insert_id;
        } else {
            $error = "Makanan tidak ditemukan di database. Silakan masukkan data nutrisi.";
            $show_error = true;
        }
    }

    if (!isset($show_error)) {
        error_log("Menyimpan log: user_id=$user_id, food_id=$food_id, grams=$grams, log_date=$log_date");
        $insert_log_query = "INSERT INTO food_logs (user_id, food_id, grams, log_date) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_log_query);
        $stmt->bind_param("iids", $user_id, $food_id, $grams, $log_date);
        $success = $stmt->execute();
        if ($success) {
            error_log("Log berhasil disimpan untuk user_id=$user_id pada tanggal $log_date");
            $success = true;
        } else {
            $error = "Error menyimpan log: " . $conn->error;
            error_log("Gagal menyimpan log: " . $conn->error);
        }
    }
    $stmt->close();
}

// Tangani penyimpanan laporan harian
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_report'])) {
    $today = date('Y-m-d');
    $logs_query = "SELECT fl.id, fl.food_id, fl.grams, f.name, f.calories, f.protein, f.carbs 
                  FROM food_logs fl 
                  JOIN foods f ON fl.food_id = f.id 
                  WHERE fl.user_id = ? AND fl.log_date = ?";
    $stmt = $conn->prepare($logs_query);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $logs_result = $stmt->get_result();

    $total_calories = 0;
    $total_protein = 0;
    $total_carbs = 0;

    if ($logs_result->num_rows > 0) {
        while ($row = $logs_result->fetch_assoc()) {
            $calorie_ratio = floatval($row['grams']) / 100;
            $total_calories += floatval($row['calories']) * $calorie_ratio;
            $total_protein += floatval($row['protein']) * $calorie_ratio;
            $total_carbs += floatval($row['carbs']) * $calorie_ratio;
        }
    }
    $stmt->close();

    $check_report_query = "SELECT id FROM daily_reports WHERE user_id = ? AND report_date = ?";
    $stmt = $conn->prepare($check_report_query);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $check_result = $stmt->get_result();
    $report_exists = $check_result->num_rows > 0;
    $stmt->close();

    if ($report_exists) {
        $update_report_query = "UPDATE daily_reports SET total_calories = ?, total_protein = ?, total_carbs = ?, bmr = ?, tdee = ?, calorie_limit = ?, protein_limit = ?, carb_limit = ? WHERE user_id = ? AND report_date = ?";
        $stmt = $conn->prepare($update_report_query);
        $stmt->bind_param("dddddddiss", $total_calories, $total_protein, $total_carbs, $bmr, $tdee, $daily_calorie_limit, $daily_protein_limit, $daily_carb_limit, $user_id, $today);
        $stmt->execute();
        $stmt->close();
        header("Location: food_tracker.php?report_updated=true");
        exit();
    } else {
        $insert_report_query = "INSERT INTO daily_reports (user_id, report_date, total_calories, total_protein, total_carbs, bmr, tdee, calorie_limit, protein_limit, carb_limit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_report_query);
        $stmt->bind_param("isdddddddd", $user_id, $today, $total_calories, $total_protein, $total_carbs, $bmr, $tdee, $daily_calorie_limit, $daily_protein_limit, $daily_carb_limit);
        $stmt->execute();
        $stmt->close();
        header("Location: food_tracker.php?report_saved=true");
        exit();
    }
}

// Tangani AJAX request untuk data makanan
if (isset($_GET['action']) && $_GET['action'] == 'get_food_data' && isset($_GET['food_name'])) {
    $food_name = $_GET['food_name'];
    $query = "SELECT id, name, calories, protein, carbs FROM foods WHERE name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $food_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $food = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode($food ?: ['error' => 'Food not found']);
    $stmt->close();
    $conn->close();
    exit();
}

// Tangani AJAX request untuk saran makanan
if (isset($_GET['action']) && $_GET['action'] == 'suggest' && isset($_GET['term'])) {
    $term = '%' . $_GET['term'] . '%';
    $query = "SELECT name FROM foods WHERE name LIKE ? LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    $stmt->close();
    $conn->close();
    exit();
}

// Ambil log makanan pengguna untuk hari ini
$today = date('Y-m-d');
error_log("Mengambil log untuk user_id=$user_id, tanggal=$today");
$logs_query = "SELECT fl.id, fl.food_id, fl.grams, f.name, f.calories, f.protein, f.carbs 
              FROM food_logs fl 
              JOIN foods f ON fl.food_id = f.id 
              WHERE fl.user_id = ? AND fl.log_date = ?
              ORDER BY fl.id DESC";
$stmt = $conn->prepare($logs_query);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$logs_result = $stmt->get_result();
error_log("Jumlah log ditemukan: " . $logs_result->num_rows);

$logs = [];
$total_calories = 0;
$total_protein = 0;
$total_carbs = 0;

if ($logs_result->num_rows > 0) {
    while ($row = $logs_result->fetch_assoc()) {
        $calorie_ratio = floatval($row['grams']) / 100;
        $row['calculated_calories'] = round(floatval($row['calories']) * $calorie_ratio, 1);
        $row['calculated_protein'] = round(floatval($row['protein']) * $calorie_ratio, 1);
        $row['calculated_carbs'] = round(floatval($row['carbs']) * $calorie_ratio, 1);
        
        $total_calories += $row['calculated_calories'];
        $total_protein += $row['calculated_protein'];
        $total_carbs += $row['calculated_carbs'];
        
        $logs[] = $row;
    }
}
$stmt->close();

// Ambil semua makanan untuk autocomplete
$foods_query = "SELECT id, name, calories, protein, carbs FROM foods ORDER BY name";
$foods_result = $conn->query($foods_query);
$foods = [];

if ($foods_result && $foods_result->num_rows > 0) {
    while ($row = $foods_result->fetch_assoc()) {
        $foods[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="food_tracker.css">
    <style>
        .menu-bar {
            display: flex;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .menu-item {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s;
            display: block;
        }
        .menu-item.active {
            background-color: #4CAF50;
            color: white;
        }
        .menu-item:hover:not(.active) {
            background-color: #e9e9e9;
        }
        .info-message {
            background-color: #e3f2fd;
            color: #0066cc;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <div class="menu-bar">
            <a href="food_tracker.php" class="menu-item active">Food Tracker</a>
            <a href="report_history.php" class="menu-item">Riwayat Laporan</a>
            <a href="dash.php" class="menu-item">Dashboard</a>
        </div>

        <div id="tracker-section" class="content">
            <div class="header">
                <h1>FOOD TRACKER</h1>
                <p>Catat asupan makanan dan nutrisi harian Anda</p>
            </div>

            <?php if (isset($success) || (isset($_GET['deleted']) && $_GET['deleted'] == 'true')): ?>
            <div class="success-message" id="success-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="currentColor"/>
                </svg>
                <?php echo (isset($_GET['deleted']) && $_GET['deleted'] == 'true') ? 'Makanan berhasil dihapus!' : 'Makanan berhasil ditambahkan!'; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error) || (isset($_GET['error']) && $_GET['error'] == 'permission')): ?>
            <div class="error-message" id="error-message">
                <?php echo isset($error) ? htmlspecialchars($error) : "Tidak bisa menghapus item. Anda tidak memiliki izin atau item tidak ditemukan."; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['report_saved']) && $_GET['report_saved'] == 'true'): ?>
            <div class="success-message" id="report-saved-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="currentColor"/>
                </svg>
                Laporan berhasil disimpan!
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['report_updated']) && $_GET['report_updated'] == 'true'): ?>
            <div class="success-message" id="report-updated-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="currentColor"/>
                </svg>
                Laporan berhasil diperbarui!
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['report_deleted']) && $_GET['report_deleted'] == 'true'): ?>
            <div class="success-message" id="report-deleted-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="currentColor"/>
                </svg>
                Laporan berhasil dihapus!
            </div>
            <?php endif; ?>

            <div class="nutrition-preview" id="nutrition-preview">
                <div class="preview-title">Informasi Nutrisi - <span id="preview-food-name">Pilih Makanan</span></div>
                <div class="preview-info">
                    <span>Kalori per 100g:</span>
                    <span class="preview-value" id="preview-calories">0</span>
                </div>
                <div class="preview-info">
                    <span>Protein per 100g:</span>
                    <span class="preview-value" id="preview-protein">0 g</span>
                </div>
                <div class="preview-info">
                    <span>Karbohidrat per 100g:</span>
                    <span class="preview-value" id="preview-carbs">0 g</span>
                </div>
                <div class="preview-info" style="font-weight: 600; margin-top: 10px; color: #2e7d32;">
                    <span>Total kalori:</span>
                    <span class="preview-value" id="preview-total-calories">0</span>
                </div>
            </div>

            <form id="food-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="food_name">Nama Makanan</label>
                        <input type="text" class="form-control" id="food_name" name="food_name" placeholder="Masukkan nama makanan" required>
                    </div>
                    <div class="form-group">
                        <label for="gram">Jumlah (gram)</label>
                        <input type="number" class="form-control" id="gram" name="gram" placeholder="Jumlah dalam gram" value="100" min="1" required>
                    </div>
                </div>
                <input type="hidden" id="calories" name="calories">
                <input type="hidden" id="protein" name="protein">
                <input type="hidden" id="carbs" name="carbs">
                <button type="submit" class="btn" id="add-food-btn" name="add_food">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah Makanan
                </button>
            </form>

            <div class="food-list" id="food-list">
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                    <div class="food-item" id="food-item-<?php echo $log['id']; ?>" 
                         data-calories="<?php echo $log['calculated_calories']; ?>" 
                         data-protein="<?php echo $log['calculated_protein']; ?>" 
                         data-carbs="<?php echo $log['calculated_carbs']; ?>">
                        <div>
                            <div class="food-name"><?php echo htmlspecialchars($log['name']); ?></div>
                            <div class="food-gram"><?php echo $log['grams']; ?> gram</div>
                        </div>
                        <div class="food-details">
                            <div class="calories"><?php echo $log['calculated_calories']; ?> kkal</div>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $log['id']; ?>)" class="delete-btn" title="Hapus">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" id="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a10 10 0 00-10 10c0 5.5 4.5 10 10 10s10-4.5 10-10a10 10 0 00-10-10z"></path>
                            <path d="M12 6v12m-6-6h12"></path>
                        </svg>
                        <p>Data makanan belum tersedia.</p>
                        <p>Mulai tambahkan makanan untuk melacak nutrisi hari ini.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nutrition-summary">
                <div class="nutrition-box">
                    <div class="nutrition-value" id="total-calories"><?php echo round($total_calories); ?></div>
                    <div class="nutrition-label">Kalori</div>
                </div>
                <div class="nutrition-box">
                    <div class="nutrition-value" id="total-protein"><?php echo round($total_protein, 1); ?>g</div>
                    <div class="nutrition-label">Protein</div>
                </div>
                <div class="nutrition-box">
                    <div class="nutrition-value" id="total-carbs"><?php echo round($total_carbs, 1); ?>g</div>
                    <div class="nutrition-label">Karbo</div>
                </div>
            </div>
        </div>

        <div id="report-section" class="content">
            <div class="header">
                <h1>LAPORAN NUTRISI</h1>
                <p>Ringkasan nutrisi harian Anda</p>
            </div>

            <div class="report-section">
                <div class="report-date" id="today-date"><?php echo date("d F Y"); ?></div>
                <div class="report-data">
                    <span class="report-label">BMR (Kalori dasar tubuh saat istirahat)</span>
                    <span class="report-value"><?php echo round($bmr); ?> kkal</span>
                </div>
                <div class="report-data">
                    <span class="report-label">TDEE (Total kalori harian yang dibutuhkan tubuh)</span>
                    <span class="report-value"><?php echo round($tdee); ?> kkal</span>
                </div>
                <form method="post">
                    <input type="hidden" name="save_report" value="1">
                    <button class="btn" style="margin-bottom: 20px;">Simpan Laporan Hari Ini</button>
                </form>

                <div class="report-data">
                    <span class="report-label">Total Kalori</span>
                    <span class="report-value" id="report-calories">
                        <?php echo round($total_calories); ?> / 
                        <?php echo round($daily_calorie_limit); ?> kkal
                    </span>
                </div>
                <div class="report-progress">
                    <?php $calorie_percent = ($daily_calorie_limit > 0) ? min(100, round(($total_calories / $daily_calorie_limit) * 100)) : 0; ?>
                    <div class="progress-bar" style="width: <?php echo $calorie_percent; ?>%;"></div>
                </div>

                <div class="report-data">
                    <span class="report-label">Total Protein</span>
                    <span class="report-value" id="report-protein">
                        <?php echo round($total_protein, 1); ?> / 
                        <?php echo round($daily_protein_limit, 1); ?> g
                    </span>
                </div>
                <div class="report-progress">
                    <?php $protein_percent = ($daily_protein_limit > 0) ? min(100, round(($total_protein / $daily_protein_limit) * 100)) : 0; ?>
                    <div class="progress-bar" style="width: <?php echo $protein_percent; ?>%;"></div>
                </div>

                <div class="report-data">
                    <span class="report-label">Total Karbohidrat</span>
                    <span class="report-value" id="report-carbs">
                        <?php echo round($total_carbs, 1); ?> / 
                        <?php echo round($daily_carb_limit, 1); ?> g
                    </span>
                </div>
                <div class="report-progress">
                    <?php $carbs_percent = ($daily_carb_limit > 0) ? min(100, round(($total_carbs / $daily_carb_limit) * 100)) : 0; ?>
                    <div class="progress-bar" style="width: <?php echo $carbs_percent; ?>%;"></div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="report_history.php" class="btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y1="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Lihat Riwayat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Konfirmasi</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus makanan ini?</p>
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0);" id="confirmDelete" class="btn-delete">Hapus</a>
                <button type="button" class="btn-cancel" id="cancelDelete">Batal</button>
            </div>
        </div>
    </div>

    <div id="deleteReportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Konfirmasi</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus laporan ini?</p>
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0);" id="confirmDeleteReport" class="btn-delete">Hapus</a>
                <button type="button" class="btn-cancel" id="cancelDeleteReport">Batal</button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        var foodData = <?php echo json_encode($foods); ?>;
        var dailyCalorieLimit = <?php echo $daily_calorie_limit; ?>;
        var dailyProteinLimit = <?php echo $daily_protein_limit; ?>;
        var dailyCarbLimit = <?php echo $daily_carb_limit; ?>;
        var foodMap = {};

        foodData.forEach(function(food) {
            foodMap[food.name.toLowerCase()] = food;
        });

        function updateCurrentDate() {
            var now = new Date();
            var options = { day: 'numeric', month: 'long', year: 'numeric' };
            $("#today-date").text(now.toLocaleDateString('id-ID', options));

            if (now.getHours() === 0 && now.getMinutes() === 0 && now.getSeconds() < 5) {
                if (!window.midnightReset) {
                    window.midnightReset = true;
                    showInfoMessage("Hari telah berganti. Simpan laporan hari sebelumnya atau refresh halaman.");
                    setTimeout(function() { window.midnightReset = false; }, 5000);
                }
            } else {
                window.midnightReset = false;
            }
        }

        updateCurrentDate();
        setInterval(updateCurrentDate, 1000);

        setTimeout(function() {
            $(".success-message, .error-message").fadeOut(500);
        }, 3000);

        $("#food_name").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>",
                    dataType: "json",
                    data: { action: "suggest", term: request.term },
                    success: function(data) { response(data); },
                    error: function() { response([]); }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                fetchFoodData(ui.item.value);
            }
        });

        function fetchFoodData(foodName) {
            if (!foodName) return;
            $.ajax({
                url: "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>",
                dataType: "json",
                data: { action: "get_food_data", food_name: foodName },
                success: function(food) {
                    if (!food.error) {
                        updateFoodPreview(food);
                    } else {
                        clearFoodPreview();
                    }
                },
                error: function() {
                    clearFoodPreview();
                }
            });
        }

        function updateFoodPreview(food) {
            $("#calories").val(food.calories);
            $("#protein").val(food.protein);
            $("#carbs").val(food.carbs);
            $("#preview-food-name").text(food.name);
            $("#preview-calories").text(food.calories + " kkal");
            $("#preview-protein").text(food.protein + " g");
            $("#preview-carbs").text(food.carbs + " g");
            updateNutritionCalculation(food);
            $("#nutrition-preview").show();
        }

        function clearFoodPreview() {
            $("#calories").val("");
            $("#protein").val("");
            $("#carbs").val("");
            $("#preview-food-name").text("Pilih Makanan");
            $("#preview-calories").text("0");
            $("#preview-protein").text("0 g");
            $("#preview-carbs").text("0 g");
            $("#preview-total-calories").text("0");
            $("#nutrition-preview").hide();
        }

        $("#food_name").on("blur", function() {
            var foodName = $(this).val().trim();
            if (foodName.length > 0) {
                fetchFoodData(foodName);
            } else {
                clearFoodPreview();
            }
        });

        $("#gram").on("input", function() {
            var foodName = $("#food_name").val().trim();
            if (foodName.length > 0) {
                var calories = parseFloat($("#calories").val()) || 0;
                var protein = parseFloat($("#protein").val()) || 0;
                var carbs = parseFloat($("#carbs").val()) || 0;
                if (calories > 0 || protein > 0 || carbs > 0) {
                    var tempFood = { name: foodName, calories: calories, protein: protein, carbs: carbs };
                    updateNutritionCalculation(tempFood);
                } else {
                    fetchFoodData(foodName);
                }
            }
        });

        function updateNutritionCalculation(food) {
            if (!food) return;
            var grams = parseFloat($("#gram").val()) || 0;
            var ratio = grams / 100;
            var totalCalories = (parseFloat(food.calories) || 0) * ratio;
            $("#preview-total-calories").text(Math.round(totalCalories) + " kkal");
            $("#nutrition-preview").show();
        }

        $("#food-form").on("submit", function(e) {
            var foodName = $("#food_name").val().trim();
            var calories = $("#calories").val();
            var protein = $("#protein").val();
            var carbs = $("#carbs").val();
            var gram = parseFloat($("#gram").val()) || 0;

            if (foodName === "") {
                e.preventDefault();
                showErrorMessage("Nama makanan harus diisi");
                return false;
            }
            if (gram <= 0) {
                e.preventDefault();
                showErrorMessage("Jumlah gram harus lebih dari 0");
                return false;
            }
            if (calories === "" && protein === "" && carbs === "") {
                e.preventDefault();
                showErrorMessage("Data nutrisi tidak lengkap. Silakan masukkan nilai kalori, protein, dan karbohidrat.");
                return false;
            }
            return true;
        });

        function showErrorMessage(message) {
            $("#error-message").remove();
            var errorMessage = '<div class="error-message" id="error-message">' + message + '</div>';
            $(errorMessage).insertBefore("#nutrition-preview");
            setTimeout(function() { $("#error-message").fadeOut(500, function() { $(this).remove(); }); }, 3000);
        }

        function showInfoMessage(message) {
            $("#info-message").remove();
            var infoMessage = '<div class="info-message" id="info-message">' +
                '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                '<path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM11 15H9V9H11V15ZM11 7H9V5H11V7Z" fill="#0066CC"/>' +
                '</svg> ' + message + '</div>';
            $(infoMessage).insertBefore("#nutrition-preview");
            setTimeout(function() { $("#info-message").fadeOut(500, function() { $(this).remove(); }); }, 5000);
        }

        function showSuccessMessage(message) {
            $("#success-message").remove();
            var successMessage = '<div class="success-message" id="success-message">' +
                '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                '<path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="currentColor"/>' +
                '</svg> ' + message + '</div>';
            $(successMessage).insertBefore("#nutrition-preview");
            setTimeout(function() { $("#success-message").fadeOut(500, function() { $(this).remove(); }); }, 3000);
        }

        window.confirmDelete = function(id) {
            console.log("Konfirmasi hapus dipanggil dengan ID: " + id);
            var deleteModal = document.getElementById("deleteModal");
            deleteModal.style.display = "block";
            $("#confirmDelete").data("id", id);
        };

        var deleteModal = document.getElementById("deleteModal");
        var deleteModalClose = deleteModal.querySelector(".close");
        var cancelDelete = document.getElementById("cancelDelete");

        deleteModalClose.onclick = function() {
            deleteModal.style.display = "none";
        };
        cancelDelete.onclick = function() {
            deleteModal.style.display = "none";
        };

        $("#confirmDelete").on("click", function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            console.log("Mengirimkan permintaan hapus untuk ID: " + id);
            $.ajax({
                url: "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>",
                type: "GET",
                data: { action: "delete", id: id, ajax: "true" },
                dataType: "json",
                success: function(response) {
                    console.log("Respons dari server: ", response);
                    if (response.success) {
                        $("#food-item-" + id).fadeOut(300, function() {
                            $(this).remove();
                            if ($(".food-item").length === 0) {
                                $("#food-list").html(
                                    '<div class="empty-state" id="empty-state">' +
                                    '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                                    '<path d="M12 2a10 10 0 00-10 10c0 5.5 4.5 10 10 10s10-4.5 10-10a10 10 0 00-10-10z"></path>' +
                                    '<path d="M12 6v12m-6-6h12"></path>' +
                                    '</svg>' +
                                    '<p>Data makanan belum tersedia.</p>' +
                                    '<p>Mulai tambahkan makanan untuk melacak nutrisi hari ini.</p>' +
                                    '</div>'
                                );
                                resetNutritionTotals();
                            } else {
                                updateNutritionTotals();
                            }
                            showSuccessMessage("Makanan berhasil dihapus!");
                        });
                    } else {
                        showErrorMessage(response.message || "Gagal menghapus makanan");
                    }
                    deleteModal.style.display = "none";
                },
                error: function(xhr, status, error) {
                    console.error("Error AJAX: ", status, error);
                    showErrorMessage("Terjadi kesalahan saat menghapus item. Silakan coba lagi.");
                    deleteModal.style.display = "none";
                }
            });
        });

        var reportModal = document.getElementById("deleteReportModal");
        var reportModalClose = reportModal.querySelector(".close");
        var cancelReportDelete = document.getElementById("cancelDeleteReport");

        reportModalClose.onclick = function() {
            reportModal.style.display = "none";
        };
        cancelReportDelete.onclick = function() {
            reportModal.style.display = "none";
        };

        window.onclick = function(event) {
            if (event.target == deleteModal) deleteModal.style.display = "none";
            if (event.target == reportModal) reportModal.style.display = "none";
        };

        $("#confirmDeleteReport").on("click", function(e) {
            e.preventDefault();
            var reportId = $(this).data("id");
            $.ajax({
                url: "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>",
                type: "GET",
                data: { delete_report: reportId, ajax: "true" },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage("Laporan berhasil dihapus!");
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showErrorMessage(response.message || "Gagal menghapus laporan");
                    }
                    reportModal.style.display = "none";
                },
                error: function() {
                    showErrorMessage("Terjadi kesalahan saat menghapus laporan. Silakan coba lagi.");
                    reportModal.style.display = "none";
                }
            });
        });

        function resetNutritionTotals() {
            $("#total-calories").text("0");
            $("#total-protein").text("0 g");
            $("#total-carbs").text("0 g");
            $("#report-calories").text("0 / " + Math.round(dailyCalorieLimit) + " kkal");
            $("#report-protein").text("0 / " + Math.round(dailyProteinLimit * 10) / 10 + " g");
            $("#report-carbs").text("0 / " + Math.round(dailyCarbLimit * 10) / 10 + " g");
            $(".progress-bar").css("width", "0%");
        }

        function updateNutritionTotals() {
            var totalCalories = 0;
            var totalProtein = 0;
            var totalCarbs = 0;

            $(".food-item").each(function() {
                var calories = parseFloat($(this).data("calories")) || 0;
                var protein = parseFloat($(this).data("protein")) || 0;
                var carbs = parseFloat($(this).data("carbs")) || 0;

                totalCalories += calories;
                totalProtein += protein;
                totalCarbs += carbs;
            });

            $("#total-calories").text(Math.round(totalCalories));
            $("#report-calories").text(Math.round(totalCalories) + " / " + Math.round(dailyCalorieLimit) + " kkal");
            var caloriePercent = dailyCalorieLimit > 0 ? Math.min(100, Math.round((totalCalories / dailyCalorieLimit) * 100)) : 0;
            $("#report-calories").parent().next(".report-progress").find(".progress-bar").css("width", caloriePercent + "%");

            $("#total-protein").text(Math.round(totalProtein * 10) / 10 + " g");
            $("#total-carbs").text(Math.round(totalCarbs * 10) / 10 + " g");
            $("#report-protein").text(Math.round(totalProtein * 10) / 10 + " / " + Math.round(dailyProteinLimit * 10) / 10 + " g");
            $("#report-carbs").text(Math.round(totalCarbs * 10) / 10 + " / " + Math.round(dailyCarbLimit * 10) / 10 + " g");

            var proteinPercent = dailyProteinLimit > 0 ? Math.min(100, Math.round((totalProtein / dailyProteinLimit) * 100)) : 0;
            var carbsPercent = dailyCarbLimit > 0 ? Math.min(100, Math.round((totalCarbs / dailyCarbLimit) * 100)) : 0;
            $("#report-protein").parent().next(".report-progress").find(".progress-bar").css("width", proteinPercent + "%");
            $("#report-carbs").parent().next(".report-progress").find(".progress-bar").css("width", carbsPercent + "%");
        }
    });
    </script>
</body>
</html>