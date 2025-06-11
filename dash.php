<?php
// dashboard.php - Dashboard untuk aplikasi Calorie Counter

session_start();

// Periksa session dan user
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/config.php';

$username = $_SESSION['username'];
$success_message = '';
$error_message = '';
$user = null;
$bmr = 0;
$tdee = 0;
$daily_intake = [
    'calories' => 0,
    'protein' => 0,
    'carbohydrates' => 0
];

// Ambil data pengguna
$query = "SELECT * FROM users WHERE username = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_destroy();
        header("Location: login.php?error=User+not+found");
        exit();
    }
} else {
    $error_message = "Gagal menyiapkan query: " . $conn->error;
}

// Proses pembaruan profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $height_cm = filter_input(INPUT_POST, 'height_cm', FILTER_VALIDATE_FLOAT);
    $weight_cm = filter_input(INPUT_POST, 'weight_cm', FILTER_VALIDATE_FLOAT);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $activity_level = filter_input(INPUT_POST, 'activity_level', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $goal = filter_input(INPUT_POST, 'goal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $valid_genders = ['male', 'female'];
    $valid_activity_levels = ['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extremely_active'];
    $valid_goals = ['weight_loss', 'maintenance', 'bulking'];

    if ($age === false || $age < 1 || $age > 120 ||
        $height_cm === false || $height_cm < 50 || $height_cm > 250 ||
        $weight_cm === false || $weight_cm < 20 || $weight_cm > 200 ||
        !in_array($gender, $valid_genders) ||
        !in_array($activity_level, $valid_activity_levels) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($goal, $valid_goals)) {
        $error_message = "Data tidak valid. Pastikan semua field diisi dengan benar.";
    } else {
        $query = "UPDATE users 
                  SET age = ?, height_cm = ?, weight_cm = ?, gender = ?, 
                      activity_level = ?, email = ?, goal = ?
                  WHERE username = ?";
        if ($update_stmt = $conn->prepare($query)) {
            $update_stmt->bind_param("iddsssss", 
                $age, $height_cm, $weight_cm, $gender, $activity_level, 
                $email, $goal, $username);
            if ($update_stmt->execute()) {
                $success_message = "Profil berhasil diperbarui!";
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $error_message = "Gagal memperbarui profil: " . $conn->error;
            }
            $update_stmt->close();
        } else {
            $error_message = "Gagal menyiapkan query update: " . $conn->error;
        }
    }
}

// Siapkan data untuk ditampilkan
if ($user) {
    if (isset($user['gender'], $user['weight_cm'], $user['height_cm'], $user['age'], $user['activity_level'], $user['goal'])) {
        // Hitung BMR
        $bmr = ($user['gender'] === 'male')
            ? (10 * $user['weight_cm'] + 6.25 * $user['height_cm'] - 5 * $user['age'] + 5)
            : (10 * $user['weight_cm'] + 6.25 * $user['height_cm'] - 5 * $user['age'] - 161);

        // Hitung TDEE
        $activity_multipliers = [
            'sedentary' => 1.2,
            'lightly_active' => 1.375,
            'moderately_active' => 1.55,
            'very_active' => 1.725,
            'extremely_active' => 1.9
        ];
        $tdee = $bmr * ($activity_multipliers[$user['activity_level']] ?? 1.2);
    }

    $today = date('Y-m-d');
    $query = "SELECT 
                COALESCE(SUM(f.calories * fl.grams / 100), 0) as total_calories,
                COALESCE(SUM(f.protein * fl.grams / 100), 0) as total_protein,
                COALESCE(SUM(f.carbs * fl.grams / 100), 0) as total_carbs
              FROM food_logs fl
              JOIN foods f ON fl.food_id = f.id
              WHERE fl.user_id = ? AND DATE(fl.log_date) = ?";
    if ($nutrition_stmt = $conn->prepare($query)) {
        $nutrition_stmt->bind_param("is", $user['id'], $today);
        $nutrition_stmt->execute();
        $nutrition_data = $nutrition_stmt->get_result()->fetch_assoc();
        $nutrition_stmt->close();

        if ($nutrition_data) {
            $daily_intake = [
                'calories' => $nutrition_data['total_calories'],
                'protein' => $nutrition_data['total_protein'],
                'carbohydrates' => $nutrition_data['total_carbs']
            ];
        }
    } else {
        error_log("Error menyiapkan query nutrisi: " . $conn->error);
    }
}

function getBMICategory($bmi) {
    if ($bmi < 18.5) return 'Berat Badan Kurang';
    if ($bmi < 25) return 'Berat Badan Normal';
    if ($bmi < 30) return 'Berat Badan Berlebih';
    return 'Obesitas';
}

$bmi = 0;
$bmi_category = '';
if (isset($user['weight_cm'], $user['height_cm']) && $user['height_cm'] > 0) {
    $height_m = $user['height_cm'] / 100;
    $bmi = $user['weight_cm'] / ($height_m * $height_m);
    $bmi_category = getBMICategory($bmi);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Food Tracker</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="food_tracker.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>
<body>
    <div class="user-info">
        Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <div class="menu-bar">
            <a href="food_tracker.php" class="menu-item">Food Tracker</a>
            <a href="report_history.php" class="menu-item">Riwayat Laporan</a>
            <a href="dash.php" class="menu-item active">Dashboard</a>
        </div>

        <div class="header">
            <h1>DASHBOARD</h1>
            <p>Kelola profil dan lihat informasi pribadi Anda</p>
        </div>

        <?php if ($success_message): ?>
            <div class="success-message" id="success-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="#155724"/>
                </svg>
                <?php echo htmlspecialchars($success_message); ?>
                <button onclick="document.getElementById('success-message').style.display='none'" class="close-btn">×</button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message" id="error-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM11 15H9V13H11V15ZM11 11H9V5H11V11Z" fill="#721c24"/>
                </svg>
                <?php echo htmlspecialchars($error_message); ?>
                <button onclick="document.getElementById('error-message').style.display='none'" class="close-btn">×</button>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="profile-section">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Informasi Pribadi
                </h2>
                <div class="profile-info">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    <p><strong>Usia:</strong> <?php echo htmlspecialchars($user['age'] ?? ''); ?> tahun</p>
                    <p><strong>Tinggi Badan:</strong> <?php echo htmlspecialchars($user['height_cm'] ?? ''); ?> cm</p>
                    <p><strong>Berat Badan:</strong> <?php echo htmlspecialchars($user['weight_cm'] ?? ''); ?> kg</p>
                    <p><strong>Jenis Kelamin:</strong> <?php echo htmlspecialchars($user['gender'] ?? '') === 'male' ? 'Laki-laki' : 'Perempuan'; ?></p>
                    <p><strong>Level Aktivitas:</strong> <?php echo htmlspecialchars($user['activity_level'] ?? ''); ?></p>
                    <p><strong>Tujuan:</strong> <?php echo htmlspecialchars($user['goal'] ?? ''); ?></p>
                </div>
            </div>

            <div class="nutrition-limits">
                <?php if ($bmi > 0): ?>
                <div id="bmi-result">
                    <h3>Indeks Massa Tubuh (BMI)</h3>
                    <p><strong>BMI Anda:</strong> <span id="bmi-value"><?php echo number_format($bmi, 2); ?></span></p>
                    <p><strong>Kategori:</strong> <span id="bmi-category"><?php echo $bmi_category; ?></span></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="edit-profile-section">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit Profil
                </h2>
                <form id="edit-profile-form" method="POST" action="dash.php">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="age">Usia (tahun)</label>
                        <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user['age'] ?? ''); ?>" required min="1" max="120">
                    </div>
                    <div class="form-group">
                        <label for="height_cm">Tinggi Badan (cm)</label>
                        <input type="number" id="height_cm" name="height_cm" value="<?php echo htmlspecialchars($user['height_cm'] ?? ''); ?>" required min="50" max="250" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="weight_cm">Berat Badan (kg)</label>
                        <input type="number" id="weight_cm" name="weight_cm" value="<?php echo htmlspecialchars($user['weight_cm'] ?? ''); ?>" required min="20" max="200" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="gender">Jenis Kelamin</label>
                        <select id="gender" name="gender" required>
                            <option value="male" <?php echo ($user['gender'] ?? 'male') === 'male' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="female" <?php echo ($user['gender'] ?? 'male') === 'female' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity_level">Level Aktivitas</label>
                        <select id="activity_level" name="activity_level" required>
                            <option value="sedentary" <?php echo ($user['activity_level'] ?? 'sedentary') === 'sedentary' ? 'selected' : ''; ?>>Sedentary (jarang bergerak)</option>
                            <option value="lightly_active" <?php echo ($user['activity_level'] ?? 'sedentary') === 'lightly_active' ? 'selected' : ''; ?>>Lightly Active (olahraga ringan 1-3 hari/minggu)</option>
                            <option value="moderately_active" <?php echo ($user['activity_level'] ?? 'sedentary') === 'moderately_active' ? 'selected' : ''; ?>>Moderately Active (olahraga sedang 3-5 hari/minggu)</option>
                            <option value="very_active" <?php echo ($user['activity_level'] ?? 'sedentary') === 'very_active' ? 'selected' : ''; ?>>Very Active (olahraga berat 6-7 hari/minggu)</option>
                            <option value="extremely_active" <?php echo ($user['activity_level'] ?? 'sedentary') === 'extremely_active' ? 'selected' : ''; ?>>Extremely Active (olahraga sangat berat & pekerjaan fisik)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="goal">Tujuan</label>
                        <select id="goal" name="goal" required>
                            <option value="weight_loss" <?php echo ($user['goal'] ?? 'maintenance') === 'weight_loss' ? 'selected' : ''; ?>>Diet</option>
                            <option value="maintenance" <?php echo ($user['goal'] ?? 'maintenance') === 'maintenance' ? 'selected' : ''; ?>>Menjaga bb</option>
                            <option value="bulking" <?php echo ($user['goal'] ?? 'maintenance') === 'bulking' ? 'selected' : ''; ?>>Menaikkan BB</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Simpan Perubahan</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state" style="text-align: center; padding: 40px 20px;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <p style="font-size: 18px; margin-top: 15px; color: #666;">Data profil tidak ditemukan.</p>
                <p style="color: #888;">Silakan isi profil Anda untuk melanjutkan.</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .close-btn {
            background: none;
            border: none;
            float: right;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin: 0;
            color: inherit;
        }
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
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('edit-profile-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const age = parseInt(document.getElementById('age').value);
                    const height = parseFloat(document.getElementById('height_cm').value);
                    const weight = parseFloat(document.getElementById('weight_cm').value);
                    
                    let isValid = true;
                    let errorMsg = "";
                    
                    if (isNaN(age) || age < 1 || age > 120) {
                        isValid = false;
                        errorMsg += "• Usia harus antara 1-120 tahun\n";
                    }
                    if (isNaN(height) || height < 50 || height > 250) {
                        isValid = false;
                        errorMsg += "• Tinggi badan harus antara 50-250 cm\n";
                    }
                    if (isNaN(weight) || weight < 20 || weight > 200) {
                        isValid = false;
                        errorMsg += "• Berat badan harus antara 20-200 kg\n";
                    }
                    if (!isValid) {
                        e.preventDefault();
                        alert("Validasi gagal:\n" + errorMsg);
                    }
                });
            }
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 5000);
            }
            if (errorMessage) {
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>