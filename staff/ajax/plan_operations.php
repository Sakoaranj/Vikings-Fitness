<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is staff
if (!isLoggedIn() || !hasRole('staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->begin_transaction();
        
        switch ($data['action']) {
            case 'edit':
                $id = $conn->real_escape_string($data['id']);
                $name = $conn->real_escape_string($data['name']);
                $duration = (int)$data['duration'];
                $price = (float)$data['price'];
                $features = array_filter(array_map('trim', explode("\n", $data['features'])));
                
                // Staff can only edit active plans
                $sql = "UPDATE plans 
                        SET name = '$name',
                            duration = $duration,
                            price = $price,
                            features = '" . json_encode($features) . "',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE id = $id 
                        AND deleted_at IS NULL";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error updating plan');
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