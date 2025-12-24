<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$user_info = getUserInfo();
$user_name =  $user_info['firstname'] . ' ' . $user_info['lastname'];

try {
    $sql = "SELECT id, firstname, lastname, email, role, created_at 
            FROM users 
            ORDER BY created_at DESC";
    $stmt = $conn->query($sql);
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Dolphin CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styler.css">
    <style>
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .data-table th {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #1a252f;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 20px;
        }
        
        .badge-primary {
            background-color: #e3f2fd;
            color: #3498db;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #27ae60;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .text-muted {
            color: #7f8c8d;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                margin-bottom: 20px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
        }
    </style>
</head>
<body>
    <nav class="main-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-water"></i>
                <span>Dolphin CRM</span>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="app-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="contacts.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="new-contact.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>New Contact</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link active">
                            <i class="fas fa-user-cog"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Users</h1>
                <div class="header-actions">
                    <a href="new-user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> New User
                    </a>
                </div>
            </div>

            <?php
            $admin_count = 0;
            $member_count = 0;
            foreach ($users as $user) {
                if ($user['role'] === 'Admin') $admin_count++;
                else $member_count++;
            }
            ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User List</h3>
                </div>
                <div class="card-body">
                    <?php if (count($users) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($user['role']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['created_at']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3 class="text-xl font-semibold mb-2">No users found</h3>
                        <a href="new-user.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>