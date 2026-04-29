<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$company_id = $_SESSION['company_id'];
$success = '';
$error = '';

// Handle marking notifications as read
if (isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $mark_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND company_id = ?");
    $mark_stmt->bind_param("ii", $notification_id, $company_id);
    
    if ($mark_stmt->execute()) {
        $success = "Notification marked as read";
    } else {
        $error = "Failed to mark notification as read";
    }
}

// Handle marking all as read
if (isset($_GET['mark_all_read'])) {
    $mark_all_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE company_id = ? AND is_read = 0");
    $mark_all_stmt->bind_param("i", $company_id);
    
    if ($mark_all_stmt->execute()) {
        $success = "All notifications marked as read";
    } else {
        $error = "Failed to mark all notifications as read";
    }
}

// Handle delete all notifications
if (isset($_GET['delete_all'])) {
    $delete_all_stmt = $conn->prepare("DELETE FROM notifications WHERE company_id = ?");
    $delete_all_stmt->bind_param("i", $company_id);
    
    if ($delete_all_stmt->execute()) {
        $success = "All notifications deleted successfully";
    } else {
        $error = "Failed to delete notifications";
    }
}

// Handle delete single notification
if (isset($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $delete_stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND company_id = ?");
    $delete_stmt->bind_param("ii", $notification_id, $company_id);
    
    if ($delete_stmt->execute()) {
        $success = "Notification deleted successfully";
    } else {
        $error = "Failed to delete notification";
    }
}

// Handle setting monthly notification preference
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_monthly_preference'])) {
    $monthly_only = isset($_POST['monthly_only']) ? 1 : 0;
    
    // Store preference in session or database (using session for simplicity)
    $_SESSION['monthly_notifications_only'] = $monthly_only;
    
    if ($monthly_only) {
        $success = "Monthly notification preference enabled. You'll only receive month-end notifications.";
    } else {
        $success = "Monthly notification preference disabled. You'll receive all notifications.";
    }
}

// Get notifications based on preference
$monthly_only = $_SESSION['monthly_notifications_only'] ?? false;

if ($monthly_only) {
    // Only show month-end notifications (assuming they have a specific type or title)
    $notifications_stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE company_id = ? 
        AND (type = 'month_end' OR title LIKE '%month end%' OR title LIKE '%monthly%')
        ORDER BY created_at DESC
        LIMIT 50
    ");
} else {
    // Show all notifications
    $notifications_stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE company_id = ? 
        ORDER BY created_at DESC
        LIMIT 50
    ");
}

$notifications_stmt->bind_param("i", $company_id);
$notifications_stmt->execute();
$notifications = $notifications_stmt->get_result();

// Get unread count
$unread_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE company_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $company_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread_count'];

// Get total notification count
$total_stmt = $conn->prepare("SELECT COUNT(*) as total_count FROM notifications WHERE company_id = ?");
$total_stmt->bind_param("i", $company_id);
$total_stmt->execute();
$total_count = $total_stmt->get_result()->fetch_assoc()['total_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Taxxpert</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
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
            flex-wrap: wrap;
            gap: 15px;
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

        .back-link:hover {
            text-decoration: underline;
        }

        /* Settings Panel */
        .settings-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .settings-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preference-form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-label {
            font-weight: 500;
            color: #2c3e50;
        }

        .save-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .save-btn:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        /* Notification List */
        .notifications-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .notification-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .notification-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-count {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .total-count {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .mark-all-btn {
            background: #3498db;
            color: white;
        }

        .mark-all-btn:hover {
            background: #2980b9;
        }

        .delete-all-btn {
            background: #e74c3c;
            color: white;
        }

        .delete-all-btn:hover {
            background: #c0392b;
        }

        .notification-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.3s ease;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
        }

        .notification-item:hover {
            background: #f1f3f4;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .notification-message {
            color: #7f8c8d;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #95a5a6;
        }

        .notification-time {
            font-size: 12px;
            color: #95a5a6;
        }

        .notification-actions-single {
            display: flex;
            gap: 10px;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 12px;
            text-decoration: underline;
            padding: 5px;
        }

        .mark-read-btn:hover {
            color: #2980b9;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 12px;
            text-decoration: underline;
            padding: 5px;
        }

        .delete-btn:hover {
            color: #c0392b;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: #e74c3c;
            border-radius: 50%;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #2c3e50;
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

        /* Responsive */
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
                font-size: 2rem;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notification-stats {
                flex-wrap: wrap;
            }
            
            .notification-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .notification-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .notification-actions-single {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="welcome-message">
                <h1>Taxxpert Notifications</h1>
                <p>Stay updated with important alerts and reminders</p>
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
            <li><a href="purchase_invoice.php">Purchase</a></li>
            <li><a href="sales_invoice.php">Sales</a></li>
            <li><a href="gst_summary.php">GST</a></li>
            <li><a href="income_tax_summary.php">Income Tax</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="notifications.php" class="active">Notifications</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Notifications</h1>
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

        <!-- Settings Panel -->
        <div class="settings-panel">
            <div class="settings-title">
                ⚙️ Notification Preferences
            </div>
            <form method="POST" class="preference-form">
                <div class="checkbox-group">
                    <input type="checkbox" id="monthly_only" name="monthly_only" 
                           <?php echo ($monthly_only) ? 'checked' : ''; ?>>
                    <label for="monthly_only" class="checkbox-label">
                        Show only month-end notifications
                    </label>
                </div>
                <button type="submit" name="set_monthly_preference" class="save-btn">
                    💾 Save Preference
                </button>
            </form>
        </div>

        <!-- Notifications Container -->
        <div class="notifications-container">
            <div class="notification-header">
                <div class="notification-stats">
                    <strong>Recent Notifications</strong>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-count"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                    <?php if ($total_count > 0): ?>
                        <span class="total-count"><?php echo $total_count; ?> total</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_count > 0): ?>
                    <div class="notification-actions">
                        <?php if ($unread_count > 0): ?>
                            <a href="?mark_all_read=1" class="action-btn mark-all-btn">
                                📝 Mark All as Read
                            </a>
                        <?php endif; ?>
                        <a href="?delete_all=1" class="action-btn delete-all-btn"
                           onclick="return confirm('Are you sure you want to delete ALL notifications? This action cannot be undone.')">
                            🗑️ Delete All
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($notifications->num_rows > 0): ?>
                <div class="notification-list">
                    <?php while($notification = $notifications->fetch_assoc()): 
                        $is_unread = !$notification['is_read'];
                    ?>
                        <div class="notification-item <?php echo $is_unread ? 'unread' : ''; ?>">
                            <?php if ($is_unread): ?>
                                <div class="unread-dot"></div>
                            <?php endif; ?>
                            
                            <div class="notification-content">
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </div>
                                
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                
                                <div class="notification-meta">
                                    <span class="notification-time">
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="notification-actions-single">
                                <?php if ($is_unread): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="mark-read-btn">
                                        Mark read
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $notification['id']; ?>" class="delete-btn"
                                   onclick="return confirm('Are you sure you want to delete this notification?')">
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🔔</div>
                    <h3>No Notifications</h3>
                    <p>
                        <?php if ($monthly_only): ?>
                            No month-end notifications available. Check back at the end of the month.
                        <?php else: ?>
                            You're all caught up! No notifications at the moment.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>