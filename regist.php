<?php
// Cek jika sudah submit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "web_login");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Sanitasi dan validasi input
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $age = (int)$_POST['age'];
    $height_cm = (float)$_POST['height_cm'];
    $weight_cm = (float)$_POST['weight_cm'];
    $gender = $_POST['gender'];
    $activity_level = $_POST['activity_level'];
    $goal = $_POST['goal'];

    // Validasi server-side
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if ($age < 13 || $age > 100) {
        $errors[] = "Umur harus antara 13-100 tahun";
    }
    
    if ($height_cm < 100 || $height_cm > 250) {
        $errors[] = "Tinggi harus antara 100-250 cm";
    }
    
    if ($weight_cm < 30 || $weight_cm > 300) {
        $errors[] = "Berat harus antara 30-300 kg";
    }
    
    if (!in_array($gender, ['male', 'female'])) {
        $errors[] = "Gender tidak valid";
    }
    
    if (!in_array($activity_level, ['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extremely_active'])) {
        $errors[] = "Level aktivitas tidak valid";
    }
    
    if (!in_array($goal, ['weight_loss', 'maintenance', 'bulking'])) {
        $errors[] = "Goal tidak valid";
    }

    if (empty($errors)) {
        // Hash password
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Cek username/email dengan prepared statement
        $cek = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $cek->bind_param("ss", $username, $email);
        $cek->execute();
        $result = $cek->get_result();
        
        if ($result->num_rows > 0) {
            $error_msg = "Username atau email sudah terdaftar.";
        } else {
            // Insert data dengan prepared statement
            $insert = $conn->prepare("INSERT INTO users (username, password, email, age, height_cm, weight_cm, gender, activity_level, goal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("sssiidsss", $username, $password_hashed, $email, $age, $height_cm, $weight_cm, $gender, $activity_level, $goal);
            
            if ($insert->execute()) {
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Gagal register: " . $conn->error;
            }
            $insert->close();
        }
        $cek->close();
    } else {
        $error_msg = implode(", ", $errors);
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #73e0d4, #5bbee5);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 450px;
            max-width: 100%;
            padding: 30px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            color: #777;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-size: 15px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #2ecc71;
            outline: none;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.2);
        }

        .inline-groups {
            display: flex;
            gap: 15px;
        }

        .inline-groups .form-group {
            flex: 1;
        }

        .input-suffix {
            position: relative;
        }

        .input-suffix input {
            padding-right: 50px;
        }

        .suffix {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            font-size: 14px;
            line-height: 1;
            display: flex;
            align-items: center;
        }

        .btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }

        .footer a {
            color: #2ecc71;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
            accent-color: #2ecc71;
            width: 16px;
            height: 16px;
        }

        .radio-option label {
            margin-bottom: 0;
            cursor: pointer;
            font-size: 14px;
        }

        .goal-radio-group {
            display: flex;
            gap: 10px;
            margin-top: 5px;
            flex-wrap: wrap;
        }

        .goal-radio-group .radio-option {
            flex: 1;
            min-width: 120px;
            justify-content: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .goal-radio-group .radio-option:hover {
            background-color: #f8f9fa;
        }

        .goal-radio-group .radio-option input[type="radio"]:checked + label {
            color: #2ecc71;
            font-weight: 600;
        }

        .goal-radio-group .radio-option:has(input:checked) {
            background-color: #e8f5e8;
            border-color: #2ecc71;
        }

        select.form-control {
            background-color: white;
            cursor: pointer;
        }

        select.form-control option {
            padding: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Create Account</h2>
            <p>Please fill in your details for BMR & TDEE calculation</p>
        </div>
        
        <?php if (isset($error_msg)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
        <?php endif; ?>
        
        <!-- PERBAIKAN: Ubah action menjadi regist.php -->
        <form id="registerForm" action="regist.php" method="POST" autocomplete="off">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                <div class="error-message" id="username-error">Username must be at least 3 characters</div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Create a password" 
                       autocomplete="new-password" 
                       required>
                <div class="error-message" id="password-error">Password must be at least 6 characters</div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Enter your email address" 
                       required>
                <div class="error-message" id="email-error">Please enter a valid email address</div>
            </div>
            
            <div class="form-group">
                <label>Gender</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="gender-male" name="gender" value="male" required checked>
                        <label for="gender-male">Male</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="gender-female" name="gender" value="female">
                        <label for="gender-female">Female</label>
                    </div>
                </div>
                <div class="error-message" id="gender-error"></div>
            </div>

            <div class="inline-groups">
                <div class="form-group">
                    <label for="age">Age</label>
                    <div class="input-suffix">
                        <input type="number" class="form-control" id="age" name="age" 
                               placeholder="Age" min="13" max="100" required>
                        <span class="suffix">years</span>
                    </div>
                    <div class="error-message" id="age-error">Age must be between 13-100 years</div>
                </div>
                
                <div class="form-group">
                    <label for="height_cm">Height</label>
                    <div class="input-suffix">
                        <input type="number" class="form-control" id="height_cm" name="height_cm" 
                               placeholder="Height" min="100" max="250" required>
                        <span class="suffix">cm</span>
                    </div>
                    <div class="error-message" id="height-error">Height must be between 100-250 cm</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="weight_cm">Weight</label>
                <div class="input-suffix">
                    <input type="number" class="form-control" id="weight_cm" name="weight_cm" 
                           placeholder="Weight" min="30" max="300" step="0.1" required>
                    <span class="suffix">kg</span>
                </div>
                <div class="error-message" id="weight-error">Weight must be between 30-300 kg</div>
            </div>

            <div class="form-group">
                <label for="activity_level">Activity Level</label>
                <select class="form-control" id="activity_level" name="activity_level" required>
                    <option value="">Select your activity level</option>
                    <option value="sedentary">Sedentary (Little/no exercise)</option>
                    <option value="lightly_active">Lightly Active (Light exercise 1-3 days/week)</option>
                    <option value="moderately_active">Moderately Active (Moderate exercise 3-5 days/week)</option>
                    <option value="very_active">Very Active (Hard exercise 6-7 days/week)</option>
                    <option value="extremely_active">Extremely Active (Very hard exercise, physical job)</option>
                </select>
                <div class="error-message" id="activity-error">Please select your activity level</div>
            </div>

            <div class="form-group">
                <label>Goal</label>
                <div class="goal-radio-group">
                    <div class="radio-option">
                        <input type="radio" id="goal-loss" name="goal" value="weight_loss" required>
                        <label for="goal-loss">Weight Loss</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="goal-maintain" name="goal" value="maintenance" required>
                        <label for="goal-maintain">Maintenance</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="goal-bulk" name="goal" value="bulking" required>
                        <label for="goal-bulk">Bulking</label>
                    </div>
                </div>
                <div class="error-message" id="goal-error">Please select your goal</div>
            </div>
            
            <button type="submit" class="btn" id="registerBtn">Register</button>
            
            <div class="footer">
                <!-- PERBAIKAN: Ubah link ke login1.php -->
                Already have an account? <a href="index.php" id="loginLink">Login here</a>
            </div>
        </form>
    </div>

    <script>
        // Script untuk validasi form
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const email = document.getElementById('email').value;
            const age = document.getElementById('age').value;
            const height = document.getElementById('height_cm').value;
            const weight = document.getElementById('weight_cm').value;
            const activityLevel = document.getElementById('activity_level').value;
            const genderSelected = document.querySelector('input[name="gender"]:checked');
            const goalSelected = document.querySelector('input[name="goal"]:checked');
            
            let isValid = true;
            
            // Reset error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.style.display = 'none';
            });
            
            // Validate username
            if (username.length < 3) {
                document.getElementById('username-error').style.display = 'block';
                isValid = false;
            }
            
            // Validate password
            if (password.length < 6) {
                document.getElementById('password-error').style.display = 'block';
                isValid = false;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }
            
            // Validate gender
            if (!genderSelected) {
                document.getElementById('gender-error').style.display = 'block';
                document.getElementById('gender-error').textContent = 'Please select your gender';
                isValid = false;
            }

            // Validate age
            if (age < 13 || age > 100) {
                document.getElementById('age-error').style.display = 'block';
                isValid = false;
            }
            
            // Validate height
            if (height < 100 || height > 250) {
                document.getElementById('height-error').style.display = 'block';
                isValid = false;
            }
            
            // Validate weight
            if (weight < 30 || weight > 300) {
                document.getElementById('weight-error').style.display = 'block';
                isValid = false;
            }

            // Validate activity level
            if (!activityLevel) {
                document.getElementById('activity-error').style.display = 'block';
                isValid = false;
            }

            // Validate goal
            if (!goalSelected) {
                document.getElementById('goal-error').style.display = 'block';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>