# Taxxpert - GST & Income Tax Portal

A free, web-based automation portal that simplifies tax compliance for Indian product manufacturers by automating monthly GST tracking and yearly income tax computation.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Active-brightgreen)

---

## 🎯 Overview

Taxxpert helps **SMEs** manage tax obligations without expensive accounting software.

| Module | Purpose |
|--------|---------|
| **GST Computation** | Intra/inter-state classification, set-off rules, net payable/refundable |
| **Income Tax** | Annual profit computation, corporate tax rates |
| **Compliance** | Automated reminders for deadlines |
| **Admin Panel** | Centralized company monitoring |

---

## ✨ Features

- GSTIN, PAN-based company registration & profile management
- Purchase & sales invoices with IGST, CGST, SGST breakup
- Auto-fill suppliers/customers during entry
- Automatic GST set-off per Indian GST rules
- Monthly GST summary with credit audit trail
- Annual income tax with expense tracking
- Real-time dashboard charts
- CSV & PDF report downloads
- Tax deadline notifications
- Admin oversight panel

---

## 🛠 Tech Stack

| Layer | Tech |
|-------|------|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Local | XAMPP |
| Hosting | InfinityFree |

---

## 📁 Project Structure
taxxpert/
├── index.php # Landing page
├── config.php # Database config & helpers
├── register.php # Company registration
├── login.php # Company login
├── logout.php # Logout handler
├── dashboard.php # Main company dashboard
├── purchase_invoice.php # Purchase invoice management
├── sales_invoice.php # Sales invoice management
├── expenses.php # Business expense tracking
├── gst_summary.php # GST computation & summary
├── income_tax_summary.php # Income tax calculation
├── reports.php # Report generation (CSV/PDF)
├── notifications.php # Notification management
├── profile.php # Company profile management
├── admin_login.php # Admin authentication
├── admin_panel.php # Admin dashboard
├── admin_logout.php # Admin logout
├── style.css # Main stylesheet
└── README.md # Project documentation

text

---

## 🗄 Database Tables

| Table | Purpose |
|-------|---------|
| `companies` | Company registration & profile |
| `suppliers` | Supplier auto-fill records |
| `customers` | Customer auto-fill records |
| `purchase_invoices` | Purchase records + GST details |
| `sales_invoices` | Sales records + GST details |
| `expenses` | Business expense entries |
| `gst_summary` | Monthly GST calculation results |
| `income_tax_summary` | Yearly IT computation |
| `notifications` | System alerts & reminders |
| `admin` | Admin credentials |

---

## ⚡ Quick Setup

### Requirements
- XAMPP (PHP 7.4+, MySQL 5.7+)
- Web browser

### Steps

**1. Clone Repository**
```bash
git clone https://github.com/AkashprabuA/TaxXpert.git
2. Move to htdocs

bash
mv taxxpert /xampp/htdocs/
3. Start XAMPP - Launch Apache & MySQL

4. Create Database

Open http://localhost/phpmyadmin

Create database: taxxpert

Tables will auto-create on first run

5. Configure (edit config.php)

php
$host = "localhost";
$username = "root";
$password = "";
$database = "taxxpert";
6. Access: http://localhost/taxxpert



📋 How to Use
Company Flow
Register → Add Suppliers/Customers → Enter Purchase & Sales Invoices → Track Expenses → View GST Summary → View Income Tax → Download Reports

Admin Flow
Login → View All Companies → Monitor Compliance → Manage Accounts

🔄 GST Set-Off Order
Priority	Input Tax	Set Against
1	IGST	IGST
2	IGST	CGST
3	IGST	SGST
4	CGST	CGST
5	SGST	SGST
❌ CGST & SGST cannot be set off against IGST

🧪 Testing
Unit Testing - Individual modules

Integration Testing - Data flow between components

Functional Testing - Feature validation

Validation Testing - Input formats (GSTIN, PAN, email)

Database Testing - Relational integrity

Security Testing - Session management, access control

UI Testing - Responsiveness

⚠️ Limitations
Free hosting constraints (bandwidth, no SSL)

No direct government portal filing integration

Manual expense entry required

Flat 25% corporate tax assumption

Not optimized for large enterprises

🚀 Future Scope
OTP & Multi-factor authentication

Direct GSTN & IT e-filing integration

Role-based access (admin, accountant, auditor)

Mobile-responsive design

REST API for third-party integration

Multi-user per company support

Cloud deployment (AWS/Azure)

📄 License
This project is submitted as partial fulfillment of the requirements for the Degree of Bachelor of Science in Computer Science. All rights reserved.

👨‍💻 Author
Akash Prabu A

Register Number: 235114411

Batch: 2023-2026

PG Department of Computer Science (S.F)

Bishop Heber College (Autonomous), Tiruchirappalli - 620017

🙏 Acknowledgments
Guide: Ms.Rizwana, Associate Professor/Assistant Professor

Institution: Bishop Heber College (Autonomous), NAAC Reaccredited 'A++' Grade (CGPA 3.69/4)

Affiliation: Bharathidasan University

📚 References
Ahuja, Dr. Girish and Gupta, Dr. Ravi, "Systematic Approach to Taxation Containing Income Tax and GST", 7th Edition, Commercial Law Publishers, 2025.

Duckett, Jon, "PHP & MySQL: Server-Side Web Development", 1st Edition, Wiley India, 2022.

Nixon, Robin, "Learning PHP, MySQL & JavaScript with jQuery, CSS & HTML5", 5th Edition, O'Reilly Media, 2015.

<p align="center"> Made with ❤️ by Akash Prabu A </p> ```
