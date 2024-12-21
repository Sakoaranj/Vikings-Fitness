<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input
        $account_name = $conn->real_escape_string($_POST['account_name']);
        $account_number = $conn->real_escape_string($_POST['account_number']);
        $account_type = $conn->real_escape_string($_POST['account_type']);
        $user_id = $_SESSION['user_id'];

        // Insert payment account
        $query = "INSERT INTO payment_accounts (
            account_name, 
            account_number, 
            account_type,
            created_by,
            created_at
        ) VALUES (
            '$account_name',
            '$account_number',
            '$account_type',
            $user_id,
            NOW()
        )";

        if ($conn->query($query)) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment account added successfully'
            ]);
        } else {
            throw new Exception('Failed to add payment account');
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