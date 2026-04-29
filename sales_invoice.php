<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$company_id = $_SESSION['company_id'];
$success = '';
$error = '';

// Get customers for dropdown
$customers_stmt = $conn->prepare("SELECT id, name, gstin, place_of_supply FROM customers WHERE company_id = ? ORDER BY name");
$customers_stmt->bind_param("i", $company_id);
$customers_stmt->execute();
$customers = $customers_stmt->get_result();

// Get company place of supply for interstate detection
$company_stmt = $conn->prepare("SELECT place_of_supply FROM companies WHERE id = ?");
$company_stmt->bind_param("i", $company_id);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();
$company_place = $company['place_of_supply'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitize_input($_POST['customer_name']);
    $customer_gstin = sanitize_input($_POST['customer_gstin']);
    $customer_place = sanitize_input($_POST['customer_place']);
    $invoice_number = sanitize_input($_POST['invoice_number']);
    $invoice_date = sanitize_input($_POST['invoice_date']);
    $taxable_value = floatval($_POST['taxable_value']);
    $igst = floatval($_POST['igst']);
    $cgst = floatval($_POST['cgst']);
    $sgst = floatval($_POST['sgst']);
    
    // Calculate totals
    $total_gst = $igst + $cgst + $sgst;
    $total_amount = $taxable_value + $total_gst;
    
    // Validate required fields
    if (empty($customer_name) || empty($invoice_number) || empty($invoice_date) || empty($taxable_value)) {
        $error = "Please fill all required fields";
    } else {
        // Check if customer exists, if not create new
        $customer_id = null;
        
        if (!empty($_POST['existing_customer']) && $_POST['existing_customer'] != 'new') {
            $customer_id = intval($_POST['existing_customer']);
        } else {
            // Create new customer
            $new_customer_stmt = $conn->prepare("INSERT INTO customers (company_id, name, gstin, place_of_supply) VALUES (?, ?, ?, ?)");
            $new_customer_stmt->bind_param("isss", $company_id, $customer_name, $customer_gstin, $customer_place);
            
            if ($new_customer_stmt->execute()) {
                $customer_id = $conn->insert_id;
            } else {
                $error = "Failed to create customer: " . $conn->error;
            }
        }
        
        if ($customer_id && empty($error)) {
            // Insert sales invoice
            $insert_stmt = $conn->prepare("INSERT INTO sales_invoices (company_id, customer_id, invoice_number, invoice_date, taxable_value, igst, cgst, sgst, total_gst, total_amount, place_of_supply) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iissdddddds", $company_id, $customer_id, $invoice_number, $invoice_date, $taxable_value, $igst, $cgst, $sgst, $total_gst, $total_amount, $customer_place);
            
            if ($insert_stmt->execute()) {
                $success = "Sales invoice added successfully!";
                // Clear form
                $_POST = array();
            } else {
                $error = "Failed to add invoice: " . $conn->error;
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
    <title>Taxxpert - Sales Invoice</title>
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
    background: linear-gradient(135deg, #62e436ff 0%, #26601dff 100%);
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
    color: #007c00ff;
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
    background: linear-gradient(90deg, #37db34ff, #2c3e50);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.nav-menu a:hover, 
.nav-menu a.active {
    color: #37db34ff;
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
            max-width: 1000px;
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
            color: #27ae60;
            text-decoration: none;
            font-weight: 500;
        }

        /* Form Styles */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            border-color: #27ae60;
            background: white;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        /* Tax Type Indicator */
        .tax-indicator {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 500;
            text-align: center;
        }

        .inter-state {
            background: #e8f6f3;
            color: #27ae60;
            border: 2px solid #27ae60;
        }

        .intra-state {
            background: #fef9e7;
            color: #f39c12;
            border: 2px solid #f39c12;
        }

        /* Calculation Box */
        .calculation-box {
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e1e8ed;
        }

        .calc-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
        }

        .calc-total {
            background: #e8f5e8;
            border: 2px solid #27ae60;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
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
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
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

        /* GST Rate Buttons */
        .gst-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .gst-btn {
            padding: 8px 15px;
            border: 2px solid #27ae60;
            background: white;
            color: #27ae60;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: 500;
        }

        .gst-btn:hover, .gst-btn.active {
            background: #27ae60;
            color: white;
        }

        /* Auto GST Toggle */
        .auto-gst-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .toggle-label {
            font-weight: 500;
            color: #2c3e50;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #27ae60;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Responsive */
        @media (max-width: 768px) {
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
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="welcome-message">
                <h1>Taxxpert - Sales Invoice</h1>
                <p>Record your sales transactions with GST details</p>
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
            <li><a href="sales_invoice.php" class="active">Sales Invoices</a></li>
            <li><a href="expenses.php">Expenses</a></li>
            <li><a href="gst_summary.php">GST Summary</a></li>
            <li><a href="income_tax_summary.php">Income Tax</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="notifications.php">Notifications</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Add Sales Invoice</h1>
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

        <div class="form-container">
            <form method="POST" action="" id="salesForm">
                <!-- Customer Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span>👥</span>
                        Customer Information
                    </h3>
                    
                    <div class="form-group">
                        <label class="required">Select or Add Customer</label>
                        <select name="existing_customer" id="existing_customer" class="form-control" onchange="toggleCustomerFields()">
                            <option value="new">+ Add New Customer</option>
                            <?php while($customer = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>" 
                                        data-gstin="<?php echo htmlspecialchars($customer['gstin']); ?>"
                                        data-place="<?php echo htmlspecialchars($customer['place_of_supply']); ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?> 
                                    <?php if ($customer['gstin']): ?> (<?php echo htmlspecialchars($customer['gstin']); ?>)<?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row" id="newCustomerFields">
                        <div class="form-group">
                            <label class="required">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Enter customer name" value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Customer GSTIN</label>
                            <input type="text" name="customer_gstin" class="form-control" placeholder="Customer GSTIN (optional)" value="<?php echo isset($_POST['customer_gstin']) ? htmlspecialchars($_POST['customer_gstin']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="required">Place of Supply</label>
                            <select name="customer_place" id="customer_place" class="form-control" onchange="checkTaxType()">
                                <option value="">Select State</option>
                                <option value="Delhi" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Delhi') ? 'selected' : ''; ?>>Delhi</option>
                                <option value="Maharashtra" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Maharashtra') ? 'selected' : ''; ?>>Maharashtra</option>
                                <option value="Karnataka" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Karnataka') ? 'selected' : ''; ?>>Karnataka</option>
                                <option value="Tamil Nadu" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Tamil Nadu') ? 'selected' : ''; ?>>Tamil Nadu</option>
                                <option value="Uttar Pradesh" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Uttar Pradesh') ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                <option value="Gujarat" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Gujarat') ? 'selected' : ''; ?>>Gujarat</option>
                                <option value="West Bengal" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'West Bengal') ? 'selected' : ''; ?>>West Bengal</option>
                                <option value="Rajasthan" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Rajasthan') ? 'selected' : ''; ?>>Rajasthan</option>
                                <option value="Punjab" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Punjab') ? 'selected' : ''; ?>>Punjab</option>
                                <option value="Haryana" <?php echo (isset($_POST['customer_place']) && $_POST['customer_place'] == 'Haryana') ? 'selected' : ''; ?>>Haryana</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tax Type Indicator -->
                    <div id="taxTypeIndicator" class="tax-indicator" style="display: none;">
                        <span id="taxTypeText"></span>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span>📄</span>
                        Invoice Details
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control" placeholder="Invoice number" value="<?php echo isset($_POST['invoice_number']) ? htmlspecialchars($_POST['invoice_number']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?php echo isset($_POST['invoice_date']) ? htmlspecialchars($_POST['invoice_date']) : date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required">Taxable Value (₹)</label>
                        <input type="number" name="taxable_value" id="taxable_value" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['taxable_value']) ? htmlspecialchars($_POST['taxable_value']) : ''; ?>" required oninput="calculateGST()">
                    </div>

                    <!-- Auto GST Toggle -->
                    <div class="auto-gst-toggle">
                        <span class="toggle-label">Auto GST Calculation:</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="autoGST" checked onchange="toggleAutoGST()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Quick GST Rates -->
                    <div class="form-group" id="gstRateButtons">
                        <label>Quick GST Rates</label>
                        <div class="gst-buttons">
                            <button type="button" class="gst-btn" onclick="applyGSTRate(5)">5% GST</button>
                            <button type="button" class="gst-btn" onclick="applyGSTRate(12)">12% GST</button>
                            <button type="button" class="gst-btn" onclick="applyGSTRate(18)">18% GST</button>
                            <button type="button" class="gst-btn" onclick="applyGSTRate(28)">28% GST</button>
                            <button type="button" class="gst-btn" onclick="clearGST()">Clear GST</button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>IGST (₹)</label>
                            <input type="number" name="igst" id="igst" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['igst']) ? htmlspecialchars($_POST['igst']) : '0'; ?>" oninput="updateTotal()" readonly>
                        </div>
                        <div class="form-group">
                            <label>CGST (₹)</label>
                            <input type="number" name="cgst" id="cgst" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['cgst']) ? htmlspecialchars($_POST['cgst']) : '0'; ?>" oninput="updateTotal()" readonly>
                        </div>
                        <div class="form-group">
                            <label>SGST (₹)</label>
                            <input type="number" name="sgst" id="sgst" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['sgst']) ? htmlspecialchars($_POST['sgst']) : '0'; ?>" oninput="updateTotal()" readonly>
                        </div>
                    </div>
                </div>

                <!-- Calculation Summary -->
                <div class="calculation-box">
                    <h4 style="margin-bottom: 15px; color: #2c3e50;">Calculation Summary</h4>
                    <div class="calc-row">
                        <span>Taxable Value:</span>
                        <span id="display_taxable">₹0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>IGST:</span>
                        <span id="display_igst">₹0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>CGST:</span>
                        <span id="display_cgst">₹0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>SGST:</span>
                        <span id="display_sgst">₹0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Total GST:</span>
                        <span id="display_total_gst">₹0.00</span>
                    </div>
                    <div class="calc-total">
                        <div class="calc-row">
                            <span><strong>Total Invoice Amount:</strong></span>
                            <span id="display_total_amount"><strong>₹0.00</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                    <button type="submit" class="btn btn-primary">Save Sales Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Company place of supply from PHP
        const companyPlace = '<?php echo $company_place; ?>';

        // Toggle customer fields based on selection
        function toggleCustomerFields() {
            const select = document.getElementById('existing_customer');
            const newFields = document.getElementById('newCustomerFields');
            const customerName = document.querySelector('input[name="customer_name"]');
            const customerGSTIN = document.querySelector('input[name="customer_gstin"]');
            const customerPlace = document.getElementById('customer_place');
            
            if (select.value === 'new') {
                newFields.style.display = 'grid';
                customerName.required = true;
                customerPlace.required = true;
            } else {
                newFields.style.display = 'none';
                customerName.required = false;
                customerPlace.required = false;
                
                // Auto-fill customer details
                const selectedOption = select.options[select.selectedIndex];
                customerName.value = selectedOption.text.split(' (')[0];
                customerGSTIN.value = selectedOption.getAttribute('data-gstin') || '';
                customerPlace.value = selectedOption.getAttribute('data-place') || '';
            }
            
            checkTaxType();
        }

        // Check if transaction is inter-state or intra-state
        function checkTaxType() {
            const customerPlace = document.getElementById('customer_place').value;
            const indicator = document.getElementById('taxTypeIndicator');
            const text = document.getElementById('taxTypeText');
            
            if (customerPlace && customerPlace !== companyPlace) {
                // Inter-state transaction - IGST applicable
                indicator.style.display = 'block';
                indicator.className = 'tax-indicator inter-state';
                text.textContent = '🔄 Inter-State Transaction - IGST Applicable';
            } else if (customerPlace && customerPlace === companyPlace) {
                // Intra-state transaction - CGST + SGST applicable
                indicator.style.display = 'block';
                indicator.className = 'tax-indicator intra-state';
                text.textContent = '🏠 Intra-State Transaction - CGST + SGST Applicable';
            } else {
                indicator.style.display = 'none';
            }
        }

        // Toggle auto GST calculation
        function toggleAutoGST() {
            const autoGST = document.getElementById('autoGST').checked;
            const igst = document.getElementById('igst');
            const cgst = document.getElementById('cgst');
            const sgst = document.getElementById('sgst');
            const gstButtons = document.getElementById('gstRateButtons');
            
            if (autoGST) {
                igst.readOnly = true;
                cgst.readOnly = true;
                sgst.readOnly = true;
                gstButtons.style.display = 'block';
                calculateGST();
            } else {
                igst.readOnly = false;
                cgst.readOnly = false;
                sgst.readOnly = false;
                gstButtons.style.display = 'none';
            }
        }

        // Calculate GST based on taxable value and tax type
        function calculateGST() {
            if (!document.getElementById('autoGST').checked) return;
            
            const taxableValue = parseFloat(document.getElementById('taxable_value').value) || 0;
            const customerPlace = document.getElementById('customer_place').value;
            const gstRate = 18; // Default rate, can be made dynamic
            
            if (taxableValue > 0 && customerPlace) {
                const gstAmount = taxableValue * (gstRate / 100);
                
                if (customerPlace !== companyPlace) {
                    // Inter-state - IGST
                    document.getElementById('igst').value = gstAmount.toFixed(2);
                    document.getElementById('cgst').value = '0';
                    document.getElementById('sgst').value = '0';
                } else {
                    // Intra-state - CGST + SGST
                    document.getElementById('igst').value = '0';
                    document.getElementById('cgst').value = (gstAmount / 2).toFixed(2);
                    document.getElementById('sgst').value = (gstAmount / 2).toFixed(2);
                }
                
                updateTotal();
            }
        }

        // Apply GST rate (quick buttons)
        function applyGSTRate(rate) {
            const taxableValue = parseFloat(document.getElementById('taxable_value').value) || 0;
            const customerPlace = document.getElementById('customer_place').value;
            
            if (taxableValue > 0 && customerPlace) {
                const gstAmount = taxableValue * (rate / 100);
                
                if (customerPlace !== companyPlace) {
                    // Inter-state - IGST
                    document.getElementById('igst').value = gstAmount.toFixed(2);
                    document.getElementById('cgst').value = '0';
                    document.getElementById('sgst').value = '0';
                } else {
                    // Intra-state - CGST + SGST
                    document.getElementById('igst').value = '0';
                    document.getElementById('cgst').value = (gstAmount / 2).toFixed(2);
                    document.getElementById('sgst').value = (gstAmount / 2).toFixed(2);
                }
                
                updateTotal();
            } else {
                alert('Please enter taxable value and select customer place of supply first');
            }
        }

        // Clear GST fields
        function clearGST() {
            document.getElementById('igst').value = '0';
            document.getElementById('cgst').value = '0';
            document.getElementById('sgst').value = '0';
            updateTotal();
        }

        // Update total amounts
        function updateTotal() {
            const taxableValue = parseFloat(document.getElementById('taxable_value').value) || 0;
            const igst = parseFloat(document.getElementById('igst').value) || 0;
            const cgst = parseFloat(document.getElementById('cgst').value) || 0;
            const sgst = parseFloat(document.getElementById('sgst').value) || 0;
            
            const totalGST = igst + cgst + sgst;
            const totalAmount = taxableValue + totalGST;
            
            // Update display
            updateDisplay();
        }

        // Update calculation display
        function updateDisplay() {
            const taxableValue = parseFloat(document.getElementById('taxable_value').value) || 0;
            const igst = parseFloat(document.getElementById('igst').value) || 0;
            const cgst = parseFloat(document.getElementById('cgst').value) || 0;
            const sgst = parseFloat(document.getElementById('sgst').value) || 0;
            const totalGST = igst + cgst + sgst;
            const totalAmount = taxableValue + totalGST;
            
            document.getElementById('display_taxable').textContent = '₹' + taxableValue.toFixed(2);
            document.getElementById('display_igst').textContent = '₹' + igst.toFixed(2);
            document.getElementById('display_cgst').textContent = '₹' + cgst.toFixed(2);
            document.getElementById('display_sgst').textContent = '₹' + sgst.toFixed(2);
            document.getElementById('display_total_gst').textContent = '₹' + totalGST.toFixed(2);
            document.getElementById('display_total_amount').innerHTML = '<strong>₹' + totalAmount.toFixed(2) + '</strong>';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomerFields();
            toggleAutoGST();
            checkTaxType();
            updateDisplay();
            
            // Auto-focus on first input
            document.getElementById('existing_customer').focus();
        });

        // Form validation
        document.getElementById('salesForm').addEventListener('submit', function(e) {
            const taxableValue = parseFloat(document.getElementById('taxable_value').value) || 0;
            if (taxableValue <= 0) {
                alert('Please enter a valid taxable value');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>