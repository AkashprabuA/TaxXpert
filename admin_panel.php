<?php
require_once 'config.php';

// Redirect to admin login if not logged in as admin
if (!is_admin_logged_in()) {
    redirect('admin_login.php');
}

// Get admin details
$admin_id = $_SESSION['admin_id'];

// Get all companies with their activity stats
$companies_stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM purchase_invoices WHERE company_id = c.id) as purchase_count,
           (SELECT COUNT(*) FROM sales_invoices WHERE company_id = c.id) as sales_count,
           (SELECT COUNT(*) FROM expenses WHERE company_id = c.id) as expense_count,
           (SELECT MAX(created_at) FROM purchase_invoices WHERE company_id = c.id) as last_purchase,
           (SELECT MAX(created_at) FROM sales_invoices WHERE company_id = c.id) as last_sale
    FROM companies c 
    ORDER BY c.created_at DESC
");
$companies_stmt->execute();
$companies = $companies_stmt->get_result();

// Get overall statistics
$stats_stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM companies) as total_companies,
        (SELECT COUNT(*) FROM purchase_invoices) as total_purchases,
        (SELECT COUNT(*) FROM sales_invoices) as total_sales,
        (SELECT COUNT(*) FROM expenses) as total_expenses,
        (SELECT COUNT(*) FROM companies WHERE DATE(created_at) = CURDATE()) as new_today,
        (SELECT COUNT(*) FROM companies WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as new_this_week,
        (SELECT COUNT(*) FROM notifications WHERE is_read = 0) as unread_notifications
");
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Handle company actions (view, delete)
if (isset($_GET['action']) && isset($_GET['company_id'])) {
    $company_id = intval($_GET['company_id']);
    $action = $_GET['action'];
    
    if ($action == 'view') {
        $_SESSION['viewing_company_id'] = $company_id;
        redirect('dashboard.php?admin_view=true');
    } elseif ($action == 'delete') {
        // Delete company and all related data
        $delete_stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
        $delete_stmt->bind_param("i", $company_id);
        if ($delete_stmt->execute()) {
            $success = "Company deleted successfully";
        } else {
            $error = "Failed to delete company";
        }
    }
}

// Handle notification sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_notification'])) {
    $notification_title = sanitize_input($_POST['notification_title']);
    $notification_message = sanitize_input($_POST['notification_message']);
    $notification_type = sanitize_input($_POST['notification_type']);
    $target_companies = isset($_POST['target_companies']) ? $_POST['target_companies'] : [];
    
    if (empty($notification_title) || empty($notification_message)) {
        $notification_error = "Please fill in all notification fields";
    } elseif (empty($target_companies)) {
        $notification_error = "Please select at least one company";
    } else {
        $success_count = 0;
        foreach ($target_companies as $company_id) {
            $stmt = $conn->prepare("INSERT INTO notifications (company_id, type, title, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $company_id, $notification_type, $notification_title, $notification_message);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        $notification_success = "Notification sent to $success_count companies successfully";
    }
}

// Get recent activities
$activities_stmt = $conn->prepare("
    (SELECT 'purchase' as type, invoice_number as ref, created_at, company_id FROM purchase_invoices ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'sale' as type, invoice_number as ref, created_at, company_id FROM sales_invoices ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'expense' as type, category as ref, created_at, company_id FROM expenses ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 10
");
$activities_stmt->execute();
$recent_activities = $activities_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #242424ff 0%, #545356ff 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        /* Header Styles */
.header {
    background: linear-gradient(135deg, #1b1414ff 0%, #2f2c2cff 100%);
    color: white;
    padding: 1.5rem 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-bottom: 1px solid rgba(255,255,255,0.3);
}

.header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
    z-index: 1;
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 2;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    text-decoration: none;
    text-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: transform 0.3s ease;
}

.logo:hover {
    transform: translateY(-2px);
}

.logo-icon {
    font-size: 2rem;
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    padding: 0.75rem 1.5rem;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.admin-info:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.admin-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,249,250,0.9));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e15252;
    font-weight: 700;
    font-size: 1.2rem;
    border: 3px solid rgba(255,255,255,0.4);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.admin-info:hover .admin-avatar {
    transform: scale(1.05);
    border-color: rgba(255,255,255,0.6);
}

.admin-info > div {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.admin-info > div > div:first-child {
    font-weight: 700;
    font-size: 1rem;
    color: white;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    margin-bottom: 2px;
}

.admin-info > div > div:last-child {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.9);
    font-weight: 500;
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.logout-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 0.5rem;
}

.logout-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.7s ease;
}

.logout-btn:hover::before {
    left: 100%;
}

.logout-btn:hover {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Add a subtle pulse animation to the header */
@keyframes headerGlow {
    0%, 100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
    50% { box-shadow: 0 4px 30px rgba(234, 88, 88, 0.25); }
}

.header {
    animation: headerGlow 4s ease-in-out infinite;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header {
        padding: 1rem 1.5rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .logo {
        font-size: 1.3rem;
    }
    
    .logo-icon {
        font-size: 1.8rem;
    }
    
    .admin-info {
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
        width: 100%;
        max-width: 300px;
    }
    
    .admin-info > div {
        align-items: center;
    }
    
    .logout-btn {
        margin-left: 0;
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 0.75rem 1rem;
    }
    
    .logo {
        font-size: 1.2rem;
        gap: 8px;
    }
    
    .logo-icon {
        font-size: 1.6rem;
    }
    
    .admin-info {
        padding: 0.75rem;
    }
    
    .admin-avatar {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }
    
    .logout-btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.85rem;
    }
}

/* Add a smooth transition for all interactive elements */
.logo,
.admin-info,
.admin-avatar,
.logout-btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Add a subtle background animation */
.header::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent,
        rgba(255,255,255,0.1),
        transparent
    );
    transform: rotate(45deg);
    animation: shimmer 8s infinite linear;
    z-index: 1;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}
        
        /* Main Layout */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Companies Section */
        .companies-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .search-box {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 10px;
            font-size: 0.9rem;
            width: 300px;
            background: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Companies Table */
        .companies-table {
            width: 100%;
            border-collapse: collapse;
        }

        .companies-table th {
            background: rgba(99, 102, 241, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--gray-light);
        }

        .companies-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .company-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .company-name {
            font-weight: 600;
            color: var(--dark);
        }

        .company-gstin {
            font-family: monospace;
            font-size: 0.8rem;
            color: var(--gray);
        }

        .company-location {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .activity-stats {
            display: flex;
            gap: 0.75rem;
        }

        .stat-badge {
            background: var(--gray-light);
            padding: 0.4rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .stat-badge.purchases { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .stat-badge.sales { background: rgba(16, 185, 129, 0.1); color: var(--secondary); }
        .stat-badge.expenses { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Notification Panel */
        .notification-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .notification-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.75rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-select {
            padding: 0.75rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-send {
            background: var(--secondary);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-send:hover {
            background: #0da271;
            transform: translateY(-2px);
        }

        /* Recent Activities */
        .activities-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .activity-icon.purchase { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .activity-icon.sale { background: rgba(16, 185, 129, 0.1); color: var(--secondary); }
        .activity-icon.expense { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Messages */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #7f1d1d;
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
            
            .companies-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .activity-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        /* Animations */
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

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <span class="logo-icon">📊</span>
                <span>Taxxpert Admin</span>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                    <div style="font-size: 0.8rem; color: var(--gray);">Administrator</div>
                </div>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success fade-in">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error fade-in">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-value"><?php echo $stats['total_companies']; ?></div>
                    <div class="stat-label">Total Companies</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">📥</div>
                    <div class="stat-value"><?php echo $stats['total_purchases']; ?></div>
                    <div class="stat-label">Purchase Invoices</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">📤</div>
                    <div class="stat-value"><?php echo $stats['total_sales']; ?></div>
                    <div class="stat-label">Sales Invoices</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value"><?php echo $stats['total_expenses']; ?></div>
                    <div class="stat-label">Expense Entries</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">🆕</div>
                    <div class="stat-value"><?php echo $stats['new_today']; ?></div>
                    <div class="stat-label">New Today</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?php echo $stats['new_this_week']; ?></div>
                    <div class="stat-label">New This Week</div>
                </div>
            </div>

            <!-- Companies List -->
            <div class="companies-section fade-in">
                <div class="section-header">
                    <h2 class="section-title">Registered Companies</h2>
                    <input type="text" class="search-box" placeholder="Search companies..." id="searchCompanies">
                </div>

                <?php if ($companies->num_rows > 0): ?>
                    <table class="companies-table" id="companiesTable">
                        <thead>
                            <tr>
                                <th>Company Details</th>
                                <th>Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($company = $companies->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="company-info">
                                            <div class="company-name"><?php echo htmlspecialchars($company['name']); ?></div>
                                            <div class="company-gstin"><?php echo htmlspecialchars($company['gstin']); ?></div>
                                            <div class="company-location"><?php echo htmlspecialchars($company['place_of_supply']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--gray);">
                                                Registered: <?php echo date('M j, Y', strtotime($company['created_at'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="activity-stats">
                                            <div class="stat-badge purchases">
                                                📥 <?php echo $company['purchase_count']; ?> Purchases
                                            </div>
                                            <div class="stat-badge sales">
                                                📤 <?php echo $company['sales_count']; ?> Sales
                                            </div>
                                            <div class="stat-badge expenses">
                                                💰 <?php echo $company['expense_count']; ?> Expenses
                                            </div>
                                        </div>
                                        <?php
                                        $last_purchase = $company['last_purchase'];
                                        $last_sale = $company['last_sale'];
                                        $last_activity = max($last_purchase, $last_sale);
                                        
                                        if ($last_activity): ?>
                                            <div style="font-size: 0.8rem; color: var(--gray); margin-top: 0.5rem;">
                                                Last activity: <?php echo date('M j, Y', strtotime($last_activity)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&company_id=<?php echo $company['id']; ?>" class="btn btn-view">
                                                👁️ View
                                            </a>
                                            <a href="?action=delete&company_id=<?php echo $company['id']; ?>" 
                                               class="btn btn-delete"
                                               onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($company['name']); ?>? This action cannot be undone.')">
                                                🗑️ Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">🏢</div>
                        <h3>No Companies Registered</h3>
                        <p>No companies have registered in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Notification Panel -->
            <div class="notification-panel fade-in">
                <h3 style="margin-bottom: 1.5rem; color: var(--dark);">Send Notification</h3>
                
                <?php if (isset($notification_success)): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($notification_success); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($notification_error)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($notification_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="notification-form">
                    <div class="form-group">
                        <label class="form-label">Notification Type</label>
                        <select name="notification_type" class="form-select" required>
                            <option value="general">General</option>
                            <option value="gst_reminder">GST Reminder</option>
                            <option value="payment_due">Payment Due</option>
                            <option value="tax_filing">Tax Filing</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="notification_title" class="form-control" placeholder="Enter notification title" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea name="notification_message" class="form-control" rows="3" placeholder="Enter notification message" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Target Companies</label>
                        <div class="checkbox-group">
                            <?php 
                            $companies->data_seek(0); // Reset pointer
                            while($company = $companies->fetch_assoc()): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="target_companies[]" value="<?php echo $company['id']; ?>">
                                    <span><?php echo htmlspecialchars($company['name']); ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <button type="submit" name="send_notification" class="btn-send">
                        📤 Send Notification
                    </button>
                </form>
            </div>

            <!-- Recent Activities -->
            <div class="activities-panel fade-in">
                <h3 style="margin-bottom: 1.5rem; color: var(--dark);">Recent Activities</h3>
                <div class="activity-list">
                    <?php if ($recent_activities->num_rows > 0): ?>
                        <?php while($activity = $recent_activities->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['type']; ?>">
                                    <?php 
                                    switch($activity['type']) {
                                        case 'purchase': echo '📥'; break;
                                        case 'sale': echo '📤'; break;
                                        case 'expense': echo '💰'; break;
                                    }
                                    ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php 
                                        switch($activity['type']) {
                                            case 'purchase': echo 'Purchase Invoice'; break;
                                            case 'sale': echo 'Sales Invoice'; break;
                                            case 'expense': echo 'Expense Entry'; break;
                                        }
                                        ?>: <?php echo htmlspecialchars($activity['ref']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--gray); padding: 2rem;">
                            No recent activities
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchCompanies').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#companiesTable tbody tr');
            
            rows.forEach(row => {
                const companyName = row.querySelector('.company-name').textContent.toLowerCase();
                const companyGSTIN = row.querySelector('.company-gstin').textContent.toLowerCase();
                
                if (companyName.includes(searchTerm) || companyGSTIN.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.fade-in');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>