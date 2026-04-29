<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get company details
$company_id = $_SESSION['company_id'];
$stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

// Get current period for calculations
$current_month = date('n');
$current_year = date('Y');

// Dashboard Statistics
// Purchase Invoices
$purchase_stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(total_gst), 0) as gst FROM purchase_invoices WHERE company_id = ? AND MONTH(invoice_date) = ? AND YEAR(invoice_date) = ?");
$purchase_stmt->bind_param("iii", $company_id, $current_month, $current_year);
$purchase_stmt->execute();
$purchase_stats = $purchase_stmt->get_result()->fetch_assoc();

// Sales Invoices
$sales_stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total, COALESCE(SUM(total_gst), 0) as gst FROM sales_invoices WHERE company_id = ? AND MONTH(invoice_date) = ? AND YEAR(invoice_date) = ?");
$sales_stmt->bind_param("iii", $company_id, $current_month, $current_year);
$sales_stmt->execute();
$sales_stats = $sales_stmt->get_result()->fetch_assoc();

// Expenses
$expenses_stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM expenses WHERE company_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
$expenses_stmt->bind_param("iii", $company_id, $current_month, $current_year);
$expenses_stmt->execute();
$expenses_stats = $expenses_stmt->get_result()->fetch_assoc();

// GST Calculation
$input_igst = $purchase_stats['gst'] * 0.5; // Assuming equal split for demo
$input_cgst = $purchase_stats['gst'] * 0.5;
$output_igst = $sales_stats['gst'] * 0.5;
$output_cgst = $sales_stats['gst'] * 0.5;

// Simple GST Payable Calculation
$gst_payable = max(0, $sales_stats['gst'] - $purchase_stats['gst']);

// Income Tax Calculation (Yearly)
$current_year = date('Y');
$revenue_stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM sales_invoices WHERE company_id = ? AND YEAR(invoice_date) = ?");
$revenue_stmt->bind_param("ii", $company_id, $current_year);
$revenue_stmt->execute();
$revenue = $revenue_stmt->get_result()->fetch_assoc()['revenue'];

$yearly_expenses_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as expenses FROM expenses WHERE company_id = ? AND YEAR(expense_date) = ?");
$yearly_expenses_stmt->bind_param("ii", $company_id, $current_year);
$yearly_expenses_stmt->execute();
$yearly_expenses = $yearly_expenses_stmt->get_result()->fetch_assoc()['expenses'];

$profit = $revenue - $yearly_expenses;
$income_tax = max(0, $profit * 0.25); // 25% tax rate

// Get recent notifications
$notifications_stmt = $conn->prepare("SELECT * FROM notifications WHERE company_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notifications_stmt->bind_param("i", $company_id);
$notifications_stmt->execute();
$notifications = $notifications_stmt->get_result();

// Get recent invoices
$recent_invoices_stmt = $conn->prepare("(SELECT 'Purchase' as type, invoice_number, invoice_date, total_amount FROM purchase_invoices WHERE company_id = ? ORDER BY invoice_date DESC LIMIT 3) UNION ALL (SELECT 'Sales' as type, invoice_number, invoice_date, total_amount FROM sales_invoices WHERE company_id = ? ORDER BY invoice_date DESC LIMIT 3) ORDER BY invoice_date DESC LIMIT 6");
$recent_invoices_stmt->bind_param("ii", $company_id, $company_id);
$recent_invoices_stmt->execute();
$recent_invoices = $recent_invoices_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Dashboard</title>
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: linear-gradient(90deg, #3498db, #2c3e50);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.nav-menu a:hover, 
.nav-menu a.active {
    color: #3498db;
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

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.purchase { border-left-color: #e74c3c; }
        .stat-card.sales { border-left-color: #27ae60; }
        .stat-card.expenses { border-left-color: #f39c12; }
        .stat-card.gst { border-left-color: #9b59b6; }
        .stat-card.income-tax { border-left-color: #1abc9c; }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-subtext {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 5px;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-container, .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-3px);
            border-color: #3498db;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.2);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 15px;
            color: #3498db;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .action-desc {
            font-size: 12px;
            color: #7f8c8d;
        }

        /* Recent Activity */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .type-purchase { background: #ffeaa7; color: #856404; }
        .type-sales { background: #d1f7c4; color: #2d5016; }

        .activity-amount {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Notifications */
        .notification-item {
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .notification-message {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .notification-date {
            font-size: 11px;
            color: #95a5a6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-menu a {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .header-top {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .user-actions {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="welcome-message">
                <h1>Welcome, <?php echo htmlspecialchars($company['name']); ?>! 👋</h1>
                <p>GSTIN: <?php echo htmlspecialchars($company['gstin']); ?> | Place of Supply: <?php echo htmlspecialchars($company['place_of_supply']); ?></p>
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
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="purchase_invoice.php">Purchase Invoices</a></li>
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
        <!-- Stat Cards -->
        <div class="dashboard-grid">
            <div class="stat-card purchase">
                <div class="stat-icon">📥</div>
                <div class="stat-value">₹<?php echo number_format($purchase_stats['total'], 2); ?></div>
                <div class="stat-label">Total Purchases</div>
                <div class="stat-subtext"><?php echo $purchase_stats['count']; ?> invoices | GST: ₹<?php echo number_format($purchase_stats['gst'], 2); ?></div>
            </div>

            <div class="stat-card sales">
                <div class="stat-icon">📤</div>
                <div class="stat-value">₹<?php echo number_format($sales_stats['total'], 2); ?></div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-subtext"><?php echo $sales_stats['count']; ?> invoices | GST: ₹<?php echo number_format($sales_stats['gst'], 2); ?></div>
            </div>

            <div class="stat-card expenses">
                <div class="stat-icon">💰</div>
                <div class="stat-value">₹<?php echo number_format($expenses_stats['total'], 2); ?></div>
                <div class="stat-label">Monthly Expenses</div>
                <div class="stat-subtext"><?php echo $expenses_stats['count']; ?> expense entries</div>
            </div>

            <div class="stat-card gst">
                <div class="stat-icon">🧮</div>
                <div class="stat-value">₹<?php echo number_format($gst_payable, 2); ?></div>
                <div class="stat-label">GST Payable</div>
                <div class="stat-subtext">Current Month</div>
            </div>

            <div class="stat-card income-tax">
                <div class="stat-icon">📊</div>
                <div class="stat-value">₹<?php echo number_format($income_tax, 2); ?></div>
                <div class="stat-label">Income Tax (Yearly)</div>
                <div class="stat-subtext">Based on current data</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="purchase_invoice.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-title">Add Purchase</div>
                <div class="action-desc">Record new purchase invoice</div>
            </a>
            <a href="sales_invoice.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-title">Add Sale</div>
                <div class="action-desc">Record new sales invoice</div>
            </a>
            <a href="expenses.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-title">Add Expense</div>
                <div class="action-desc">Record business expense</div>
            </a>
            <a href="gst_summary.php" class="action-card">
                <div class="action-icon">📋</div>
                <div class="action-title">GST Report</div>
                <div class="action-desc">View GST summary</div>
            </a>
        </div>

        <div class="charts-section">
            <!-- Recent Invoices -->
            <div class="chart-container">
                <h3 class="section-title">Recent Invoices</h3>
                <div class="activity-list">
                    <?php while($invoice = $recent_invoices->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div>
                                <span class="activity-type <?php echo 'type-' . strtolower($invoice['type']); ?>">
                                    <?php echo $invoice['type']; ?>
                                </span>
                                <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                <div class="stat-subtext"><?php echo $invoice['invoice_date']; ?></div>
                            </div>
                            <div class="activity-amount">₹<?php echo number_format($invoice['total_amount'], 2); ?></div>
                        </div>
                    <?php endwhile; ?>
                    <?php if ($recent_invoices->num_rows == 0): ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">No invoices found</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications -->
            <div class="recent-activity">
                <h3 class="section-title">Notifications</h3>
                <div class="notification-list">
                    <?php while($notification = $notifications->fetch_assoc()): ?>
                        <div class="notification-item">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-date"><?php echo $notification['created_at']; ?></div>
                        </div>
                    <?php endwhile; ?>
                    <?php if ($notifications->num_rows == 0): ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">No new notifications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('fade-in');
            });

            // Auto-refresh notifications every 30 seconds
            setInterval(() => {
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update notification count if needed
                    });
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
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>