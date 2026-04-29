<?php
require_once 'config.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $gstin = sanitize_input($_POST['gstin']);
    $pan = sanitize_input($_POST['pan']);
    $place_of_supply = sanitize_input($_POST['place_of_supply']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($gstin) || empty($pan) || empty($place_of_supply) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if company already exists
        $check_stmt = $conn->prepare("SELECT id FROM companies WHERE gstin = ? OR email = ? OR name = ?");
        $check_stmt->bind_param("sss", $gstin, $email, $name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Company already registered with this GSTIN, email, or name";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new company
            $insert_stmt = $conn->prepare("INSERT INTO companies (name, gstin, pan, place_of_supply, email, password) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $name, $gstin, $pan, $place_of_supply, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success = "Company registered successfully! You can now login.";
                // Clear form
                $_POST = array();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Company Registration</title>
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

        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }

        .register-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 25px 20px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .register-form {
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
            background: linear-gradient(135deg, #27ae60, #2ecc71);
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
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .btn-secondary:hover {
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.3);
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

        .form-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }

        .form-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .form-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .input-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        @media (max-width: 480px) {
            .register-container {
                margin: 10px;
            }
            
            .register-form {
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Taxxpert</h1>
            <p>Register Your Company</p>
        </div>

        <div class="register-form">
            <?php if ($error): ?>
                <div class="error-message">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Company Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           placeholder="Enter company name"
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gstin">GSTIN *</label>
                        <input type="text" 
                               id="gstin" 
                               name="gstin" 
                               class="form-control" 
                               placeholder="e.g., 07AABCU9603R1ZM"
                               value="<?php echo isset($_POST['gstin']) ? htmlspecialchars($_POST['gstin']) : ''; ?>"
                               required>
                        <span class="input-hint">15-character GSTIN number</span>
                    </div>

                    <div class="form-group">
                        <label for="pan">PAN *</label>
                        <input type="text" 
                               id="pan" 
                               name="pan" 
                               class="form-control" 
                               placeholder="e.g., AABCU9603R"
                               value="<?php echo isset($_POST['pan']) ? htmlspecialchars($_POST['pan']) : ''; ?>"
                               required>
                        <span class="input-hint">10-character PAN number</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="place_of_supply">Place of Supply *</label>
                    <select id="place_of_supply" name="place_of_supply" class="form-control" required>
                        <option value="">Select State</option>
                        <option value="Delhi" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Delhi') ? 'selected' : ''; ?>>Delhi</option>
                        <option value="Maharashtra" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Maharashtra') ? 'selected' : ''; ?>>Maharashtra</option>
                        <option value="Karnataka" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Karnataka') ? 'selected' : ''; ?>>Karnataka</option>
                        <option value="Tamil Nadu" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Tamil Nadu') ? 'selected' : ''; ?>>Tamil Nadu</option>
                        <option value="Uttar Pradesh" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Uttar Pradesh') ? 'selected' : ''; ?>>Uttar Pradesh</option>
                        <option value="Gujarat" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Gujarat') ? 'selected' : ''; ?>>Gujarat</option>
                        <option value="West Bengal" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'West Bengal') ? 'selected' : ''; ?>>West Bengal</option>
                        <option value="Rajasthan" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Rajasthan') ? 'selected' : ''; ?>>Rajasthan</option>
                        <option value="Punjab" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Punjab') ? 'selected' : ''; ?>>Punjab</option>
                        <option value="Haryana" <?php echo (isset($_POST['place_of_supply']) && $_POST['place_of_supply'] == 'Haryana') ? 'selected' : ''; ?>>Haryana</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Enter company email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter password"
                               required>
                        <span class="input-hint">Min. 6 characters</span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               placeholder="Confirm password"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn">Register Company</button>
            </form>

            <div class="form-links">
                <a href="login.php">Already have an account? Login</a>
                <a href="admin_login.php">Admin Login</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Real-time password match validation
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#e74c3c';
                } else {
                    confirmPassword.style.borderColor = '#27ae60';
                }
            }
            
            password.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);
            
            // Auto-focus on first input
            document.getElementById('name').focus();
        });
    </script>
</body>
</html>