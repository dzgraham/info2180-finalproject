<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';

$email = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.html?error=Please fill in all fields');
    exit;
}

try {
    $sql = "SELECT id, firstname, lastname, email, password, role FROM users WHERE email = :email";
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
    
    header('Location: login.php?error=Invalid email or password');
    exit;
    
} catch(PDOException $e) {
    header('Location: login.php?error=Login failed. Please try again.');
    exit;
}
?>