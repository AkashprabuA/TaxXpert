<?php
require_once 'config.php';

// Redirect if already logged in as admin
if (is_admin_logged_in()) {
    redirect('admin_panel.php');
}

// Handle admin login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Check admin credentials
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name'] = $admin['name'];
                
                redirect('admin_panel.php');
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Admin account not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: 
                linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 50%, #000000 100%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.1) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }

        .login-container {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1),
                inset 0 -1px 0 rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(135deg, 
                    rgba(255, 255, 255, 0.1) 0%, 
                    transparent 20%, 
                    transparent 80%, 
                    rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
            z-index: -1;
        }

        .login-header {
            background: linear-gradient(135deg, 
                rgba(46, 49, 146, 0.8) 0%, 
                rgba(27, 20, 100, 0.9) 50%, 
                rgba(14, 11, 56, 0.8) 100%);
            color: #00ffcc;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 255, 204, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 204, 0.1), 
                transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .login-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(0, 255, 204, 0.5);
            letter-spacing: 2px;
        }

        .login-header p {
            opacity: 0.8;
            font-size: 14px;
            color: #88ffdd;
            letter-spacing: 1px;
        }

        .login-form {
            padding: 35px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #00ffcc;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(0, 255, 204, 0.3);
            border-radius: 8px;
            font-size: 14px;
            color: #00ffcc;
            font-family: 'Courier New', monospace;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .form-control::placeholder {
            color: rgba(0, 255, 204, 0.5);
        }

        .form-control:focus {
            outline: none;
            border-color: #00ffcc;
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 
                0 0 0 3px rgba(0, 255, 204, 0.1),
                inset 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, 
                rgba(0, 255, 204, 0.9) 0%, 
                rgba(0, 200, 160, 0.9) 100%);
            color: #001a14;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Courier New', monospace;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.4), 
                transparent);
            transition: left 0.7s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 5px 20px rgba(0, 255, 204, 0.4),
                0 0 30px rgba(0, 255, 204, 0.2);
        }

        .btn:hover::before {
            left: 100%;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.1);
            color: #ff4444;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 0, 0, 0.3);
            font-size: 14px;
            text-align: center;
            text-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
            font-family: 'Courier New', monospace;
        }

        .login-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 255, 204, 0.2);
        }

        .login-links a {
            color: #00ffcc;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .login-links a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: #00ffcc;
            transition: width 0.3s ease;
        }

        .login-links a:hover {
            color: #88ffdd;
            text-shadow: 0 0 10px rgba(0, 255, 204, 0.5);
        }

        .login-links a:hover::after {
            width: 100%;
        }

        /* Matrix-like background effect */
        .matrix-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: 0.1;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                max-width: calc(100% - 20px);
            }
            
            .login-form {
                padding: 25px 20px;
            }
            
            .login-header h1 {
                font-size: 28px;
            }
        }

        /* Terminal-style cursor effect */
        .form-control:focus {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { border-color: #00ffcc; }
            50% { border-color: transparent; }
        }
    </style>
</head>
<body>
    <div class="matrix-bg"></div>
    
    <div class="login-container">
        <div class="login-header">
            <h1>TAXXPERT</h1>
            <p>SYSTEM ADMIN ACCESS</p>
        </div>

        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    ⚠️ ACCESS DENIED: <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">ADMIN IDENTIFIER</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="ENTER ADMIN EMAIL"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">ENCRYPTION KEY</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="ENTER SECURITY KEY" 
                           required>
                </div>

                <button type="submit" class="btn">INITIATE SYSTEM ACCESS</button>
            </form>

            <div class="login-links">
                <a href="login.php">← RETURN TO USER PORTAL</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on email input
            document.getElementById('email').focus();
            
            // Add matrix background effect
            const matrixBg = document.querySelector('.matrix-bg');
            const chars = '01';
            let matrixText = '';
            
            for (let i = 0; i < 200; i++) {
                matrixText += chars[Math.floor(Math.random() * chars.length)];
            }
            
            matrixBg.textContent = matrixText;
            matrixBg.style.fontFamily = 'Courier New, monospace';
            matrixBg.style.fontSize = '12px';
            matrixBg.style.color = '#00ffcc';
            matrixBg.style.opacity = '0.05';
            matrixBg.style.padding = '20px';
            matrixBg.style.wordBreak = 'break-all';
        });
    </script>
</body>
</html>