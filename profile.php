<?php
session_start();

// Database configuration with error handling
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
    } else {
        // Fallback direct database connection
        $host = 'localhost';
        $dbname = 'taxxpert';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];
$message = '';
$error = '';

// Fetch company data
try {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error loading company data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $gstin = trim($_POST['gstin']);
    $pan = trim($_POST['pan']);
    $place_of_supply = trim($_POST['place_of_supply']);
    $email = trim($_POST['email']);
    
    // Basic validation
    if (empty($name) || empty($gstin) || empty($pan) || empty($place_of_supply) || empty($email)) {
        $error = "All fields are required except profile image.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Handle profile image upload
            $profile_image = $company['profile_image']; // Keep existing image by default
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/profile_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    // Check file size (2MB limit)
                    if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                        $error = "File size must be less than 2MB.";
                    } else {
                        $new_filename = 'company_' . $company_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            // Delete old profile image if exists
                            if ($company['profile_image'] && file_exists($upload_dir . $company['profile_image'])) {
                                unlink($upload_dir . $company['profile_image']);
                            }
                            $profile_image = $new_filename;
                        } else {
                            $error = "Failed to upload image. Please try again.";
                        }
                    }
                } else {
                    $error = "Invalid file type. Please upload JPG, PNG, or GIF images only.";
                }
            }
            
            if (empty($error)) {
                // Update company data
                $update_stmt = $pdo->prepare("
                    UPDATE companies 
                    SET name = ?, gstin = ?, pan = ?, place_of_supply = ?, email = ?, profile_image = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $update_stmt->execute([
                    $name,
                    $gstin,
                    $pan,
                    $place_of_supply,
                    $email,
                    $profile_image,
                    $company_id
                ]);
                
                $message = "Profile updated successfully!";
                
                // Refresh company data
                $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
                $stmt->execute([$company_id]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update session company name if changed
                $_SESSION['company_name'] = $company['name'];
            }
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get counts for dashboard stats
try {
    $purchase_count = $pdo->prepare("SELECT COUNT(*) FROM purchase_invoices WHERE company_id = ?");
    $purchase_count->execute([$company_id]);
    $purchase_total = $purchase_count->fetchColumn();

    $sales_count = $pdo->prepare("SELECT COUNT(*) FROM sales_invoices WHERE company_id = ?");
    $sales_count->execute([$company_id]);
    $sales_total = $sales_count->fetchColumn();

    $expenses_count = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE company_id = ?");
    $expenses_count->execute([$company_id]);
    $expenses_total = $expenses_count->fetchColumn();
} catch(PDOException $e) {
    // If tables don't exist yet, set defaults
    $purchase_total = 0;
    $sales_total = 0;
    $expenses_total = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - Taxxpert</title>
    <style>
        /* Your existing CSS styles here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #4a5568;
        }

        .logo span {
            color: #667eea;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            text-decoration: none;
            color: #4a5568;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover, .nav-links a.active {
            background: #667eea;
            color: white;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .profile-card, .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header h1 {
            color: #2d3748;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #718096;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #e2e8f0;
            margin: 0 auto 20px;
            display: block;
        }

        .photo-upload {
            text-align: center;
            margin: 20px 0;
        }

        .photo-upload input {
            margin: 10px 0;
        }

        .info-grid {
            display: grid;
            gap: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 150px;
        }

        .info-value {
            color: #2d3748;
            text-align: right;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-block {
            width: 100%;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Taxx<span>pert</span></div>
            <div class="nav-links">
              
                <a href="gst_summary.php">GST Summary</a>
                <a href="income_tax.php">Income Tax</a>
                <a href="profile.php" class="active">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <!-- Profile Overview Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <img src="<?php 
                        if (!empty($company['profile_image'])) {
                            echo 'uploads/profile_photos/' . htmlspecialchars($company['profile_image']);
                        } else {
                            // Generate initial-based avatar
                            $initial = strtoupper(substr($company['name'], 0, 1));
                            echo "https://via.placeholder.com/150/667eea/ffffff?text=" . urlencode($initial);
                        }
                    ?>" 
                         alt="Profile Image" class="profile-photo">
                    <h1><?php echo htmlspecialchars($company['name']); ?></h1>
                    <p>GSTIN: <?php echo htmlspecialchars($company['gstin']); ?></p>
                    <div class="status-badge status-active">Active</div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">PAN Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($company['pan']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Place of Supply:</span>
                        <span class="info-value"><?php echo htmlspecialchars($company['place_of_supply']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($company['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registered:</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($company['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated:</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($company['updated_at'])); ?></span>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $purchase_total; ?></div>
                        <div class="stat-label">Purchase Invoices</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $sales_total; ?></div>
                        <div class="stat-label">Sales Invoices</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $expenses_total; ?></div>
                        <div class="stat-label">Expenses</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">1</div>
                        <div class="stat-label">Active Company</div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="form-card">
                <div class="profile-header">
                    <h2>Edit Company Profile</h2>
                    <p>Update your company information</p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="photo-upload">
                        <label for="profile_image">Update Profile Image:</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <small>Supported formats: JPG, PNG, GIF (Max: 2MB)</small>
                    </div>

                    <div class="form-group">
                        <label for="name">Company Name *</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($company['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="gstin">GSTIN *</label>
                        <input type="text" id="gstin" name="gstin" 
                               value="<?php echo htmlspecialchars($company['gstin']); ?>" 
                               pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}" 
                               title="Please enter a valid 15-character GSTIN" required>
                        <small>Format: 07AABCU9603R1ZM (15 characters)</small>
                    </div>

                    <div class="form-group">
                        <label for="pan">PAN Number *</label>
                        <input type="text" id="pan" name="pan" 
                               value="<?php echo htmlspecialchars($company['pan']); ?>" 
                               pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" 
                               title="Please enter a valid 10-character PAN" required>
                        <small>Format: AABCU9603R (10 characters)</small>
                    </div>

                    <div class="form-group">
                        <label for="place_of_supply">Place of Supply *</label>
                        <input type="text" id="place_of_supply" name="place_of_supply" 
                               value="<?php echo htmlspecialchars($company['place_of_supply']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($company['email']); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-block">Update Profile</button>
                </form>

                <!-- Security Section -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                    <h3 style="color: #4a5568; margin-bottom: 15px;">Security</h3>
                    <a href="change_password.php" class="btn" style="background: #e53e3e;">Change Password</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const gstin = document.getElementById('gstin').value;
            const pan = document.getElementById('pan').value;
            
            // GSTIN validation (15 characters)
            const gstinRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
            if (!gstinRegex.test(gstin)) {
                alert('Please enter a valid GSTIN (15 characters in proper format)');
                e.preventDefault();
                return;
            }
            
            // PAN validation (10 characters)
            const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
            if (!panRegex.test(pan)) {
                alert('Please enter a valid PAN (10 characters in proper format)');
                e.preventDefault();
                return;
            }
        });

        // Preview profile image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (2MB limit)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-photo').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Add real-time validation indicators
        document.getElementById('gstin').addEventListener('input', function() {
            const gstinRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
            this.style.borderColor = gstinRegex.test(this.value) ? '#48bb78' : '#e53e3e';
        });

        document.getElementById('pan').addEventListener('input', function() {
            const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
            this.style.borderColor = panRegex.test(this.value) ? '#48bb78' : '#e53e3e';
        });
    </script>
</body>
</html>