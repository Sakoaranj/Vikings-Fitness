<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->begin_transaction();
        
        switch ($data['action']) {
            case 'add':
                $name = $conn->real_escape_string($data['name']);
                $duration = (int)$data['duration'];
                $price = (float)$data['price'];
                $features = json_encode($data['features']);
                $created_by = $_SESSION['user_id'];
                
                $sql = "INSERT INTO plans (name, duration, price, features, created_by) 
                        VALUES ('$name', $duration, $price, '$features', $created_by)";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error creating plan');
                }
                break;
                
            case 'update':
                $plan_id = (int)$data['plan_id'];
                $name = $conn->real_escape_string($data['name']);
                $duration = (int)$data['duration'];
                $price = (float)$data['price'];
                $features = json_encode($data['features']);
                
                $sql = "UPDATE plans 
                        SET name = '$name',
                            duration = $duration,
                            price = $price,
                            features = '$features'
                        WHERE id = $plan_id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error updating plan');
                }
                break;
                
            case 'delete':
                $plan_id = (int)$data['plan_id'];
                
                // Check if plan is being used
                $check_sql = "SELECT COUNT(*) as count 
                             FROM subscriptions 
                             WHERE plan_id = $plan_id 
                             AND status = 'active'";
                $result = $conn->query($check_sql);
                if ($result->fetch_assoc()['count'] > 0) {
                    throw new Exception('Cannot delete plan: Active subscriptions exist');
                }
                
                $sql = "UPDATE plans 
                        SET deleted_at = CURRENT_TIMESTAMP 
                        WHERE id = $plan_id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error deleting plan');
                }
                break;
                
            case 'edit':
                $id = $conn->real_escape_string($data['id']);
                $name = $conn->real_escape_string($data['name']);
                $duration = (int)$data['duration'];
                $price = (float)$data['price'];
                $features = array_filter(array_map('trim', explode("\n", $data['features'])));
                
                $sql = "UPDATE plans 
                        SET name = '$name',
                            duration = $duration,
                            price = $price,
                            features = '" . json_encode($features) . "',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE id = $id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error updating plan');
                }
                break;
                
            case 'deactivate':
                $id = $conn->real_escape_string($data['id']);
                
                // Check if plan has active subscriptions
                $check_sql = "SELECT COUNT(*) as count 
                              FROM subscriptions 
                              WHERE plan_id = $id 
                              AND status = 'active'";
                $result = $conn->query($check_sql);
                if ($result->fetch_assoc()['count'] > 0) {
                    throw new Exception('Cannot deactivate plan: Active subscriptions exist');
                }
                
                $sql = "UPDATE plans 
                        SET deleted_at = CURRENT_TIMESTAMP 
                        WHERE id = $id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error deactivating plan');
                }
                break;
                
            case 'activate':
                $id = $conn->real_escape_string($data['id']);
                
                $sql = "UPDATE plans 
                        SET deleted_at = NULL 
                        WHERE id = $id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error activating plan');
                }
                break;
                
            case 'delete':
                $plan_id = (int)$data['plan_id'];
                
                // Check if plan has any subscriptions
                $check_sql = "SELECT COUNT(*) as count 
                             FROM subscriptions 
                             WHERE plan_id = $plan_id";
                $result = $conn->query($check_sql);
                if ($result->fetch_assoc()['count'] > 0) {
                    throw new Exception('Cannot delete plan: Subscriptions exist');
                }
                
                $sql = "DELETE FROM plans WHERE id = $plan_id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error deleting plan');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 