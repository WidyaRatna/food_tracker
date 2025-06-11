<?php
// report_history.php - Halaman untuk menampilkan riwayat laporan nutrisi

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Set zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');
$conn = new mysqli("localhost", "root", "", "web_login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+07:00'");

// Ambil user_id dari sesi
$username = $_SESSION['username'];
$user_query = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$stmt->close();

// Tangani penghapusan laporan
if (isset($_GET['delete_report'])) {
    $report_id = intval($_GET['delete_report']);
    $stmt = $conn->prepare("SELECT id FROM daily_reports WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Hapus log makanan terkait
        $delete_logs_query = "DELETE FROM food_logs WHERE user_id = ? AND log_date = (SELECT report_date FROM daily_reports WHERE id = ?)";
        $stmt = $conn->prepare($delete_logs_query);
        $stmt->bind_param("ii", $user_id, $report_id);
        $stmt->execute();

        // Hapus laporan
        $stmt = $conn->prepare("DELETE FROM daily_reports WHERE id = ?");
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $stmt->close();

        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dihapus']);
            exit();
        }

        header("Location: report_history.php?report_deleted=true");
        exit();
    } else {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan atau izin ditolak']);
            exit();
        }

        header("Location: report_history.php?error=permission");
        exit();
    }
}

// Ambil laporan harian pengguna
$reports_query = "SELECT id, report_date, total_calories, total_protein, total_carbs, bmr, tdee, calorie_limit, protein_limit, carb_limit 
                 FROM daily_reports 
                 WHERE user_id = ? 
                 ORDER BY report_date DESC";
$stmt = $conn->prepare($reports_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reports_result = $stmt->get_result();

$daily_reports = [];
$report_dates = [];

if ($reports_result->num_rows > 0) {
    while ($row = $reports_result->fetch_assoc()) {
        $date_obj = new DateTime($row['report_date']);
        $row['formatted_date'] = $date_obj->format("d F Y");
        $daily_reports[] = $row;
        $report_dates[] = $row['report_date'];
    }
}
$stmt->close();

// Ambil log makanan untuk setiap tanggal laporan
$food_logs = [];
if (!empty($report_dates)) {
    $date_params = str_repeat("?,", count($report_dates) - 1) . "?";
    $food_query = "SELECT fl.id, fl.grams, f.name, f.calories, f.protein, f.carbs, fl.log_date as log_date
                   FROM food_logs fl 
                   JOIN foods f ON fl.food_id = f.id 
                   WHERE fl.user_id = ? AND fl.log_date IN ($date_params)
                   ORDER BY fl.log_date DESC";
    $params = array_merge([$user_id], $report_dates);
    $param_types = str_repeat("s", count($params));
    $stmt = $conn->prepare($food_query);

    if ($stmt) {
        $bind_params = array();
        $bind_params[] = &$param_types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_params[] = &$params[$i];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);

        $stmt->execute();
        $food_result = $stmt->get_result();

        while ($row = $food_result->fetch_assoc()) {
            $gram_ratio = $row['grams'] / 100;
            $row['calculated_calories'] = round($row['calories'] * $gram_ratio, 1);
            $row['calculated_protein'] = round($row['protein'] * $gram_ratio, 1);
            $row['calculated_carbs'] = round($row['carbs'] * $gram_ratio, 1);
            $food_logs[$row['log_date']][] = $row;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - Food Tracker</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="food_tracker.css">
    <link rel="stylesheet" href="report_history.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 550px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .user-info {
            text-align: right;
            margin: 20px 0;
        }

        .user-info strong {
           color: #2ecc71;
        }

         .logout-btn {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #6c757d;
    padding: 5px 10px;
    border-radius: 5px;
    margin-left: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 12px;
    text-decoration: none;
}
        .content {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .menu-bar {
            display: flex;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .menu-item {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .menu-item.active {
            background-color: #4CAF50;
            color: white;
        }

        .menu-item:hover:not(.active) {
            background-color: #e0e0e0;
        }

        .header h1 {
            margin: 0 0 20px;
            color: #333;
            font-size: 1.5em;
        }

        .header p {
            color: #666;
            margin: 0 0 25px;
            font-size: 0.9em;
        }

        .success-message, .error-message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
        }

        .report-section {
            background-color:#FCFFFE;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .report-date {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .report-data {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            color: #555;
            font-size: 0.95em;
        }

        .report-label {
            font-weight: 500;
        }

        .report-value {
            color: #333;
        }

        .report-progress {
            background-color: #eee;
            border-radius: 4px;
            height: 8px;
            margin: 8px 0 15px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #4CAF50;
            height: 100%;
            transition: width 0.3s ease;
        }

        .food-items-list {
            margin: 15px 0;
            background-color: #F7F7F7;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .food-item-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .food-item-detail:last-child {
            border-bottom: none;
        }

        .food-list-title {
            font-weight: 500;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
        }

        .food-list-title svg {
            margin-right: 5px;
        }

        .toggle-food-list {
            background: none;
            border: none;
            color: #4CAF50;
            cursor: pointer;
            font-weight: 500;
            padding: 8px 0;
            margin: 10px 0;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .toggle-food-list svg {
            margin-right: 5px;
        }

        .food-name {
            font-weight: 500;
        }

        .food-detail {
            color: #666;
            font-size: 14px;
        }

        .delete-btn {
            color: #c62828;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            font-size: 14px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close {
            cursor: pointer;
            font-size: 20px;
            color: #666;
        }

        .modal-body p {
            margin: 0 0 15px;
            color: #333;
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        .btn-delete {
            background-color: #c62828;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn-cancel {
            background-color: #ccc;
            color: #333;
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            margin-left: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
            margin-top: 30px;
        }

        .empty-state svg {
            margin-bottom: 12px;
        }

        .empty-state p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <div id="report-section" class="content">
            <div class="menu-bar">
                <a href="food_tracker.php" class="menu-item">Food Tracker</a>
                <a href="report_history.php" class="menu-item active">Riwayat Laporan</a>
                <a href="dash.php" class="menu-item">Dashboard</a>
            </div>

            <div class="header">
                <h1>RIWAYAT LAPORAN</h1>
                <p>Laporan nutrisi yang telah disimpan</p>
            </div>

            <?php if (isset($_GET['report_deleted']) && $_GET['report_deleted'] == 'true'): ?>
            <div class="success-message" id="report-deleted-message">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="#155724"/>
                </svg>
                Laporan berhasil dihapus!
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'permission'): ?>
            <div class="error-message" id="error-message">
                Tidak bisa menghapus laporan. Anda tidak memiliki izin atau laporan tidak ditemukan.
            </div>
            <?php endif; ?>

            <?php if (count($daily_reports) > 0): ?>
                <?php foreach ($daily_reports as $report): ?>
                <div class="report-section" id="report-<?php echo $report['id']; ?>">
                    <div class="report-date"><?php echo $report['formatted_date']; ?></div>

                    <div class="report-data">
                        <span class="report-label">BMR </span>
                        <span class="report-value"><?php echo round($report['bmr']); ?> kkal</span>
                    </div>

                    <div class="report-data">
                        <span class="report-label">TDEE</span>
                        <span class="report-value"><?php echo round($report['tdee']); ?> kkal</span>
                    </div>

                    <div class="report-data">
                        <span class="report-label">Total Kalori</span>
                        <span class="report-value"><?php echo round($report['total_calories']); ?> / <?php echo round($report['calorie_limit']); ?> kkal</span>
                    </div>
                    <div class="report-progress">
                        <?php $calorie_percent = ($report['calorie_limit'] > 0) ? min(100, round(($report['total_calories'] / $report['calorie_limit']) * 100)) : 0; ?>
                        <div class="progress-bar" style="width: <?php echo $calorie_percent; ?>%;"></div>
                    </div>

                    <div class="report-data">
                        <span class="report-label">Total Protein</span>
                        <span class="report-value"><?php echo round($report['total_protein'], 1); ?> / <?php echo round($report['protein_limit'], 1); ?> g</span>
                    </div>
                    <div class="report-progress">
                        <?php $protein_percent = ($report['protein_limit'] > 0) ? min(100, round(($report['total_protein'] / $report['protein_limit']) * 100)) : 0; ?>
                        <div class="progress-bar" style="width: <?php echo $protein_percent; ?>%;"></div>
                    </div>

                    <div class="report-data">
                        <span class="report-label">Total Karbohidrat</span>
                        <span class="report-value"><?php echo round($report['total_carbs'], 1); ?> / <?php echo round($report['carb_limit'], 1); ?> g</span>
                    </div>
                    <div class="report-progress">
                        <?php $carbs_percent = ($report['carb_limit'] > 0) ? min(100, round(($report['total_carbs'] / $report['carb_limit']) * 100)) : 0; ?>
                        <div class="progress-bar" style="width: <?php echo $carbs_percent; ?>%;"></div>
                    </div>

                    <?php 
                    $report_date = $report['report_date'];
                    if (isset($food_logs[$report_date]) && !empty($food_logs[$report_date])): 
                    ?>
                    <button class="toggle-food-list" onclick="toggleFoodList('food-list-<?php echo $report['id']; ?>')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                        Tampilkan Makanan
                    </button>

                    <div class="food-items-list" id="food-list-<?php echo $report['id']; ?>" style="display: none;">
                        <div class="food-list-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                <line x1="6" y1="1" x2="6" y2="4"></line>
                                <line x1="10" y1="1" x2="10" y2="4"></line>
                                <line x1="14" y1="1" x2="14" y2="4"></line>
                            </svg>
                            Daftar Makanan:
                        </div>

                        <?php foreach ($food_logs[$report_date] as $food): ?>
                        <div class="food-item-detail">
                            <div>
                                <span class="food-name"><?php echo htmlspecialchars($food['name']); ?></span>
                                <span class="food-detail">(<?php echo $food['grams']; ?> g)</span>
                            </div>
                            <div class="food-detail">
                                <?php echo $food['calculated_calories']; ?> kkal | 
                                <?php echo $food['calculated_protein']; ?> g protein | 
                                <?php echo $food['calculated_carbs']; ?> g karbo
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div style="text-align: right; margin-top: 15px;">
                        <a href="javascript:void(0);" onclick="confirmDeleteReport(<?php echo $report['id']; ?>)" class="delete-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            Hapus Laporan
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p>Belum ada laporan yang disimpan.</p>
                    <p>Simpan laporan nutrisi hari ini untuk melihatnya di sini.</p>
                </div>
            <?php endif; ?>
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
        function confirmDeleteReport(id) {
            var modal = document.getElementById("deleteReportModal");
            var confirmBtn = document.getElementById("confirmDeleteReport");
            var cancelBtn = document.getElementById("cancelDeleteReport");
            var closeBtn = document.getElementsByClassName("close")[0];

            modal.style.display = "block";

            confirmBtn.onclick = function() {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "report_history.php?delete_report=" + id + "&ajax=true", true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var reportElement = document.getElementById("report-" + id);
                            if (reportElement) {
                                reportElement.remove();
                            }

                            var successMsg = document.createElement("div");
                            successMsg.className = "success-message";
                            successMsg.innerHTML = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                                '<path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM8 15L3 10L4.41 8.59L8 12.17L15.59 4.58L17 6L8 15Z" fill="#155724"/>' +
                                '</svg>' +
                                'Laporan berhasil dihapus!';

                            document.querySelector("#report-section .header").insertAdjacentElement('afterend', successMsg);

                            setTimeout(function() {
                                successMsg.style.display = "none";
                            }, 3000);

                            if (document.querySelectorAll(".report-section").length === 0) {
                                var emptyState = document.createElement("div");
                                emptyState.className = "empty-state";
                                emptyState.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
                                    '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>' +
                                    '<line x1="16" y1="2" x2="16" y2="6"></line>' +
                                    '<line x1="8" y1="2" x2="8" y2="6"></line>' +
                                    '<line x1="3" y1="10" x2="21" y2="10"></line>' +
                                    '</svg>' +
                                    '<p>Belum ada laporan yang disimpan.</p>' +
                                    '<p>Simpan laporan nutrisi hari ini untuk melihatnya di sini.</p>';

                                document.getElementById("report-section").appendChild(emptyState);
                            }
                        }
                    }
                };
                xhr.send();
                modal.style.display = "none";
            };

            cancelBtn.onclick = function() {
                modal.style.display = "none";
            };

            closeBtn.onclick = function() {
                modal.style.display = "none";
            };

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            };
        }

        function toggleFoodList(elementId) {
            var foodList = document.getElementById(elementId);
            var isHidden = foodList.style.display === 'none';
            foodList.style.display = isHidden ? 'block' : 'none';
            var button = foodList.previousElementSibling;
            if (isHidden) {
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg> Sembunyikan Makanan';
            } else {
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg> Tampilkan Makanan';
            }
        }

        setTimeout(function() {
            var successMessages = document.querySelectorAll('.success-message');
            for (var i = 0; i < successMessages.length; i++) {
                successMessages[i].style.display = 'none';
            }
            var errorMessages = document.querySelectorAll('.error-message');
            for (var i = 0; i < errorMessages.length; i++) {
                errorMessages[i].style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>