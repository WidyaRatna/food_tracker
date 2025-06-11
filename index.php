<?php
session_start();
$error_msg = '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Tracker</title>
    <style>
        /* Reset dan Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #73e0d4, #5bbee5);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Container */
        .container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 400px;
            max-width: 100%;
            padding: 30px;
        }

        /* Header */
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

        /* Form Elements */
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
            background-color: #fff;
        }

        .form-control:focus {
            border-color: #2ecc71;
            outline: none;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.2);
        }

        .form-control:hover {
            border-color: #bdc3c7;
        }

        .form-control.error {
            border-color: #e74c3c;
        }

        .form-control.success {
            border-color: #2ecc71;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            position: relative;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login {
            background-color: #2ecc71;
            color: white;
        }

        .btn-login:hover:not(:disabled) {
            background-color: #27ae60;
        }

        .btn-register {
            background-color: #2ecc71;
            color: white;
        }

        .btn-register:hover {
            background-color: #27ae60;
        }

        .btn-admin {
            background-color: #667eea;
            color: white;
        }

        .btn-admin:hover {
            background-color: #5a6fd8;
        }

        /* Loading State */
        .btn.loading {
            color: transparent;
        }

        .btn.loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            animation: spin 1s ease infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Alert Messages */
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            transition: opacity 0.3s ease;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Error Messages */
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .header h2 {
                font-size: 24px;
            }
            
            .btn {
                padding: 12px;
                font-size: 14px;
            }
            
            .form-control {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome Back</h2>
            <p>Please login to your account or create a new one</p>
        </div>
        
        <div class="alert alert-error" id="errorAlert" style="display: none;"></div>
        <div class="alert alert-success" id="successAlert" style="display: none;"></div>
        
        <form id="loginForm" action="login_backend.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       placeholder="Enter your username" 
                       required>
                <div class="error-message" id="username-error">Username minimal 3 karakter</div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Enter your password" 
                       autocomplete="current-password" 
                       required>
                <div class="error-message" id="password-error">Password minimal 6 karakter</div>
            </div>
            
            <button type="submit" class="btn btn-login" id="loginBtn">Login</button>
            <button type="button" class="btn btn-register" onclick="window.location.href='regist.php'">Register</button>
            <button type="button" class="btn btn-admin" onclick="window.location.href='login1.php'">Admin</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const errorAlert = document.getElementById('errorAlert');
            const successAlert = document.getElementById('successAlert');

            // Form validation
            function validateForm() {
                const username = usernameField.value.trim();
                const password = passwordField.value;
                let isValid = true;

                // Reset previous error states
                clearErrors();

                // Username validation
                if (username.length < 3) {
                    showError('username', 'Username minimal 3 karakter');
                    isValid = false;
                }

                // Password validation
                if (password.length < 6) {
                    showError('password', 'Password minimal 6 karakter');
                    isValid = false;
                }

                return isValid;
            }

            // Show error message
            function showError(fieldName, message) {
                const field = document.getElementById(fieldName);
                const errorDiv = document.getElementById(fieldName + '-error');
                
                field.classList.add('error');
                field.classList.remove('success');
                if (errorDiv) {
                    errorDiv.textContent = message;
                    errorDiv.classList.add('show');
                }
            }

            // Clear all errors
            function clearErrors() {
                const fields = [usernameField, passwordField];
                fields.forEach(field => {
                    field.classList.remove('error', 'success');
                    const errorDiv = document.getElementById(field.id + '-error');
                    if (errorDiv) {
                        errorDiv.classList.remove('show');
                    }
                });
                errorAlert.style.display = 'none';
                successAlert.style.display = 'none';
            }

            // Add loading state to button
            function setLoadingState(isLoading) {
                if (isLoading) {
                    loginBtn.classList.add('loading');
                    loginBtn.disabled = true;
                } else {
                    loginBtn.classList.remove('loading');
                    loginBtn.disabled = false;
                }
            }

            // Show alert
            function showAlert(type, message) {
                const alert = type === 'error' ? errorAlert : successAlert;
                alert.textContent = message;
                alert.style.display = 'block';
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                        alert.style.opacity = '1';
                    }, 300);
                }, 5000);
            }

            // Form submit handler with AJAX
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateForm()) {
                    return false;
                }
                
                setLoadingState(true);
                
                const formData = new FormData(loginForm);
                
                fetch('login_backend.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    setLoadingState(false);
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    setLoadingState(false);
                    showAlert('error', 'Terjadi kesalahan: ' + error.message);
                });
            });

            // Real-time validation
            usernameField.addEventListener('input', function() {
                const username = this.value.trim();
                const errorDiv = document.getElementById('username-error');
                
                if (username.length >= 3) {
                    this.classList.remove('error');
                    this.classList.add('success');
                    if (errorDiv) {
                        errorDiv.classList.remove('show');
                    }
                } else if (username.length > 0) {
                    this.classList.add('error');
                    this.classList.remove('success');
                    if (errorDiv) {
                        errorDiv.classList.add('show');
                    }
                } else {
                    this.classList.remove('error', 'success');
                    if (errorDiv) {
                        errorDiv.classList.remove('show');
                    }
                }
            });

            passwordField.addEventListener('input', function() {
                const password = this.value;
                const errorDiv = document.getElementById('password-error');
                
                if (password.length >= 6) {
                    this.classList.remove('error');
                    this.classList.add('success');
                    if (errorDiv) {
                        errorDiv.classList.remove('show');
                    }
                } else if (password.length > 0) {
                    this.classList.add('error');
                    this.classList.remove('success');
                    if (errorDiv) {
                        errorDiv.classList.add('show');
                    }
                } else {
                    this.classList.remove('error', 'success');
                    if (errorDiv) {
                        errorDiv.classList.remove('show');
                    }
                }
            });

            // Focus on first empty field
            if (!usernameField.value) {
                usernameField.focus();
            } else if (!passwordField.value) {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>
