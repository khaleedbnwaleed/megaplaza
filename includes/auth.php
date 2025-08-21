<?php
/**
 * Authentication Functions
 * Mega School Plaza Management System
 */

/**
 * Login user
 */
function login_user($email, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, email, password, role, status FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $user['id']);
            $update_stmt->execute();
            
            return ['success' => true, 'user' => $user];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

/**
 * Register user
 */
function register_user($data) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $data['email']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $query = "INSERT INTO users (first_name, last_name, email, password, phone, role, status, created_at) 
              VALUES (:first_name, :last_name, :email, :password, :phone, :role, 'active', NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $data['first_name']);
    $stmt->bindParam(':last_name', $data['last_name']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':role', $data['role']);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Registration successful'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

/**
 * Logout user
 */
function logout_user() {
    session_destroy();
    redirect('auth/login.php');
}

/**
 * Check user role
 */
function check_role($required_role) {
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
    
    $user_role = $_SESSION['user_role'];
    
    $role_hierarchy = [
        'tenant' => 1,
        'manager' => 2,
        'admin' => 3
    ];
    
    if ($role_hierarchy[$user_role] < $role_hierarchy[$required_role]) {
        redirect('dashboard/index.php?error=access_denied');
    }
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (is_logged_in() && isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            logout_user();
        }
    }
}
?>
