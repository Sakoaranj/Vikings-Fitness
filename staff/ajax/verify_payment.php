<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['member_id'])) {
        $member_id = $conn->real_escape_string($data['member_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get the latest subscription
            $query = "SELECT s.id, s.user_id, s.plan_id, p.price 
                     FROM subscriptions s 
                     JOIN plans p ON s.plan_id = p.id 
                     WHERE s.user_id = $member_id 
                     AND s.status IN ('active', 'pending') 
                     ORDER BY s.created_at DESC 
                     LIMIT 1";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $subscription = $result->fetch_assoc();
                
                // Update payment status
                $update_payment = "UPDATE payments 
                                 SET status = 'paid',
                                     verified_by = {$_SESSION['user_id']},
                                     verified_at = NOW(),
                                     updated_at = NOW()
                                 WHERE subscription_id = {$subscription['id']}";
                
                if ($conn->query($update_payment)) {
                    // Update subscription status
                    $update_subscription = "UPDATE subscriptions 
                                         SET status = 'active',
                                             updated_at = NOW() 
                                         WHERE id = {$subscription['id']}";
                    
                    $conn->query($update_subscription);
                    
                    $conn->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment verified successfully'
                    ]);
                } else {
                    throw new Exception('Failed to verify payment');
                }
            } else {
                throw new Exception('No active subscription found');
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Member ID is required'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
} 