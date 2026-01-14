<?php
// auth.php - Authentication Logic
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die("Access Denied: You do not have permission to view this page.");
    }
}

// Login Function
function loginUser($email, $password) {
    $db = Database::getInstance()->getConnection();
    
    // Using MD5 as per legacy requirement (NOT SECURE for modern apps, but requested)
    $hashed_password = md5($password);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['facility_id'] = $user['facility_id'];
            return true;
        }
    }
    return false;
}
?>
