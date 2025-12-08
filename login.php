<?php
require_once 'includes/database.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_firstname'] = $user['firstname'];
                    $_SESSION['user_lastname'] = $user['lastname'];
                    $_SESSION['user_email'] = $user['email'];      
                    $_SESSION['user_role'] = $user['role'];
                    
                    header('Location: contacts.php');
                    exit;
                }
            }
            
            $error = "Invalid email or password";
            
        } catch(PDOException $e) {
            $error = "Login failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolphin CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="login_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="text-white px-6 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center space-x-5">
            <i class="fa-solid fa-water"></i>
            <h1 class="text-2xl font-bold">Dolphin CRM</h1>
        </div>
    </nav>

    <div class="login-wrapper">
        <div class="login-card">
            <h2 class="card-title">Login</h2>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div>
                    <input 
                        placeholder="Email Address" 
                        type="email" 
                        id="username" 
                        name="username" 
                        required 
                        class="form-input"
                        value="<?php echo htmlspecialchars($email); ?>"
                    >
                </div>
                <div>
                    <input placeholder="Password" type="password" id="password" name="password" required class="form-input">
                </div>
                <button type="submit" id="loginbtn" class="btn-login">
                    <i class="fas fa-lock mr-2"></i>
                    Login
                </button>
            </form>
            
        </div>
    </div>

    <footer class="text-white py-8 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p class="text-gray-400">Copyright &copy; <?php echo date('Y'); ?> Dolphin CRM</p>
        </div>
    </footer>
</body>
</html>