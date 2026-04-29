<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$company_id = $_SESSION['company_id'];
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense_date = sanitize_input($_POST['expense_date']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $amount = floatval($_POST['amount']);
    
    // Validate required fields
    if (empty($expense_date) || empty($category) || empty($amount)) {
        $error = "Please fill all required fields";
    } elseif ($amount <= 0) {
        $error = "Please enter a valid amount";
    } else {
        // Insert expense
        $insert_stmt = $conn->prepare("INSERT INTO expenses (company_id, expense_date, category, description, amount) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("isssd", $company_id, $expense_date, $category, $description, $amount);
        
        if ($insert_stmt->execute()) {
            $success = "Expense added successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error = "Failed to add expense: " . $conn->error;
        }
    }
}

// Get recent expenses for display
$recent_expenses_stmt = $conn->prepare("SELECT * FROM expenses WHERE company_id = ? ORDER BY expense_date DESC, created_at DESC LIMIT 10");
$recent_expenses_stmt->bind_param("i", $company_id);
$recent_expenses_stmt->execute();
$recent_expenses = $recent_expenses_stmt->get_result();

// Get expense statistics for current month
$current_month = date('n');
$current_year = date('Y');
$monthly_stats_stmt = $conn->prepare("SELECT 
    COUNT(*) as count, 
    COALESCE(SUM(amount), 0) as total,
    category 
    FROM expenses 
    WHERE company_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?
    GROUP BY category 
    ORDER BY total DESC");
$monthly_stats_stmt->bind_param("iii", $company_id, $current_month, $current_year);
$monthly_stats_stmt->execute();
$monthly_stats = $monthly_stats_stmt->get_result();

// Get yearly total for income tax context
$yearly_total_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as yearly_total FROM expenses WHERE company_id = ? AND YEAR(expense_date) = ?");
$yearly_total_stmt->bind_param("ii", $company_id, $current_year);
$yearly_total_stmt->execute();
$yearly_total = $yearly_total_stmt->get_result()->fetch_assoc()['yearly_total'];

// Expense categories
$categories = [
    'Salary' => '💼',
    'Rent' => '🏢',
    'Utilities' => '💡',
    'Office Supplies' => '📦',
    'Marketing' => '📢',
    'Travel' => '✈️',
    'Professional Fees' => '⚖️',
    'Maintenance' => '🔧',
    'Insurance' => '🛡️',
    'Taxes' => '🧾',
    'Bank Charges' => '🏦',
    'Entertainment' => '🎯',
    'Training' => '📚',
    'Software' => '💻',
    'Equipment' => '🖥️',
    'Raw Materials' => '📦',
    'Transportation' => '🚚',
    'Communication' => '📞',
    'Other' => '📝'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Expense Management</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background: #f8f9fa;
}

/* Header Styles */
.header {
    background: linear-gradient(135deg, #ea5858ff 0%, #e15252ff 100%);
    color: white;
    padding: 40px 30px 30px;
    position: relative;
    overflow: hidden;
}

.header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    position: relative;
    z-index: 2;
}

.welcome-message h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.welcome-message p {
    opacity: 0.9;
    font-size: 1.1rem;
    font-weight: 500;
}

.user-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.user-actions a {
    color: white;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.user-actions a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.7s ease;
}

.user-actions a:hover::before {
    left: 100%;
}

.user-actions a:first-child {
    background: rgba(255, 255, 255, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.user-actions a:first-child:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 255, 255, 0.2);
}

.logout-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
}

/* Navigation */
.main-nav {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    padding: 0 30px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 0;
}

.nav-menu li {
    margin: 0;
}

.nav-menu a {
    display: block;
    padding: 20px 25px;
    color: #310000ff;
    text-decoration: none;
    font-weight: 600;
    border-bottom: 3px solid transparent;
    transition: all 0.4s ease;
    position: relative;
}

.nav-menu a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #e12e2eff, #50342cff);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.nav-menu a:hover, 
.nav-menu a.active {
    color: #5c0707ff;
    
}

.nav-menu a:hover::after,
.nav-menu a.active::after {
    width: 80%;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header {
        padding: 30px 20px 20px;
    }
    
    .header-top {
        flex-direction: column;
        gap: 20px;
        align-items: flex-start;
    }
    
    .welcome-message h1 {
        font-size: 3rem;
    }
    
    .welcome-message p {
        font-size: 1rem;
    }
    
    .user-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .main-nav {
        padding: 0 15px;
        overflow-x: auto;
    }
    
    .nav-menu {
        flex-wrap: nowrap;
        min-width: max-content;
    }
    
    .nav-menu a {
        padding: 15px 20px;
        font-size: 0.9rem;
        white-space: nowrap;
    }
}

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }

        .back-link {
            color: #f39c12;
            text-decoration: none;
            font-weight: 500;
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        /* Form Styles */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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

        .form-group label.required:after {
            content: " *";
            color: #e74c3c;
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
            border-color: #f39c12;
            background: white;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .category-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .category-option:hover {
            border-color: #f39c12;
            background: #fffaf0;
        }

        .category-option.selected {
            border-color: #f39c12;
            background: #fef5e7;
            color: #f39c12;
        }

        .category-icon {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .category-name {
            font-size: 12px;
            font-weight: 500;
        }

        /* Buttons */
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        /* Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Sidebar Styles */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .stats-card, .recent-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stats-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .stats-value {
            font-size: 24px;
            font-weight: 700;
            color: #f39c12;
            margin-bottom: 5px;
        }

        .stats-label {
            font-size: 12px;
            color: #7f8c8d;
        }

        /* Category Breakdown */
        .category-breakdown {
            margin-top: 20px;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-amount {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Recent Expenses */
        .recent-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-details {
            flex: 1;
        }

        .recent-category {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 3px;
        }

        .recent-desc {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }

        .recent-date {
            font-size: 11px;
            color: #95a5a6;
        }

        .recent-amount {
            font-weight: 600;
            color: #e74c3c;
            text-align: right;
        }

        /* Quick Amount Buttons */
        .quick-amounts {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .amount-btn {
            padding: 6px 12px;
            border: 1px solid #f39c12;
            background: white;
            color: #f39c12;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: 500;
        }

        .amount-btn:hover {
            background: #f39c12;
            color: white;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-menu a {
                padding: 10px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="welcome-message">
                <h1>Taxxpert - Expense Management</h1>
                <p>Track business expenses for Income Tax calculation</p>
            </div>
            <div class="user-actions">
                <a href="profile.php">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="main-nav">
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="purchase_invoice.php">Purchase Invoices</a></li>
            <li><a href="sales_invoice.php">Sales Invoices</a></li>
            <li><a href="expenses.php" class="active">Expenses</a></li>
            <li><a href="gst_summary.php">GST Summary</a></li>
            <li><a href="income_tax_summary.php">Income Tax</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="notifications.php">Notifications</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Add Business Expense</h1>
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-layout">
            <!-- Main Form -->
            <div class="form-container">
                <form method="POST" action="" id="expenseForm">
                    <div class="form-section">
                        <h3 class="section-title">
                            <span>📅</span>
                            Expense Details
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Expense Date</label>
                                <input type="date" name="expense_date" class="form-control" value="<?php echo isset($_POST['expense_date']) ? htmlspecialchars($_POST['expense_date']) : date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="required">Amount (₹)</label>
                                <input type="number" name="amount" id="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>" required>
                                
                                <!-- Quick Amount Buttons -->
                                <div class="quick-amounts">
                                    <button type="button" class="amount-btn" onclick="setAmount(1000)">₹1,000</button>
                                    <button type="button" class="amount-btn" onclick="setAmount(2500)">₹2,500</button>
                                    <button type="button" class="amount-btn" onclick="setAmount(5000)">₹5,000</button>
                                    <button type="button" class="amount-btn" onclick="setAmount(10000)">₹10,000</button>
                                    <button type="button" class="amount-btn" onclick="setAmount(25000)">₹25,000</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="required">Category</label>
                            <input type="hidden" name="category" id="selected_category" value="<?php echo isset($_POST['category']) ? htmlspecialchars($_POST['category']) : ''; ?>" required>
                            
                            <div class="category-grid" id="categoryGrid">
                                <?php foreach($categories as $cat_name => $icon): ?>
                                    <div class="category-option" data-category="<?php echo htmlspecialchars($cat_name); ?>">
                                        <div class="category-icon"><?php echo $icon; ?></div>
                                        <div class="category-name"><?php echo htmlspecialchars($cat_name); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" placeholder="Enter expense description (optional)"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">Reset Form</button>
                        <button type="submit" class="btn btn-primary">Save Expense</button>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Expense Statistics -->
                <div class="stats-card">
                    <div class="stats-header">
                        <h3 class="stats-title">Expense Summary</h3>
                        <span style="font-size: 12px; color: #7f8c8d;"><?php echo date('F Y'); ?></span>
                    </div>
                    
                    <div class="stats-value">₹<?php echo number_format($yearly_total, 2); ?></div>
                    <div class="stats-label">Total Expenses This Year</div>

                    <!-- Category Breakdown -->
                    <div class="category-breakdown">
                        <h4 style="margin: 20px 0 10px 0; font-size: 14px; color: #2c3e50;">Monthly Breakdown</h4>
                        <?php 
                        $monthly_total = 0;
                        if ($monthly_stats->num_rows > 0): 
                            while($stat = $monthly_stats->fetch_assoc()): 
                                $monthly_total += $stat['total'];
                        ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <span style="font-size: 14px;"><?php echo $categories[$stat['category']] ?? '📝'; ?></span>
                                    <span style="font-size: 12px;"><?php echo htmlspecialchars($stat['category']); ?></span>
                                </div>
                                <div class="category-amount">₹<?php echo number_format($stat['total'], 2); ?></div>
                            </div>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #7f8c8d; padding: 10px; font-size: 12px;">No expenses this month</p>
                        <?php endif; ?>
                        
                        <?php if ($monthly_stats->num_rows > 0): ?>
                            <div class="category-item" style="border-top: 2px solid #f39c12; padding-top: 15px; margin-top: 10px;">
                                <div style="font-weight: 600; color: #2c3e50;">Monthly Total</div>
                                <div style="font-weight: 700; color: #f39c12;">₹<?php echo number_format($monthly_total, 2); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Expenses -->
                <div class="recent-card">
                    <h3 class="stats-title">Recent Expenses</h3>
                    <div class="recent-list">
                        <?php if ($recent_expenses->num_rows > 0): ?>
                            <?php while($expense = $recent_expenses->fetch_assoc()): ?>
                                <div class="recent-item">
                                    <div class="recent-details">
                                        <div class="recent-category">
                                            <?php echo $categories[$expense['category']] ?? '📝'; ?> 
                                            <?php echo htmlspecialchars($expense['category']); ?>
                                        </div>
                                        <?php if (!empty($expense['description'])): ?>
                                            <div class="recent-desc"><?php echo htmlspecialchars($expense['description']); ?></div>
                                        <?php endif; ?>
                                        <div class="recent-date"><?php echo $expense['expense_date']; ?></div>
                                    </div>
                                    <div class="recent-amount">₹<?php echo number_format($expense['amount'], 2); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #7f8c8d; padding: 20px; font-size: 12px;">No expenses recorded yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Category selection
        function setupCategorySelection() {
            const categoryOptions = document.querySelectorAll('.category-option');
            const selectedCategoryInput = document.getElementById('selected_category');
            
            categoryOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    categoryOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update hidden input value
                    selectedCategoryInput.value = this.getAttribute('data-category');
                });
            });
            
            // Set initial selection if category exists in POST data
            const initialCategory = selectedCategoryInput.value;
            if (initialCategory) {
                categoryOptions.forEach(option => {
                    if (option.getAttribute('data-category') === initialCategory) {
                        option.classList.add('selected');
                    }
                });
            }
        }

        // Quick amount buttons
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
        }

        // Form validation
        document.getElementById('expenseForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const category = document.getElementById('selected_category').value;
            
            if (amount <= 0) {
                alert('Please enter a valid amount');
                e.preventDefault();
                return false;
            }
            
            if (!category) {
                alert('Please select a category');
                e.preventDefault();
                return false;
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupCategorySelection();
            
            // Auto-focus on date field
            document.querySelector('input[name="expense_date"]').focus();
        });

        // Add some animations
        const style = document.createElement('style');
        style.textContent = `
            .category-option, .recent-item, .category-item {
                transition: all 0.3s ease;
            }
            
            .category-option:hover {
                transform: translateY(-2px);
            }
            
            .fade-in {
                animation: fadeInUp 0.5s ease-out;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);

        // Add fade-in animation to elements
        document.querySelectorAll('.form-container, .stats-card, .recent-card').forEach(el => {
            el.classList.add('fade-in');
        });
    </script>
</body>
</html>