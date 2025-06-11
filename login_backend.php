<?php
// Clear any output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Initialize response
$response = [
    'success' => false, 
    'message' => '', 
    'redirect' => '',
    'debug' => []
];

try {
    // Start session
    session_start();
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan!');
    }

    // Get and validate input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('Username dan password harus diisi!');
    }
    
    // Database configuration
    $host = 'localhost';
    $db_name = 'web_login';
    $db_username = 'root';
    $db_password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        throw new Exception('Koneksi database gagal: ' . $e->getMessage());
    }
    
    // Prepare and execute query
    $query = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_data'] = $user;
            
            $response['success'] = true;
            $response['message'] = 'Login berhasil! Mengalihkan...';
            $response['redirect'] = 'food_tracker.php';
        } else {
            throw new Exception('Password salah!');
        }
    } else {
        throw new Exception('Username tidak ditemukan!');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Error $e) {
    $response['message'] = 'System error: ' . $e->getMessage();
}

// Clean output buffer and send JSON
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit();
?>
