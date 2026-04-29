-- Create Database
CREATE DATABASE IF NOT EXISTS taxxpert;
USE taxxpert;

-- 1. Companies Table
CREATE TABLE companies (
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
);

-- 2. Suppliers Table
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    gstin VARCHAR(15),
    place_of_supply VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 3. Customers Table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    gstin VARCHAR(15),
    place_of_supply VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 4. Purchase Invoices Table
CREATE TABLE purchase_invoices (
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
);

-- 5. Sales Invoices Table
CREATE TABLE sales_invoices (
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
);

-- 6. Expenses Table (For Income Tax)
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 7. GST Summary Table
CREATE TABLE gst_summary (
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
);

-- 8. Income Tax Summary Table
CREATE TABLE income_tax_summary (
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
);

-- 9. Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    type ENUM('gst_reminder', 'payment_due', 'tax_filing', 'general') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 10. Admin Table
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO admin (email, password, name) VALUES 
('admin@taxxpert.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Taxxpert Admin');

-- Create Indexes for Better Performance
CREATE INDEX idx_purchase_company_date ON purchase_invoices(company_id, invoice_date);
CREATE INDEX idx_sales_company_date ON sales_invoices(company_id, invoice_date);
CREATE INDEX idx_expenses_company_date ON expenses(company_id, expense_date);
CREATE INDEX idx_notifications_company ON notifications(company_id, is_read, created_at);

-- Sample Data for Testing (Optional)
INSERT INTO companies (name, gstin, pan, place_of_supply, email, password) VALUES 
('Demo Company', '07AABCU9603R1ZM', 'AABCU9603R', 'Delhi', 'demo@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
