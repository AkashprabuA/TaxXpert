<?php
// Start session
session_start();

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "taxxpert";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Create tables if they don't exist
function createTables($conn) {
    $tables = array(
        "companies" => "CREATE TABLE IF NOT EXISTS companies (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            gstin VARCHAR(15) UNIQUE NOT NULL,
            pan VARCHAR(10) NOT NULL,
            place_of_supply VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            profile_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "suppliers" => "CREATE TABLE IF NOT EXISTS suppliers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            gstin VARCHAR(15),
            place_of_supply VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )",
        
        "customers" => "CREATE TABLE IF NOT EXISTS customers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            gstin VARCHAR(15),
            place_of_supply VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )",
        
        "purchase_invoices" => "CREATE TABLE IF NOT EXISTS purchase_invoices (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            supplier_id INT NOT NULL,
            invoice_number VARCHAR(100) NOT NULL,
            invoice_date DATE NOT NULL,
            taxable_value DECIMAL(15,2) NOT NULL,
            igst DECIMAL(15,2) DEFAULT 0,
            cgst DECIMAL(15,2) DEFAULT 0,
            sgst DECIMAL(15,2) DEFAULT 0,
            total_gst DECIMAL(15,2) NOT NULL,
            total_amount DECIMAL(15,2) NOT NULL,
            itc_eligible BOOLEAN DEFAULT TRUE,
            reverse_charge BOOLEAN DEFAULT FALSE,
            place_of_supply VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
        )",
        
        "sales_invoices" => "CREATE TABLE IF NOT EXISTS sales_invoices (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            customer_id INT NOT NULL,
            invoice_number VARCHAR(100) NOT NULL,
            invoice_date DATE NOT NULL,
            taxable_value DECIMAL(15,2) NOT NULL,
            igst DECIMAL(15,2) DEFAULT 0,
            cgst DECIMAL(15,2) DEFAULT 0,
            sgst DECIMAL(15,2) DEFAULT 0,
            total_gst DECIMAL(15,2) NOT NULL,
            total_amount DECIMAL(15,2) NOT NULL,
            place_of_supply VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )",
        
        "expenses" => "CREATE TABLE IF NOT EXISTS expenses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            expense_date DATE NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            amount DECIMAL(15,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )",
        
        "gst_summary" => "CREATE TABLE IF NOT EXISTS gst_summary (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            period_month INT NOT NULL,
            period_year INT NOT NULL,
            total_input_igst DECIMAL(15,2) DEFAULT 0,
            total_input_cgst DECIMAL(15,2) DEFAULT 0,
            total_input_sgst DECIMAL(15,2) DEFAULT 0,
            total_output_igst DECIMAL(15,2) DEFAULT 0,
            total_output_cgst DECIMAL(15,2) DEFAULT 0,
            total_output_sgst DECIMAL(15,2) DEFAULT 0,
            net_gst_payable DECIMAL(15,2) DEFAULT 0,
            itc_carried_forward DECIMAL(15,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_period (company_id, period_month, period_year)
        )",
        
        "income_tax_summary" => "CREATE TABLE IF NOT EXISTS income_tax_summary (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            financial_year VARCHAR(9) NOT NULL,
            total_revenue DECIMAL(15,2) DEFAULT 0,
            total_expenses DECIMAL(15,2) DEFAULT 0,
            profit DECIMAL(15,2) DEFAULT 0,
            income_tax_payable DECIMAL(15,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 25.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_financial_year (company_id, financial_year)
        )",
        
        "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NOT NULL,
            type ENUM('gst_reminder', 'payment_due', 'tax_filing', 'general') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            due_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )",
        
        "admin" => "CREATE TABLE IF NOT EXISTS admin (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    // Execute each table creation
    foreach ($tables as $table => $sql) {
        if (!$conn->query($sql)) {
            error_log("Error creating table $table: " . $conn->error);
        }
    }

    // Insert default admin if not exists
    $check_admin = $conn->query("SELECT COUNT(*) as count FROM admin");
    if ($check_admin && $check_admin->fetch_assoc()['count'] == 0) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO admin (email, password, name) VALUES 
                    ('admin@taxxpert.com', '$hashed_password', 'Taxxpert Admin')");
    }

    // Insert demo company if not exists
    $check_company = $conn->query("SELECT COUNT(*) as count FROM companies");
    if ($check_company && $check_company->fetch_assoc()['count'] == 0) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO companies (name, gstin, pan, place_of_supply, email, password) VALUES 
                    ('Demo Company', '07AABCU9603R1ZM', 'AABCU9603R', 'Delhi', 'demo@company.com', '$hashed_password')");
    }
}

// Initialize tables
createTables($conn);

// Helper functions
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function is_logged_in() {
    return isset($_SESSION['company_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Auto-create notifications for demo
function createDemoNotifications($company_id) {
    global $conn;
    
    $notifications = [
        [
            'type' => 'gst_reminder',
            'title' => 'GST Filing Reminder',
            'message' => 'Last date for GST filing for this month is 20th. Please complete your invoice entries.',
            'due_date' => date('Y-m-d', strtotime('+5 days'))
        ],
        [
            'type' => 'payment_due',
            'title' => 'GST Payment Due',
            'message' => 'GST payment for the previous month is due on 25th.',
            'due_date' => date('Y-m-d', strtotime('+10 days'))
        ]
    ];
    
    foreach ($notifications as $notification) {
        $stmt = $conn->prepare("INSERT INTO notifications (company_id, type, title, message, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $company_id, $notification['type'], $notification['title'], $notification['message'], $notification['due_date']);
        $stmt->execute();
    }
}
?>