<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

$user_info = getUserInfo();
$user_id = $user_info['id'];

$contact_id = $_GET['id'] ?? null;
if (!$contact_id) {
    header('Location: contacts.php');
    exit;
}

$errors = [];
$success = false;

try {
    $sql = "SELECT c.*, 
            CONCAT(u.firstname, ' ', u.lastname) as assigned_to_name,
            CONCAT(uc.firstname, ' ', uc.lastname) as created_by_name,
            u_assigned.id as assigned_to_id
            FROM contacts c 
            LEFT JOIN users u ON c.assigned_to = u.id 
            LEFT JOIN users uc ON c.created_by = uc.id 
            LEFT JOIN users u_assigned ON c.assigned_to = u_assigned.id
            WHERE c.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $contact_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Location: contacts.php');
        exit;
    }
    
    $contact = $stmt->fetch();
} catch(PDOException $e) {
    die("Error loading contact: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign_to_me') {
        try {
            $update_sql = "UPDATE contacts SET assigned_to = :user_id, updated_at = NOW() WHERE id = :id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([
                ':user_id' => $user_id,
                ':id' => $contact_id
            ]);
            
            $success = "Contact assigned to you!";
            $contact['assigned_to'] = $user_id;
            $contact['assigned_to_name'] = $user_info['fullname'];
        } catch(PDOException $e) {
            $errors[] = "Failed to assign contact: " . $e->getMessage();
        }
    }
    elseif ($action === 'switch_type') {
        $new_type = $contact['type'] === 'Sales Lead' ? 'Support' : 'Sales Lead';
        
        try {
            $update_sql = "UPDATE contacts SET type = :type, updated_at = NOW() WHERE id = :id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([
                ':type' => $new_type,
                ':id' => $contact_id
            ]);
            
            $success = "Contact type changed to " . $new_type;
            $contact['type'] = $new_type;
        } catch(PDOException $e) {
            $errors[] = "Failed to update type: " . $e->getMessage();
        }
    }
    elseif ($action === 'add_note') {
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($comment)) {
            $errors[] = "Note cannot be empty";
        } else {
            try {
                $note_sql = "INSERT INTO notes (contact_id, comment, created_by, created_at) 
                            VALUES (:contact_id, :comment, :created_by, NOW())";
                $note_stmt = $conn->prepare($note_sql);
                $note_stmt->execute([
                    ':contact_id' => $contact_id,
                    ':comment' => $comment,
                    ':created_by' => $user_id
                ]);
                
                $update_sql = "UPDATE contacts SET updated_at = NOW() WHERE id = :id";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->execute([':id' => $contact_id]);
                
                $success = "Note added successfully!";
                $_POST['comment'] = '';
            } catch(PDOException $e) {
                $errors[] = "Failed to add note: " . $e->getMessage();
            }
        }
    }
}

try {
    $notes_sql = "SELECT n.*, CONCAT(u.firstname, ' ', u.lastname) as author_name 
                  FROM notes n 
                  JOIN users u ON n.created_by = u.id 
                  WHERE contact_id = :contact_id 
                  ORDER BY n.created_at DESC";
    $notes_stmt = $conn->prepare($notes_sql);
    $notes_stmt->bindParam(':contact_id', $contact_id);
    $notes_stmt->execute();
    $notes = $notes_stmt->fetchAll();
} catch(PDOException $e) {
    $notes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Details - Dolphin CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styler.css">

    <style>                
        .btn-warning {
            background: orange;
            color: white;
        }
        
        .btn-warning:hover {
            background: #b9770e;
        }
        
        .btn-success {
            background: green;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .contact-title {
            flex: 1;
        }
        
        .contact-name {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .contact-meta {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .contact-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .contact-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-item label {
            font-weight: 600;
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-item span {
            font-size: 16px;
            color: #2c3e50;
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
        
        .badge-success {
            background-color: #d4edda;
            color: #27ae60;
        }
        
        .add-note-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .notes-list {
            margin-top: 20px;
        }
        
        .note-item {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .note-item:hover {
            background-color: #f8f9fa;
        }
        
        .note-item:last-child {
            border-bottom: none;
        }
        
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .note-author {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .note-date {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .note-content {
            line-height: 1.6;
            white-space: pre-wrap;
            color: #34495e;
            padding-left: 28px;
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
            background-color: white;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: blue;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
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
            .contact-header {
                flex-direction: column;
            }
            
            .contact-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .contact-info-grid {
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
                    <span><?php echo htmlspecialchars($user_info['fullname']); ?></span>
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
                        <a href="contacts.php" class="nav-link active">
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
                <h1 class="page-title">Contact Details</h1>
                <div class="header-actions">
                    <a href="contacts.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Home
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <div class="contact-header">
                        <div class="contact-title">
                            <h2 class="contact-name">
                                <i class="fas fa-user mr-2 text-blue-500"></i>
                                <?php echo htmlspecialchars($contact['title'] . ' ' . $contact['firstname'] . ' ' . $contact['lastname']); ?>
                            </h2>
                            <div class="contact-meta">
                                <span>
                                    <i class="fas fa-calendar-plus mr-1"></i>
                                    Created on <?php echo date('F j, Y', strtotime($contact['created_at'])); ?> 
                                    by <?php echo htmlspecialchars($contact['created_by_name'] ?? 'System'); ?>
                                </span>
                                <span class="ml-4">
                                    <i class="fas fa-sync-alt mr-1"></i>
                                    Last updated: <?php echo date('F j, Y, g:i a', strtotime($contact['updated_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="contact-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="assign_to_me">
                                <button type="submit" class="btn btn-primary btn-sm" 
                                    <?php echo $contact['assigned_to'] == $user_id ? 'disabled' : ''; ?>>
                                    <i class="fas fa-user-check"></i> 
                                    <?php echo $contact['assigned_to'] == $user_id ? 'Already Assigned' : 'Assign to Me'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="switch_type">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-exchange-alt"></i> 
                                    Switch to <?php echo $contact['type'] === 'Sales Lead' ? 'Support' : 'Sales Lead'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="contact-info-grid">
                        <div class="info-item">
                            <label><i class="fas fa-envelope mr-2"></i> Email Address</label>
                            <span>
                                <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" 
                                   class="text-blue-600 hover:text-blue-800 hover:underline">
                                    <?php echo htmlspecialchars($contact['email']); ?>
                                </a>
                            </span>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-phone mr-2"></i> Telephone</label>
                            <span>
                                <?php if ($contact['telephone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($contact['telephone']); ?>" 
                                   class="text-blue-600 hover:text-blue-800 hover:underline">
                                    <?php echo htmlspecialchars($contact['telephone']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-gray-500 italic">Not provided</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-building mr-2"></i> Company</label>
                            <span>
                                <?php echo htmlspecialchars($contact['company'] ?: 'Not provided'); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Contact Type</label>
                            <span>
                                <span class="badge <?php echo $contact['type'] === 'Sales Lead' ? 'badge-success' : 'badge-primary'; ?>">
                                    <?php echo htmlspecialchars($contact['type']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-user-tie mr-2"></i> Assigned To</label>
                            <span>
                                <?php if ($contact['assigned_to_name']): ?>
                                <span class="inline-flex items-center gap-2">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($contact['assigned_to_name'], 0, 1)); ?>
                                    </div>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($contact['assigned_to_name']); ?>
                                        <?php if ($contact['assigned_to'] == $user_id): ?>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">You</span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-500 italic">Unassigned</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-id-card mr-2"></i> Contact ID</label>
                            <span class="font-mono text-gray-600">#<?php echo htmlspecialchars($contact['id']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Contact Notes
                    </h3>
                    <span class="text-muted"><?php echo count($notes); ?> note<?php echo count($notes) !== 1 ? 's' : ''; ?></span>
                </div>
                <div class="card-body">
                    <div class="add-note-form">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_note">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    Add a note about <?php echo htmlspecialchars($contact['firstname']); ?>
                                </label>
                                <textarea name="comment" 
                                          class="form-control" 
                                          rows="4" 
                                          placeholder="Enter notes here..."
                                          required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                                
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Note
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="notes-list">
                        <?php if (count($notes) > 0): ?>
                            <?php foreach ($notes as $note): ?>
                            <div class="note-item">
                                <div class="note-header">
                                    <div class="note-author">
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($note['author_name'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($note['author_name']); ?></span>
                                        <?php if ($note['created_by'] == $user_id): ?>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="note-date">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo date('F j, Y \a\t g:i a', strtotime($note['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="note-content">
                                    <?php echo nl2br(htmlspecialchars($note['comment'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3 class="text-xl font-semibold mb-2">No notes added yet</h3>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const textarea = document.querySelector('textarea[name="comment"]');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Initialize textarea height
        textarea.dispatchEvent(new Event('input'));

        // Add some interactivity to the notes
        document.querySelectorAll('.note-item').forEach(note => {
            note.addEventListener('click', function() {
                this.classList.toggle('bg-blue-50');
            });
        });

        // Confirm before switching contact type
        document.querySelector('button[value="switch_type"]').addEventListener('click', function(e) {
            const currentType = "<?php echo $contact['type']; ?>";
            const newType = currentType === 'Sales Lead' ? 'Support' : 'Sales Lead';
            
            if (!confirm(`Are you sure you want to change this contact from ${currentType} to ${newType}?`)) {
                e.preventDefault();
            }
        });

        // Highlight assigned button if it's already assigned to current user
        const assignButton = document.querySelector('button[value="assign_to_me"]');
        if (assignButton.disabled) {
            assignButton.classList.add('bg-green-600', 'hover:bg-green-700');
            assignButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        }
    </script>
</body>
</html>