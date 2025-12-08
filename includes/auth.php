<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_firstname'] = $user['firstname'];
    $_SESSION['user_lastname'] = $user['lastname'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_fullname'] = $user['firstname'] . ' ' . $user['lastname'];
}

function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'firstname' => $_SESSION['user_firstname'] ?? '',
        'lastname' => $_SESSION['user_lastname'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'Member',
        'fullname' => $_SESSION['user_fullname'] ?? 'User'
    ];
}

function logoutUser() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>