<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

$user_info = getUserInfo();
$user_id = $user_info['id'];
$user_role = $user_info['role'];
$user_name =  $user_info['firstname'] . ' ' . $user_info['lastname'];

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT c.*, 
        CONCAT(u.firstname, ' ', u.lastname) as assigned_to_name,
        CONCAT(uc.firstname, ' ', uc.lastname) as created_by_name
        FROM contacts c 
        LEFT JOIN users u ON c.assigned_to = u.id 
        LEFT JOIN users uc ON c.created_by = uc.id 
        WHERE 1=1";

$params = [];

switch ($filter) {
    case 'sales':
        $sql .= " AND c.type = 'Sales Lead'";
        break;
    case 'support':
        $sql .= " AND c.type = 'Support'";
        break;
    case 'assigned':
        $sql .= " AND c.assigned_to = :user_id";
        $params[':user_id'] = $user_id;
        break;
}

if (!empty($search)) {
    $sql .= " AND (c.firstname LIKE :search OR c.lastname LIKE :search OR c.email LIKE :search OR c.company LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY c.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();
} catch(PDOException $e) {
    $contacts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts - Dolphin CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styler.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
                    <?php if ($user_role === 'Admin'): ?>
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
                <h1 class="page-title">Contacts</h1>
                <div class="header-actions">
                    <a href="new-contact.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Contact
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            All Contacts
                        </a>
                        <a href="?filter=sales" class="filter-tab <?php echo $filter === 'sales' ? 'active' : ''; ?>">
                            Sales Leads
                        </a>
                        <a href="?filter=support" class="filter-tab <?php echo $filter === 'support' ? 'active' : ''; ?>">
                            Support
                        </a>
                        <a href="?filter=assigned" class="filter-tab <?php echo $filter === 'assigned' ? 'active' : ''; ?>">
                            Assigned to Me
                        </a>
                    </div>
                    <form method="GET" class="search-box">
                        <input type="text" 
                               name="search" 
                               placeholder="Search contacts" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="search-input">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($filter !== 'all'): ?>
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contact List</h3>
                    <span class="text-muted"><?php echo count($contacts); ?> contacts found</span>
                </div>
                <div class="card-body">
                    <?php if (count($contacts) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Type</th>
                                    <th>Assigned To</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td>
                                        <a href="contact-details.php?id=<?php echo $contact['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            <?php echo htmlspecialchars($contact['title'] . ' ' . $contact['firstname'] . ' ' . $contact['lastname']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['company'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $contact['type'] === 'Sales Lead' ? 'badge-success' : 'badge-primary'; ?>">
                                            <?php echo htmlspecialchars($contact['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($contact['assigned_to_name']): ?>
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fas fa-user text-gray-500"></i>
                                            <?php echo htmlspecialchars($contact['assigned_to_name']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-500 italic">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($contact['created_at'])); ?><br>
                                        <small class="text-gray-500 text-xs">
                                            by <?php echo htmlspecialchars($contact['created_by_name'] ?? 'System'); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p class="mb-4"><?php echo !empty($search) ? 'Try a different search term' : 'No contacts found'; ?></p>
                        <a href="new-contact.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Contact
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>