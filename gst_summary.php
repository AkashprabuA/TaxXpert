<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$company_id = $_SESSION['company_id'];

// Get period from query string or use current month
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate period
if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = date('n');
}
if ($selected_year < 2020 || $selected_year > 2030) {
    $selected_year = date('Y');
}

// Calculate GST summary for the selected period
function calculateGSTSummary($conn, $company_id, $month, $year) {
    // Get purchase invoices (Input GST)
    $purchase_stmt = $conn->prepare("SELECT 
        COALESCE(SUM(igst), 0) as input_igst,
        COALESCE(SUM(cgst), 0) as input_cgst,
        COALESCE(SUM(sgst), 0) as input_sgst,
        COALESCE(SUM(total_gst), 0) as total_input_gst,
        COUNT(*) as purchase_count
        FROM purchase_invoices 
        WHERE company_id = ? AND MONTH(invoice_date) = ? AND YEAR(invoice_date) = ? AND itc_eligible = 1");
    $purchase_stmt->bind_param("iii", $company_id, $month, $year);
    $purchase_stmt->execute();
    $input_gst = $purchase_stmt->get_result()->fetch_assoc();

    // Get sales invoices (Output GST)
    $sales_stmt = $conn->prepare("SELECT 
        COALESCE(SUM(igst), 0) as output_igst,
        COALESCE(SUM(cgst), 0) as output_cgst,
        COALESCE(SUM(sgst), 0) as output_sgst,
        COALESCE(SUM(total_gst), 0) as total_output_gst,
        COUNT(*) as sales_count
        FROM sales_invoices 
        WHERE company_id = ? AND MONTH(invoice_date) = ? AND YEAR(invoice_date) = ?");
    $sales_stmt->bind_param("iii", $company_id, $month, $year);
    $sales_stmt->execute();
    $output_gst = $sales_stmt->get_result()->fetch_assoc();

    // Apply GST set-off rules
    $setoff = applyGSTSetOffRules($input_gst, $output_gst);

    return [
        'input' => $input_gst,
        'output' => $output_gst,
        'setoff' => $setoff,
        'period' => [
            'month' => $month,
            'year' => $year,
            'name' => date('F Y', mktime(0, 0, 0, $month, 1, $year))
        ]
    ];
}

// Apply statutory GST set-off rules
function applyGSTSetOffRules($input, $output) {
    $setoff = [
        'steps' => [],
        'remaining_input' => [
            'igst' => $input['input_igst'],
            'cgst' => $input['input_cgst'],
            'sgst' => $input['input_sgst']
        ],
        'remaining_output' => [
            'igst' => $output['output_igst'],
            'cgst' => $output['output_cgst'],
            'sgst' => $output['output_sgst']
        ],
        'utilized_credits' => 0,
        'net_payable' => 0,
        'itc_carried_forward' => 0
    ];

    // Step 1: Input IGST → Output IGST
    $step1_used = min($setoff['remaining_input']['igst'], $setoff['remaining_output']['igst']);
    if ($step1_used > 0) {
        $setoff['steps'][] = [
            'step' => 1,
            'description' => 'Input IGST utilized against Output IGST',
            'used' => $step1_used,
            'from' => 'Input IGST',
            'to' => 'Output IGST'
        ];
        $setoff['remaining_input']['igst'] -= $step1_used;
        $setoff['remaining_output']['igst'] -= $step1_used;
        $setoff['utilized_credits'] += $step1_used;
    }

    // Step 2: Input IGST → Output CGST
    $step2_used = min($setoff['remaining_input']['igst'], $setoff['remaining_output']['cgst']);
    if ($step2_used > 0) {
        $setoff['steps'][] = [
            'step' => 2,
            'description' => 'Input IGST utilized against Output CGST',
            'used' => $step2_used,
            'from' => 'Input IGST',
            'to' => 'Output CGST'
        ];
        $setoff['remaining_input']['igst'] -= $step2_used;
        $setoff['remaining_output']['cgst'] -= $step2_used;
        $setoff['utilized_credits'] += $step2_used;
    }

    // Step 3: Input IGST → Output SGST
    $step3_used = min($setoff['remaining_input']['igst'], $setoff['remaining_output']['sgst']);
    if ($step3_used > 0) {
        $setoff['steps'][] = [
            'step' => 3,
            'description' => 'Input IGST utilized against Output SGST',
            'used' => $step3_used,
            'from' => 'Input IGST',
            'to' => 'Output SGST'
        ];
        $setoff['remaining_input']['igst'] -= $step3_used;
        $setoff['remaining_output']['sgst'] -= $step3_used;
        $setoff['utilized_credits'] += $step3_used;
    }

    // Step 4: Input CGST → Output CGST
    $step4_used = min($setoff['remaining_input']['cgst'], $setoff['remaining_output']['cgst']);
    if ($step4_used > 0) {
        $setoff['steps'][] = [
            'step' => 4,
            'description' => 'Input CGST utilized against Output CGST',
            'used' => $step4_used,
            'from' => 'Input CGST',
            'to' => 'Output CGST'
        ];
        $setoff['remaining_input']['cgst'] -= $step4_used;
        $setoff['remaining_output']['cgst'] -= $step4_used;
        $setoff['utilized_credits'] += $step4_used;
    }

    // Step 5: Input SGST → Output SGST
    $step5_used = min($setoff['remaining_input']['sgst'], $setoff['remaining_output']['sgst']);
    if ($step5_used > 0) {
        $setoff['steps'][] = [
            'step' => 5,
            'description' => 'Input SGST utilized against Output SGST',
            'used' => $step5_used,
            'from' => 'Input SGST',
            'to' => 'Output SGST'
        ];
        $setoff['remaining_input']['sgst'] -= $step5_used;
        $setoff['remaining_output']['sgst'] -= $step5_used;
        $setoff['utilized_credits'] += $step5_used;
    }

    // Step 6: Input CGST → Output IGST
    $step6_used = min($setoff['remaining_input']['cgst'], $setoff['remaining_output']['igst']);
    if ($step6_used > 0) {
        $setoff['steps'][] = [
            'step' => 6,
            'description' => 'Input CGST utilized against Output IGST',
            'used' => $step6_used,
            'from' => 'Input CGST',
            'to' => 'Output IGST'
        ];
        $setoff['remaining_input']['cgst'] -= $step6_used;
        $setoff['remaining_output']['igst'] -= $step6_used;
        $setoff['utilized_credits'] += $step6_used;
    }

    // Step 7: Input SGST → Output IGST
    $step7_used = min($setoff['remaining_input']['sgst'], $setoff['remaining_output']['igst']);
    if ($step7_used > 0) {
        $setoff['steps'][] = [
            'step' => 7,
            'description' => 'Input SGST utilized against Output IGST',
            'used' => $step7_used,
            'from' => 'Input SGST',
            'to' => 'Output IGST'
        ];
        $setoff['remaining_input']['sgst'] -= $step7_used;
        $setoff['remaining_output']['igst'] -= $step7_used;
        $setoff['utilized_credits'] += $step7_used;
    }

    // Calculate net payable and ITC carry forward
    $remaining_output_total = array_sum($setoff['remaining_output']);
    $remaining_input_total = array_sum($setoff['remaining_input']);
    
    $setoff['net_payable'] = max(0, $remaining_output_total - $remaining_input_total);
    $setoff['itc_carried_forward'] = max(0, $remaining_input_total - $remaining_output_total);

    return $setoff;
}

// Calculate GST summary
$gst_summary = calculateGSTSummary($conn, $company_id, $selected_month, $selected_year);

// Get recent periods for dropdown
$periods = [];
for ($i = 0; $i < 12; $i++) {
    $timestamp = strtotime("-$i months");
    $periods[] = [
        'month' => date('n', $timestamp),
        'year' => date('Y', $timestamp),
        'name' => date('F Y', $timestamp)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - GST Summary</title>
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
    background: linear-gradient(135deg, #cd66eaff 0%, #764ba2 100%);
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
    background: linear-gradient(90deg, #7f34dbff, #4e2c50ff);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.nav-menu a:hover, 
.nav-menu a.active {
    color: #b134dbff;
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
            color: #9b59b6;
            text-decoration: none;
            font-weight: 500;
        }

        /* Period Selector */
        .period-selector {
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
            border-color: #9b59b6;
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
            background: #9b59b6;
            color: white;
        }

        .btn-primary:hover {
            background: #8e44ad;
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
            border-left: 4px solid #9b59b6;
        }

        .summary-card.input {
            border-left-color: #e74c3c;
        }

        .summary-card.output {
            border-left-color: #27ae60;
        }

        .summary-card.net {
            border-left-color: #3498db;
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

        .gst-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .gst-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .gst-item:last-child {
            border-bottom: none;
            font-weight: 600;
            color: #2c3e50;
        }

        .gst-label {
            color: #7f8c8d;
        }

        .gst-value {
            font-weight: 600;
        }

        .total-amount {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
        }

        .total-amount.input {
            background: #fdedec;
            color: #e74c3c;
        }

        .total-amount.output {
            background: #eafaf1;
            color: #27ae60;
        }

        /* Set-off Steps */
        .setoff-section {
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

        .setoff-steps {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .setoff-step {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #9b59b6;
        }

        .step-number {
            background: #9b59b6;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .step-details {
            flex: 1;
        }

        .step-description {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .step-amount {
            font-size: 14px;
            color: #27ae60;
            font-weight: 600;
        }

        /* Final Result */
        .final-result {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .result-amount {
            font-size: 32px;
            font-weight: 700;
            margin: 20px 0;
        }

        .result-payable {
            color: #e74c3c;
        }

        .result-refund {
            color: #27ae60;
        }

        .result-zero {
            color: #7f8c8d;
        }

        .result-label {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        /* Invoice Counts */
        .invoice-counts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .count-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .count-number {
            font-size: 24px;
            font-weight: 700;
            color: #9b59b6;
        }

        .count-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .gst-breakdown {
                grid-template-columns: 1fr;
            }
            
            .invoice-counts {
                grid-template-columns: 1fr;
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
                <h1>Taxxpert - GST Summary</h1>
                <p>GST Calculation & Credit Utilization Report</p>
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
            <li><a href="gst_summary.php" class="active">GST Summary</a></li>
            <li><a href="income_tax_summary.php">Income Tax</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="notifications.php">Notifications</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">GST Summary & Calculation</h1>
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>

        <!-- Period Selector -->
        <div class="period-selector">
            <form method="GET" action="" class="selector-form">
                <div class="form-group">
                    <label>Select Period</label>
                    <select name="month" class="form-control">
                        <?php foreach($periods as $period): ?>
                            <option value="<?php echo $period['month']; ?>" 
                                    <?php echo ($period['month'] == $selected_month && $period['year'] == $selected_year) ? 'selected' : ''; ?>>
                                <?php echo $period['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <select name="year" class="form-control">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">View Summary</button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <!-- Input GST Card -->
            <div class="summary-card input">
                <div class="card-header">
                    <div class="card-icon">📥</div>
                    <div class="card-title">Input GST (ITC Available)</div>
                </div>
                <div class="gst-breakdown">
                    <div class="gst-item">
                        <span class="gst-label">IGST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['input']['input_igst'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">CGST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['input']['input_cgst'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">SGST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['input']['input_sgst'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">Total Input GST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['input']['total_input_gst'], 2); ?></span>
                    </div>
                </div>
                <div class="total-amount input">
                    Total ITC Available: ₹<?php echo number_format($gst_summary['input']['total_input_gst'], 2); ?>
                </div>
            </div>

            <!-- Output GST Card -->
            <div class="summary-card output">
                <div class="card-header">
                    <div class="card-icon">📤</div>
                    <div class="card-title">Output GST (Tax Liability)</div>
                </div>
                <div class="gst-breakdown">
                    <div class="gst-item">
                        <span class="gst-label">IGST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['output']['output_igst'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">CGST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['output']['output_cgst'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">SGST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['output']['output_sgst'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">Total Output GST:</span>
                        <span class="gst-value">₹<?php echo number_format($gst_summary['output']['total_output_gst'], 2); ?></span>
                    </div>
                </div>
                <div class="total-amount output">
                    Total GST Liability: ₹<?php echo number_format($gst_summary['output']['total_output_gst'], 2); ?>
                </div>
            </div>

            <!-- Net Result Card -->
            <div class="summary-card net">
                <div class="card-header">
                    <div class="card-icon">🧮</div>
                    <div class="card-title">Net GST Result</div>
                </div>
                <div class="gst-breakdown">
                    <div class="gst-item">
                        <span class="gst-label">ITC Utilized:</span>
                        <span class="gst-value" style="color: #27ae60;">₹<?php echo number_format($gst_summary['setoff']['utilized_credits'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">Net GST Payable:</span>
                        <span class="gst-value" style="color: #e74c3c;">₹<?php echo number_format($gst_summary['setoff']['net_payable'], 2); ?></span>
                    </div>
                    <div class="gst-item">
                        <span class="gst-label">ITC Carry Forward:</span>
                        <span class="gst-value" style="color: #3498db;">₹<?php echo number_format($gst_summary['setoff']['itc_carried_forward'], 2); ?></span>
                    </div>
                </div>
                
                <!-- Invoice Counts -->
                <div class="invoice-counts">
                    <div class="count-item">
                        <div class="count-number"><?php echo $gst_summary['input']['purchase_count']; ?></div>
                        <div class="count-label">Purchase Invoices</div>
                    </div>
                    <div class="count-item">
                        <div class="count-number"><?php echo $gst_summary['output']['sales_count']; ?></div>
                        <div class="count-label">Sales Invoices</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GST Set-off Steps -->
        <div class="setoff-section">
            <h3 class="section-title">
                <span>🔄</span>
                GST Credit Utilization Steps
            </h3>
            
            <?php if (!empty($gst_summary['setoff']['steps'])): ?>
                <div class="setoff-steps">
                    <?php foreach($gst_summary['setoff']['steps'] as $step): ?>
                        <div class="setoff-step">
                            <div class="step-number"><?php echo $step['step']; ?></div>
                            <div class="step-details">
                                <div class="step-description"><?php echo $step['description']; ?></div>
                                <div class="step-amount">₹<?php echo number_format($step['used'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 30px;">No GST credit utilization for this period</p>
            <?php endif; ?>
        </div>

        <!-- Final Result -->
        <div class="final-result">
            <div class="result-label">Net GST Result for <?php echo $gst_summary['period']['name']; ?></div>
            
            <?php if ($gst_summary['setoff']['net_payable'] > 0): ?>
                <div class="result-amount result-payable">
                    ₹<?php echo number_format($gst_summary['setoff']['net_payable'], 2); ?> Payable
                </div>
                <p style="color: #7f8c8d;">GST payment must be made by 20th of next month</p>
            <?php elseif ($gst_summary['setoff']['itc_carried_forward'] > 0): ?>
                <div class="result-amount result-refund">
                    ₹<?php echo number_format($gst_summary['setoff']['itc_carried_forward'], 2); ?> ITC Carried Forward
                </div>
                <p style="color: #7f8c8d;">Input Tax Credit can be used in future periods</p>
            <?php else: ?>
                <div class="result-amount result-zero">
                    No GST Liability
                </div>
                <p style="color: #7f8c8d;">Input and Output GST are balanced</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to summary cards
            const cards = document.querySelectorAll('.summary-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('fade-in');
            });

            // Auto-refresh page every 30 seconds if there are recent invoices
            setTimeout(() => {
                window.location.reload();
            }, 30000);
        });

        // Add CSS animation
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
            
            .summary-card:hover {
                transform: translateY(-5px);
                transition: transform 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>