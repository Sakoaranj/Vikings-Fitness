<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate input
        if (!isset($_POST['account_id'], $_POST['account_type'], $_POST['account_name'], $_POST['account_number'])) {
            throw new Exception('Missing required fields');
        }

        $account_id = $conn->real_escape_string($_POST['account_id']);
        $account_type = $conn->real_escape_string($_POST['account_type']);
        $account_name = $conn->real_escape_string($_POST['account_name']);
        $account_number = $conn->real_escape_string($_POST['account_number']);
        $user_id = $_SESSION['user_id'];
        
        // Check if account exists
        $check_sql = "SELECT id FROM payment_accounts WHERE id = $account_id AND deleted_at IS NULL";
        $check_result = $conn->query($check_sql);
        
        if (!$check_result || $check_result->num_rows === 0) {
            throw new Exception('Account not found');
        }
        
        // Check if account number exists (excluding current account)
        $duplicate_check = "SELECT id FROM payment_accounts 
                          WHERE account_number = '$account_number' 
                          AND id != $account_id 
                          AND deleted_at IS NULL";
        if ($conn->query($duplicate_check)->num_rows > 0) {
            throw new Exception('Account number already exists');
        }
        
        // Update account
        $sql = "UPDATE payment_accounts SET 
                account_type = '$account_type',
                account_name = '$account_name',
                account_number = '$account_number',
                updated_at = NOW()
                WHERE id = $account_id";
        
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment account updated successfully'
            ]);
        } else {
            throw new Exception('Error updating payment account: ' . $conn->error);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}