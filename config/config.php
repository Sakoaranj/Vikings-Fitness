<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fitness_gym');

// Simple URL definition for localhost
define('SITE_URL', 'http://localhost/fitness_gym');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');

// Initialize database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log($e->getMessage());
    die("Unable to connect to database. Please check if MySQL is running and try again.");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to redirect
function redirect($path) {
    $path = ltrim($path, '/');
    header("Location: " . SITE_URL . '/' . $path);
    exit;
}

// Function to get relative path
function getRelativePath($path) {
    return SITE_URL . '/' . ltrim($path, '/');
}

// Function to check if user should be redirected to dashboard
function checkDashboardRedirect() {
    if (isLoggedIn()) {
        $role = $_SESSION['user_role'];
        if (in_array($role, ['admin', 'staff', 'member'])) {
            redirect($role . '/dashboard.php');
        }
    }
}

/**
 * Get active plans with formatted details
 * @param bool $includeDeleted Whether to include soft-deleted plans
 * @return array Array of plan objects
 */
function getActivePlans($includeDeleted = false) {
    global $conn;
    
    $query = "SELECT p.*, 
              COALESCE(u.full_name, 'System') as created_by_name,
              (SELECT COUNT(*) FROM subscriptions s 
               WHERE s.plan_id = p.id 
               AND s.status = 'active') as active_subscribers
              FROM plans p 
              LEFT JOIN users u ON p.created_by = u.id
              WHERE 1=1 " . 
              (!$includeDeleted ? "AND p.deleted_at IS NULL " : "") . 
              "ORDER BY p.price ASC";
              
    $result = $conn->query($query);
    $plans = [];
    
    if ($result && $result->num_rows > 0) {
        while ($plan = $result->fetch_assoc()) {
            $plan['features'] = json_decode($plan['features'], true);
            $plan['formatted_price'] = '₱' . number_format($plan['price'], 2);
            $plan['duration_text'] = $plan['duration'] . ' ' . 
                                   ($plan['duration'] == 1 ? 'Month' : 'Months');
            $plans[] = $plan;
        }
    }
    
    return $plans;
}

/**
 * Get active plans with formatted details for staff
 * @return array Array of plan objects
 */
function getStaffPlans() {
    global $conn;
    
    $query = "SELECT p.*, 
              COALESCE(u.full_name, 'System') as created_by_name,
              (SELECT COUNT(*) FROM subscriptions s 
               WHERE s.plan_id = p.id 
               AND s.status = 'active') as active_subscribers
              FROM plans p 
              LEFT JOIN users u ON p.created_by = u.id
              WHERE p.deleted_at IS NULL 
              ORDER BY p.price ASC, p.name ASC";
              
    $result = $conn->query($query);
    
    if (!$result) {
        error_log("SQL Error in getStaffPlans: " . $conn->error);
        return [];
    }
    
    $plans = [];
    
    if ($result && $result->num_rows > 0) {
        while ($plan = $result->fetch_assoc()) {
            $plan['features'] = json_decode($plan['features'], true);
            $plan['formatted_price'] = '₱' . number_format($plan['price'], 2);
            $plan['duration_text'] = $plan['duration'] . ' ' . 
                                   ($plan['duration'] == 1 ? 'Month' : 'Months');
            $plans[] = $plan;
        }
    }
    
    return $plans;
}
?> 