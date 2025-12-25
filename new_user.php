<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/password_util.php';

requireAdmin();

$user_info = getUserInfo();
$user_name =  $user_info['firstname'] . ' ' . $user_info['lastname'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'Member';
    
    if (empty($firstname)) $errors[] = "First name is required";
    if (empty($lastname)) $errors[] = "Last name is required";
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    } else {
        $password_error = PasswordUtils::validate($password);
        if ($password_error) {
            $errors[] = $password_error;
        }
    }
    
    if (!in_array($role, ['Admin', 'Member'])) {
        $errors[] = "Invalid role selected";
    }
    
    if (empty($errors)) {
        try {
            $check_sql = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "A user with this email already exists";
            }
        } catch(PDOException $e) {
            $errors[] = "Error checking email";
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = PasswordUtils::hash($password);
            
            $sql = "INSERT INTO users (firstname, lastname, email, password, role) 
                    VALUES (:firstname, :lastname, :email, :password, :role)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email,
                ':password' => $hashed_password,
                ':role' => $role
            ]);
            
            $success = true;
            $user_id = $conn->lastInsertId();
            
            $_POST = [];
            
        } catch(PDOException $e) {
            $errors[] = "Failed to create user: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Dolphin CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styler.css">

    <style>        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-control.error {
            border-color: #e74c3c;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .password-rules {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        .password-rules ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .role-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .role-admin {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .role-member {
            background-color: #e3f2fd;
            color: #3498db;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
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
                <h1 class="page-title">Add a New User</h1>
                <div class="header-actions">
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                User created successfully! 
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <ul class="mt-2 ml-4 list-disc">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="user-form" id="userForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">First Name</label>
                                <input type="text" 
                                       name="firstname" 
                                       class="form-control" 
                                       required
                                       placeholder="Enter first name"
                                       value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>"
                                       id="firstname">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Last Name</label>
                                <input type="text" 
                                       name="lastname" 
                                       class="form-control" 
                                       required
                                       placeholder="Enter last name"
                                       value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>"
                                       id="lastname">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Email Address</label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   required
                                   placeholder="email@example.com"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   id="email">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Password</label>
                                <input type="password" 
                                       name="password" 
                                       class="form-control" 
                                       required
                                       placeholder="Enter password"
                                       id="password">
                                
                                                   
                                <div class="password-rules">
                                    <strong>Password Requirements:</strong>
                                    <ul>
                                        <li>At least 8 characters long</li>
                                        <li>At least one uppercase letter (A-Z)</li>
                                        <li>At least one lowercase letter (a-z)</li>
                                        <li>At least one number (0-9)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Confirm Password</label>
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-control" 
                                       required
                                       placeholder="Confirm password"
                                       id="confirm_password">
                                <div id="password-match" class="mt-2 text-sm"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">User Role</label>
                            <select name="role" class="form-control" required id="role">
                                <option value="Member" <?php echo ($_POST['role'] ?? 'Member') === 'Member' ? 'selected' : ''; ?>>Member</option>
                                <option value="Admin" <?php echo ($_POST['role'] ?? '') === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        
                            <small class="text-gray-500 text-sm mt-2 block">
                                <strong>Only admins</strong> can manage contacts, users, and system settings
                            </small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus"></i> Create User
                            </button>
                            <a href="users.php" class="btn btn-secondary" id = "reset">Cancel</a>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>