<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$company_id = $_SESSION['company_id'];
$success = '';
$error = '';

// Get suppliers for dropdown
$suppliers_stmt = $conn->prepare("SELECT id, name, gstin, place_of_supply FROM suppliers WHERE company_id = ? ORDER BY name");
$suppliers_stmt->bind_param("i", $company_id);
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $supplier_gstin = sanitize_input($_POST['supplier_gstin']);
    $supplier_place = sanitize_input($_POST['supplier_place']);
    $invoice_number = sanitize_input($_POST['invoice_number']);
    $invoice_date = sanitize_input($_POST['invoice_date']);
    $taxable_value = floatval($_POST['taxable_value']);
    $igst = floatval($_POST['igst']);
    $cgst = floatval($_POST['cgst']);
    $sgst = floatval($_POST['sgst']);
    $itc_eligible = isset($_POST['itc_eligible']) ? 1 : 0;
    $reverse_charge = isset($_POST['reverse_charge']) ? 1 : 0;
    
    // Calculate totals
    $total_gst = $igst + $cgst + $sgst;
    $total_amount = $taxable_value + $total_gst;
    
    // Validate required fields
    if (empty($supplier_name) || empty($invoice_number) || empty($invoice_date) || empty($taxable_value)) {
        $error = "Please fill all required fields";
    } else {
        // Check if supplier exists, if not create new
        $supplier_id = null;
        
        if (!empty($_POST['existing_supplier']) && $_POST['existing_supplier'] != 'new') {
            $supplier_id = intval($_POST['existing_supplier']);
        } else {
            // Create new supplier
            $new_supplier_stmt = $conn->prepare("INSERT INTO suppliers (company_id, name, gstin, place_of_supply) VALUES (?, ?, ?, ?)");
            $new_supplier_stmt->bind_param("isss", $company_id, $supplier_name, $supplier_gstin, $supplier_place);
            
            if ($new_supplier_stmt->execute()) {
                $supplier_id = $conn->insert_id;
            } else {
                $error = "Failed to create supplier: " . $conn->error;
            }
        }
        
        if ($supplier_id && empty($error)) {
            // Insert purchase invoice
            $insert_stmt = $conn->prepare("INSERT INTO purchase_invoices (company_id, supplier_id, invoice_number, invoice_date, taxable_value, igst, cgst, sgst, total_gst, total_amount, itc_eligible, reverse_charge, place_of_supply) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iissdddddddds", $company_id, $supplier_id, $invoice_number, $invoice_date, $taxable_value, $igst, $cgst, $sgst, $total_gst, $total_amount, $itc_eligible, $reverse_charge, $supplier_place);
            
            if ($insert_stmt->execute()) {
                $success = "Purchase invoice added successfully!";
                // Clear form
                $_POST = array();
            } else {
                $error = "Failed to add invoice: " . $conn->error;
            }
        }
    }
}

// Auto GST calculation function
function calculateGST($taxable_value, $gst_rate, $is_inter_state) {
    $gst_amount = $taxable_value * ($gst_rate / 100);
    
    if ($is_inter_state) {
        return ['igst' => $gst_amount, 'cgst' => 0, 'sgst' => 0];
    } else {
        return ['igst' => 0, 'cgst' => $gst_amount / 2, 'sgst' => $gst_amount / 2];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Purchase Invoice</title>
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
    background: linear-gradient(135deg, #de772dff 0%, #a2794bff 100%);
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
    color: #79643fff;
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
    background: linear-gradient(90deg, #db7c34ff, #da6e6eff);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.nav-menu a:hover, 
.nav-menu a.active {
    color: #db5e34ff;
    background: rgba(205, 107, 107, 0.05);
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
            justify-content: between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
        }

        .back-link {
            color: #3498db;
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

        .section-title i {
            font-size: 20px;
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
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
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
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
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
            border: 2px solid #3498db;
            background: white;
            color: #3498db;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: 500;
        }

        .gst-btn:hover, .gst-btn.active {
            background: #3498db;
            color: white;
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
                <h1>Taxxpert - Purchase Invoice</h1>
                <p>Record your purchase transactions with GST details</p>
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
            <li><a href="purchase_invoice.php" class="active">Purchase Invoices</a></li>
            <li><a href="sales_invoice.php">Sales Invoices</a></li>
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
            <h1 class="page-title">Add Purchase Invoice</h1>
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
            <form method="POST" action="" id="purchaseForm">
                <!-- Supplier Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span>👥</span>
                        Supplier Information
                    </h3>
                    
                    <div class="form-group">
                        <label class="required">Select or Add Supplier</label>
                        <select name="existing_supplier" id="existing_supplier" class="form-control" onchange="toggleSupplierFields()">
                            <option value="new">+ Add New Supplier</option>
                            <?php while($supplier = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['id']; ?>" 
                                        data-gstin="<?php echo htmlspecialchars($supplier['gstin']); ?>"
                                        data-place="<?php echo htmlspecialchars($supplier['place_of_supply']); ?>">
                                    <?php echo htmlspecialchars($supplier['name']); ?> 
                                    <?php if ($supplier['gstin']): ?> (<?php echo htmlspecialchars($supplier['gstin']); ?>)<?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row" id="newSupplierFields">
                        <div class="form-group">
                            <label class="required">Supplier Name</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="Enter supplier name" value="<?php echo isset($_POST['supplier_name']) ? htmlspecialchars($_POST['supplier_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Supplier GSTIN</label>
                            <input type="text" name="supplier_gstin" class="form-control" placeholder="Supplier GSTIN (optional)" value="<?php echo isset($_POST['supplier_gstin']) ? htmlspecialchars($_POST['supplier_gstin']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="required">Place of Supply</label>
                            <input type="text" name="supplier_place" class="form-control" placeholder="State of supply" value="<?php echo isset($_POST['supplier_place']) ? htmlspecialchars($_POST['supplier_place']) : ''; ?>">
                        </div>
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

                    <!-- Quick GST Rates -->
                    <div class="form-group">
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
                            <input type="number" name="igst" id="igst" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['igst']) ? htmlspecialchars($_POST['igst']) : '0'; ?>" oninput="updateTotal()">
                        </div>
                        <div class="form-group">
                            <label>CGST (₹)</label>
                            <input type="number" name="cgst" id="cgst" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['cgst']) ? htmlspecialchars($_POST['cgst']) : '0'; ?>" oninput="updateTotal()">
                        </div>
                        <div class="form-group">
                            <label>SGST (₹)</label>
                            <input type="number" name="sgst" id="sgst" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo isset($_POST['sgst']) ? htmlspecialchars($_POST['sgst']) : '0'; ?>" oninput="updateTotal()">
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span>⚙️</span>
                        Additional Options
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="itc_eligible" id="itc_eligible" value="1" <?php echo (isset($_POST['itc_eligible']) && $_POST['itc_eligible']) ? 'checked' : 'checked'; ?>>
                                <label for="itc_eligible">ITC Eligible (Input Tax Credit)</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="reverse_charge" id="reverse_charge" value="1" <?php echo (isset($_POST['reverse_charge']) && $_POST['reverse_charge']) ? 'checked' : ''; ?>>
                                <label for="reverse_charge">Reverse Charge Applicable</label>
                            </div>
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
                    <button type="submit" class="btn btn-primary">Save Purchase Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle supplier fields based on selection
        function toggleSupplierFields() {
            const select = document.getElementById('existing_supplier');
            const newFields = document.getElementById('newSupplierFields');
            const supplierName = document.querySelector('input[name="supplier_name"]');
            const supplierGSTIN = document.querySelector('input[name="supplier_gstin"]');
            const supplierPlace = document.querySelector('input[name="supplier_place"]');
            
            if (select.value === 'new') {
                newFields.style.display = 'grid';
                supplierName.required = true;
            } else {
                newFields.style.display = 'none';
                supplierName.required = false;
                
                // Auto-fill supplier details
                const selectedOption = select.options[select.selectedIndex];
                supplierName.value = selectedOption.text.split(' (')[0];
                supplierGSTIN.value = selectedOption.getAttribute('data-gstin') || '';
                supplierPlace.value = selectedOption.getAttribute('data-place') || '';
            }
        }

        // Calculate GST based on taxable value and rate
        function calculateGST() {
            updateDisplay();
        }

        // Apply GST rate (quick buttons)
        function applyGSTRate(rate) {
            const taxableValue = parseFloat(document.getElementById('taxable_value').value) || 0;
            const isInterState = true; // You can add logic to detect interstate based on place of supply
            
            if (taxableValue > 0) {
                const gstAmount = taxableValue * (rate / 100);
                
                if (isInterState) {
                    document.getElementById('igst').value = gstAmount.toFixed(2);
                    document.getElementById('cgst').value = '0';
                    document.getElementById('sgst').value = '0';
                } else {
                    document.getElementById('igst').value = '0';
                    document.getElementById('cgst').value = (gstAmount / 2).toFixed(2);
                    document.getElementById('sgst').value = (gstAmount / 2).toFixed(2);
                }
                
                updateTotal();
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
            toggleSupplierFields();
            updateDisplay();
            
            // Auto-focus on first input
            document.getElementById('existing_supplier').focus();
        });

        // Form validation
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
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