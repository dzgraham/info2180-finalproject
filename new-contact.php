<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_info = getUserInfo();
$user_id = $user_info['id'];
$user_name =  $user_info['firstname'] . ' ' . $user_info['lastname'];

$errors = [];
$success = false;
$contact_id = null;

try {
    $users_sql = "SELECT id, CONCAT(firstname, ' ', lastname) as name FROM users ORDER BY firstname";
    $users_stmt = $conn->query($users_sql);
    $users = $users_stmt->fetchAll();
} catch(PDOException $e) {
    $users = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request";
    }
    
    $title = trim($_POST['title'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    if (!empty($telephone) && !preg_match('/^\(\d{3}\) \d{3}-\d{4}$/', $telephone)) {
        $errors[] = "Invalid telephone format (use (123) 456-7890)";
    }
    $company = trim($_POST['company'] ?? '');
    $initial_comment = trim($_POST['initial_comment'] ?? '');
    $type = $_POST['type'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? null;
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($firstname)) $errors[] = "First name is required";
    if (empty($lastname)) $errors[] = "Last name is required";
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($type) || !in_array($type, ['Sales Lead', 'Support'])) {
        $errors[] = "Type is required";
    }
    
    if (empty($errors)) {
        try {
            $check_sql = "SELECT id FROM contacts WHERE email = :email";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "A contact with this email already exists";
            }
        } catch(PDOException $e) {
            $errors[] = "Error checking email";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO contacts 
                    (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at, updated_at) 
                    VALUES (:title, :firstname, :lastname, :email, :telephone, :company, :type, :assigned_to, :created_by, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email,
                ':telephone' => $telephone,
                ':company' => $company,
                ':type' => $type,
                ':assigned_to' => $assigned_to ?: null,
                ':created_by' => $user_id
            ]);
            
            $success = true;
            $contact_id = $conn->lastInsertId();
            
            // Insert initial comment if provided
            if (!empty($initial_comment)) {
                $note_sql = "INSERT INTO notes (contact_id, comment, created_by) VALUES (:contact_id, :comment, :created_by)";
                $note_stmt = $conn->prepare($note_sql);
                $note_stmt->execute([
                    ':contact_id' => $contact_id,
                    ':comment' => $initial_comment,
                    ':created_by' => $user_id
                ]);
            }
            
            $_POST = [];
            
        } catch(PDOException $e) {
            $errors[] = "Failed to create contact: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact - Dolphin CRM</title>
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
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        @media (max-width: 768px) {
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
                        <a href="new-contact.php" class="nav-link active">
                            <i class="fas fa-users"></i>
                            <span>New Contact</span>
                        </a>
                    </li>
                    <?php if ($user_info['role'] === 'Admin'): ?>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-user-cog"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
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
                <h1 class="page-title">Add a New Contact</h1>
                <div class="header-actions">
                    <a href="contacts.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Home
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                Contact created successfully! 
                <a href="contact-details.php?id=<?php echo $contact_id; ?>" class="font-semibold underline">
                    View contact details
                </a>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mt-2 ml-4 list-disc">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contact Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="contact-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Title</label>
                                <select name="title" class="form-control" required>
                                    <option value="">Select Title</option>
                                    <option value="Mr" <?php echo ($_POST['title'] ?? '') === 'Mr' ? 'selected' : ''; ?>>Mr</option>
                                    <option value="Mrs" <?php echo ($_POST['title'] ?? '') === 'Mrs' ? 'selected' : ''; ?>>Mrs</option>
                                    <option value="Ms" <?php echo ($_POST['title'] ?? '') === 'Ms' ? 'selected' : ''; ?>>Ms</option>
                                    <option value="Dr" <?php echo ($_POST['title'] ?? '') === 'Dr' ? 'selected' : ''; ?>>Dr</option>
                                    <option value="Prof" <?php echo ($_POST['title'] ?? '') === 'Prof' ? 'selected' : ''; ?>>Prof</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">First Name</label>
                                <input type="text" 
                                       name="firstname" 
                                       class="form-control" 
                                       required
                                       placeholder="Enter first name"
                                       value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Last Name</label>
                                <input type="text" 
                                       name="lastname" 
                                       class="form-control" 
                                       required
                                       placeholder="Enter last name"
                                       value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       class="form-control" 
                                       required
                                       placeholder="email@example.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telephone</label>
                                <input type="tel" 
                                       name="telephone" 
                                       class="form-control"
                                       placeholder="(123) 456-7890"
                                       value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Company</label>
                                <input type="text" 
                                       name="company" 
                                       class="form-control"
                                       placeholder="Company name"
                                       value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Contact Type</label>
                                <select name="type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="Sales Lead" <?php echo ($_POST['type'] ?? '') === 'Sales Lead' ? 'selected' : ''; ?>>Sales Lead</option>
                                    <option value="Support" <?php echo ($_POST['type'] ?? '') === 'Support' ? 'selected' : ''; ?>>Support</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assigned To</label>
                                <select name="assigned_to" class="form-control">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                        <?php echo ($_POST['assigned_to'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-plus-circle"></i> Additional Information</label>
                                <textarea name="initial_comment" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Add an initial note about this contact"><?php echo htmlspecialchars($_POST['initial_comment'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Create Contact
                            </button>
                            <a href="contacts.php" class="btn btn-secondary">Cancel</a>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.querySelector('.contact-form').addEventListener('submit', function(e) {
            const title = document.querySelector('select[name="title"]').value;
            const firstname = document.querySelector('input[name="firstname"]').value.trim();
            const lastname = document.querySelector('input[name="lastname"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const type = document.querySelector('select[name="type"]').value;
            
            let errors = [];
            
            if (!title) errors.push('Title is required');
            if (!firstname) errors.push('First name is required');
            if (!lastname) errors.push('Last name is required');
            if (!email) errors.push('Email is required');
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Invalid email format');
            if (!type) errors.push('Type is required');
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n' + errors.join('\n'));
            }
        });
    </script>
</body>
</html>