<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$company_id = $_SESSION['company_id'];

// Get financial year from query string or use current
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
if ($selected_year < 2020 || $selected_year > 2030) {
    $selected_year = date('Y');
}

// Calculate Income Tax summary for the selected financial year
function calculateIncomeTaxSummary($conn, $company_id, $financial_year) {
    // Get total revenue from sales invoices (excluding GST)
    $revenue_stmt = $conn->prepare("SELECT 
        COALESCE(SUM(taxable_value), 0) as total_revenue,
        COUNT(*) as invoice_count
        FROM sales_invoices 
        WHERE company_id = ? AND YEAR(invoice_date) = ?");
    $revenue_stmt->bind_param("ii", $company_id, $financial_year);
    $revenue_stmt->execute();
    $revenue_data = $revenue_stmt->get_result()->fetch_assoc();

    // Get total expenses
    $expenses_stmt = $conn->prepare("SELECT 
        COALESCE(SUM(amount), 0) as total_expenses,
        COUNT(*) as expense_count,
        category,
        SUM(amount) as category_amount
        FROM expenses 
        WHERE company_id = ? AND YEAR(expense_date) = ?
        GROUP BY category
        ORDER BY category_amount DESC");
    $expenses_stmt->bind_param("ii", $company_id, $financial_year);
    $expenses_stmt->execute();
    $expenses_data = $expenses_stmt->get_result();

    // Calculate category-wise expenses
    $category_expenses = [];
    $total_expenses = 0;
    $expense_count = 0;
    
    while($row = $expenses_data->fetch_assoc()) {
        $category_expenses[] = [
            'category' => $row['category'],
            'amount' => $row['category_amount']
        ];
        $total_expenses += $row['category_amount'];
        $expense_count += $row['expense_count'];
    }

    // Calculate profit and tax
    $total_revenue = $revenue_data['total_revenue'];
    $profit = $total_revenue - $total_expenses;
    
    // Apply tax rates (simplified - assuming 25% for companies)
    $tax_rate = 25.00; // 25% for companies
    $income_tax = max(0, $profit * ($tax_rate / 100));
    
    // Calculate effective tax rate
    $effective_tax_rate = $total_revenue > 0 ? ($income_tax / $total_revenue) * 100 : 0;

    return [
        'financial_year' => $financial_year,
        'revenue' => [
            'total' => $total_revenue,
            'invoice_count' => $revenue_data['invoice_count']
        ],
        'expenses' => [
            'total' => $total_expenses,
            'expense_count' => $expense_count,
            'categories' => $category_expenses
        ],
        'profit' => $profit,
        'tax' => [
            'rate' => $tax_rate,
            'amount' => $income_tax,
            'effective_rate' => $effective_tax_rate
        ]
    ];
}

// Calculate income tax summary
$tax_summary = calculateIncomeTaxSummary($conn, $company_id, $selected_year);

// Get available financial years
$years = [];
for ($y = date('Y'); $y >= 2020; $y--) {
    $years[] = $y;
}

// Expense categories with icons
$category_icons = [
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
    <title>Taxxpert - Income Tax Summary</title>
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
    background: linear-gradient(135deg, #ea66c2ff 0%, #a24b82ff 100%);
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
    color: #2c3e50;
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
    background: linear-gradient(90deg, #db349bff, #2c3e50);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.nav-menu a:hover, 
.nav-menu a.active {
    color: #db34acff;
    background: rgba(52, 152, 219, 0.05);
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
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }

        .back-link {
            color: #1abc9c;
            text-decoration: none;
            font-weight: 500;
        }

        /* Year Selector */
        .year-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .selector-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-control {
            padding: 10px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1abc9c;
            background: white;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #1abc9c;
            color: white;
        }

        .btn-primary:hover {
            background: #16a085;
            transform: translateY(-2px);
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #1abc9c;
        }

        .summary-card.revenue {
            border-left-color: #27ae60;
        }

        .summary-card.expenses {
            border-left-color: #e74c3c;
        }

        .summary-card.profit {
            border-left-color: #3498db;
        }

        .summary-card.tax {
            border-left-color: #9b59b6;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .card-icon {
            font-size: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .amount-display {
            text-align: center;
            margin: 20px 0;
        }

        .amount-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .amount-label {
            font-size: 14px;
            color: #7f8c8d;
        }

        .amount-revenue { color: #27ae60; }
        .amount-expenses { color: #e74c3c; }
        .amount-profit { color: #3498db; }
        .amount-tax { color: #9b59b6; }

        .card-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }

        .detail-label {
            color: #7f8c8d;
        }

        .detail-value {
            font-weight: 600;
        }

        /* Profit Calculation */
        .calculation-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .calculation-steps {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .calculation-step {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #1abc9c;
        }

        .step-number {
            background: #1abc9c;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .step-details {
            flex: 1;
        }

        .step-description {
            font-weight: 500;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .step-formula {
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0;
        }

        .step-amount {
            font-size: 24px;
            font-weight: 700;
        }

        .step-revenue { color: #27ae60; }
        .step-expenses { color: #e74c3c; }
        .step-profit { color: #3498db; }
        .step-tax { color: #9b59b6; }

        /* Expense Breakdown */
        .expense-breakdown {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        }

        .breakdown-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breakdown-icon {
            font-size: 16px;
        }

        .breakdown-category {
            font-weight: 500;
            color: #2c3e50;
        }

        .breakdown-amount {
            font-weight: 600;
            color: #e74c3c;
        }

        /* Tax Summary */
        .tax-summary {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .tax-amount {
            font-size: 42px;
            font-weight: 700;
            margin: 20px 0;
            color: #9b59b6;
        }

        .tax-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .tax-detail {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .tax-detail-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .tax-detail-label {
            font-size: 12px;
            color: #7f8c8d;
        }

        /* Compliance Notice */
        .compliance-notice {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
        }

        .notice-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .breakdown-grid {
                grid-template-columns: 1fr;
            }
            
            .tax-details {
                grid-template-columns: 1fr;
            }
            
            .calculation-step {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .selector-form {
                flex-direction: column;
                align-items: stretch;
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
                <h1>Taxxpert - Income Tax Summary</h1>
                <p>Profit Calculation & Tax Liability Report</p>
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
            <li><a href="expenses.php">Expenses</a></li>
            <li><a href="gst_summary.php">GST Summary</a></li>
            <li><a href="income_tax_summary.php" class="active">Income Tax</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="notifications.php">Notifications</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Income Tax Calculation</h1>
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>

        <!-- Year Selector -->
        <div class="year-selector">
            <form method="GET" action="" class="selector-form">
                <div class="form-group">
                    <label>Financial Year</label>
                    <select name="year" class="form-control">
                        <?php foreach($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($year == $selected_year) ? 'selected' : ''; ?>>
                                FY <?php echo $year; ?>-<?php echo substr($year + 1, 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Calculate Tax</button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <!-- Revenue Card -->
            <div class="summary-card revenue">
                <div class="card-header">
                    <div class="card-icon">📈</div>
                    <div class="card-title">Total Revenue</div>
                </div>
                <div class="amount-display">
                    <div class="amount-value amount-revenue">₹<?php echo number_format($tax_summary['revenue']['total'], 2); ?></div>
                    <div class="amount-label">From <?php echo $tax_summary['revenue']['invoice_count']; ?> Sales Invoices</div>
                </div>
                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Financial Year:</span>
                        <span class="detail-value">FY <?php echo $tax_summary['financial_year']; ?>-<?php echo substr($tax_summary['financial_year'] + 1, 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Revenue Source:</span>
                        <span class="detail-value">Product Sales</span>
                    </div>
                </div>
            </div>

            <!-- Expenses Card -->
            <div class="summary-card expenses">
                <div class="card-header">
                    <div class="card-icon">💰</div>
                    <div class="card-title">Total Expenses</div>
                </div>
                <div class="amount-display">
                    <div class="amount-value amount-expenses">₹<?php echo number_format($tax_summary['expenses']['total'], 2); ?></div>
                    <div class="amount-label">From <?php echo $tax_summary['expenses']['expense_count']; ?> Expense Entries</div>
                </div>
                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Deductible:</span>
                        <span class="detail-value">100% Business Expenses</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Categories:</span>
                        <span class="detail-value"><?php echo count($tax_summary['expenses']['categories']); ?> Categories</span>
                    </div>
                </div>
            </div>

            <!-- Profit Card -->
            <div class="summary-card profit">
                <div class="card-header">
                    <div class="card-icon">📊</div>
                    <div class="card-title">Net Profit</div>
                </div>
                <div class="amount-display">
                    <div class="amount-value amount-profit">₹<?php echo number_format($tax_summary['profit'], 2); ?></div>
                    <div class="amount-label">
                        <?php if ($tax_summary['profit'] > 0): ?>
                            ✅ Profitable Business
                        <?php elseif ($tax_summary['profit'] < 0): ?>
                            📉 Business Loss
                        <?php else: ?>
                            ⚖️ Break Even
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Calculation:</span>
                        <span class="detail-value">Revenue - Expenses</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Profit Margin:</span>
                        <span class="detail-value">
                            <?php echo $tax_summary['revenue']['total'] > 0 ? number_format(($tax_summary['profit'] / $tax_summary['revenue']['total']) * 100, 2) : '0.00'; ?>%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tax Card -->
            <div class="summary-card tax">
                <div class="card-header">
                    <div class="card-icon">🧾</div>
                    <div class="card-title">Income Tax</div>
                </div>
                <div class="amount-display">
                    <div class="amount-value amount-tax">₹<?php echo number_format($tax_summary['tax']['amount'], 2); ?></div>
                    <div class="amount-label">At <?php echo $tax_summary['tax']['rate']; ?>% Tax Rate</div>
                </div>
                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Tax Rate:</span>
                        <span class="detail-value"><?php echo $tax_summary['tax']['rate']; ?>% (Company Rate)</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Effective Rate:</span>
                        <span class="detail-value"><?php echo number_format($tax_summary['tax']['effective_rate'], 2); ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profit Calculation Steps -->
        <div class="calculation-section">
            <h3 class="section-title">
                <span>🧮</span>
                Profit Calculation Steps
            </h3>
            
            <div class="calculation-steps">
                <!-- Step 1: Revenue -->
                <div class="calculation-step">
                    <div class="step-number">1</div>
                    <div class="step-details">
                        <div class="step-description">Total Revenue from Sales</div>
                        <div class="step-formula">Revenue = Sum of all Sales Invoice Values (excluding GST)</div>
                        <div class="step-amount step-revenue">₹<?php echo number_format($tax_summary['revenue']['total'], 2); ?></div>
                    </div>
                </div>

                <!-- Step 2: Expenses -->
                <div class="calculation-step">
                    <div class="step-number">2</div>
                    <div class="step-details">
                        <div class="step-description">Total Business Expenses</div>
                        <div class="step-formula">Expenses = Sum of all deductible business expenses</div>
                        <div class="step-amount step-expenses">₹<?php echo number_format($tax_summary['expenses']['total'], 2); ?></div>
                    </div>
                </div>

                <!-- Step 3: Profit -->
                <div class="calculation-step">
                    <div class="step-number">3</div>
                    <div class="step-details">
                        <div class="step-description">Net Profit Calculation</div>
                        <div class="step-formula">Profit = Revenue - Expenses</div>
                        <div class="step-amount step-profit">₹<?php echo number_format($tax_summary['profit'], 2); ?></div>
                    </div>
                </div>

                <!-- Step 4: Tax -->
                <div class="calculation-step">
                    <div class="step-number">4</div>
                    <div class="step-details">
                        <div class="step-description">Income Tax Calculation</div>
                        <div class="step-formula">Tax = Profit × <?php echo $tax_summary['tax']['rate']; ?>%</div>
                        <div class="step-amount step-tax">₹<?php echo number_format($tax_summary['tax']['amount'], 2); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <?php if (!empty($tax_summary['expenses']['categories'])): ?>
        <div class="expense-breakdown">
            <h3 class="section-title">
                <span>📋</span>
                Expense Category Breakdown
            </h3>
            
            <div class="breakdown-grid">
                <?php foreach($tax_summary['expenses']['categories'] as $category): ?>
                    <div class="breakdown-item">
                        <div class="breakdown-info">
                            <div class="breakdown-icon"><?php echo $category_icons[$category['category']] ?? '📝'; ?></div>
                            <div class="breakdown-category"><?php echo htmlspecialchars($category['category']); ?></div>
                        </div>
                        <div class="breakdown-amount">₹<?php echo number_format($category['amount'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tax Summary -->
        <div class="tax-summary">
            <div class="section-title">Income Tax Liability Summary</div>
            
            <?php if ($tax_summary['profit'] > 0): ?>
                <div class="tax-amount">
                    ₹<?php echo number_format($tax_summary['tax']['amount'], 2); ?> Payable
                </div>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Income Tax payable for Financial Year <?php echo $tax_summary['financial_year']; ?>-<?php echo substr($tax_summary['financial_year'] + 1, 2); ?>
                </p>
            <?php elseif ($tax_summary['profit'] < 0): ?>
                <div class="tax-amount" style="color: #e74c3c;">
                    No Tax Payable
                </div>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Business loss of ₹<?php echo number_format(abs($tax_summary['profit']), 2); ?> can be carried forward
                </p>
            <?php else: ?>
                <div class="tax-amount" style="color: #7f8c8d;">
                    No Tax Liability
                </div>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Business broke even for the financial year
                </p>
            <?php endif; ?>

            <div class="tax-details">
                <div class="tax-detail">
                    <div class="tax-detail-value"><?php echo $tax_summary['tax']['rate']; ?>%</div>
                    <div class="tax-detail-label">Applicable Tax Rate</div>
                </div>
                <div class="tax-detail">
                    <div class="tax-detail-value"><?php echo number_format($tax_summary['tax']['effective_rate'], 2); ?>%</div>
                    <div class="tax-detail-label">Effective Tax Rate</div>
                </div>
                <div class="tax-detail">
                    <div class="tax-detail-value"><?php echo $tax_summary['revenue']['invoice_count']; ?></div>
                    <div class="tax-detail-label">Sales Invoices</div>
                </div>
                <div class="tax-detail">
                    <div class="tax-detail-value"><?php echo $tax_summary['expenses']['expense_count']; ?></div>
                    <div class="tax-detail-label">Expense Entries</div>
                </div>
            </div>

            <!-- Compliance Notice -->
            <div class="compliance-notice">
                <div class="notice-title">📅 Compliance Information</div>
                <p><strong>Due Date:</strong> 30th September <?php echo $tax_summary['financial_year'] + 1; ?> (Individual/Company)</p>
                <p><strong>Filing:</strong> ITR-3 for businesses with presumptive income</p>
                <p><strong>Payment:</strong> Advance tax installments if tax exceeds ₹10,000</p>
            </div>
        </div>
    </div>

    <script>
        // Add animations and interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate summary cards
            const cards = document.querySelectorAll('.summary-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('fade-in');
            });

            // Animate calculation steps
            const steps = document.querySelectorAll('.calculation-step');
            steps.forEach((step, index) => {
                step.style.animationDelay = (index * 0.2) + 's';
                step.classList.add('fade-in');
            });

            // Add hover effects
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            .fade-in {
                animation: fadeInUp 0.6s ease-out;
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
            
            .summary-card, .calculation-step, .breakdown-item {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .summary-card:hover {
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>