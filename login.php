<?php
require_once 'config.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = sanitize_input($_POST['login_id']);
    $password = $_POST['password'];
    
    if (empty($login_id) || empty($password)) {
        $error = "Please enter both login ID and password";
    } else {
        // Check if login_id is GSTIN, email, or company name
        $stmt = $conn->prepare("SELECT * FROM companies WHERE gstin = ? OR email = ? OR name = ?");
        $stmt->bind_param("sss", $login_id, $login_id, $login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $company = $result->fetch_assoc();
            
            if (password_verify($password, $company['password'])) {
                // Login successful
                $_SESSION['company_id'] = $company['id'];
                $_SESSION['company_name'] = $company['name'];
                $_SESSION['company_gstin'] = $company['gstin'];
                $_SESSION['company_email'] = $company['email'];
                
                // Create demo notifications for new login
                createDemoNotifications($company['id']);
                
                redirect('dashboard.php');
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Company not found. Please check your GSTIN, email, or company name";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Company Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }

        .login-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .login-form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
            font-size: 14px;
            text-align: center;
        }

        .success-message {
            background: #efe;
            color: #363;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
            font-size: 14px;
            text-align: center;
        }

        .login-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }

        .login-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .login-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .input-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Taxxpert</h1>
            <p>GST & Income Tax Management System</p>
        </div>

        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                <div class="success-message">
                    ✅ Registration successful! Please login with your credentials.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                <div class="success-message">
                    ✅ Logged out successfully!
                </div>
            <?php endif; ?>

            

            <form method="POST" action="">
                <div class="form-group">
                    <label for="login_id">GSTIN / Email / Company Name</label>
                    <input type="text" 
                           id="login_id" 
                           name="login_id" 
                           class="form-control" 
                           placeholder="Enter GSTIN, email or company name"
                           value="<?php echo isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : ''; ?>"
                           required>
                    <span class="input-hint">You can login with any of these: GSTIN, registered email, or company name</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter your password" 
                           required>
                </div>

                <button type="submit" class="btn">Login to Dashboard</button>
            </form>

            <div class="login-links">
                <a href="register.php">Create New Company Account</a>
                <a href="admin_login.php">Admin Login</a>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            const loginInput = document.getElementById('login_id');
            const passwordInput = document.getElementById('password');
            
            // Clear error when user starts typing
            [loginInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    const errorDiv = document.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                });
            });
            
            // Auto-focus on login input
            loginInput.focus();
        });
    </script>
</body>
</html>