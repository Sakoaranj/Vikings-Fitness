<?php
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false, 'message' => 'Invalid request']

    if (isset($data['action'])) {
        try {
            $conn->begin_transaction();
            
            switch ($data['action']) {
                case 'add':
                    $response = addMember($conn, $data);
                    break;
                case 'edit':
                    $response = editMember($conn, $data);
                    break;
                case 'get':
                    $response = getMemberDetails($conn, $data);
                    break;
                case 'toggle_status':
                    $response = toggleMemberStatus($conn, $data);
                    break;
                case 'delete':
                    $member_id = (int)$data['id'];
                    
                    try {
                        // Start transaction
                        $conn->begin_transaction();
                        
                        // Check if member exists and is actually a member
                        $check_member = "SELECT id FROM users WHERE id = ? AND role = 'member'";
                        $stmt = $conn->prepare($check_member);
                        $stmt->bind_param("i", $member_id);
                        $stmt->execute();
                        if ($stmt->get_result()->num_rows === 0) {
                            throw new Exception('Member not found');
                        }
                        
                        // Due to ON DELETE CASCADE in our foreign keys, 
                        // deleting the user will automatically delete related records
                        $delete_member = "DELETE FROM users WHERE id = ? AND role = 'member'";
                        $stmt = $conn->prepare($delete_member);
                        $stmt->bind_param("i", $member_id);
                        
                        if (!$stmt->execute()) {
                        throw new Exception('Error deleting member');
                    }
                    
                        // Commit transaction
                        $conn->commit();
                    $response = ['success' => true, 'message' => 'Member deleted successfully'];
                        
                    } catch (Exception $e) {
                        // Rollback on error
                        $conn->rollback();
                        $response = ['success' => false, 'message' => $e->getMessage()];
                    }
                    break;
                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
    }

    echo json_encode($response);
    exit;
}

function addMember($conn, $data) {
    // Validate required fields
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        return ['success' => false, 'message' => 'All fields are required'];
    }

    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND permanently_deleted = 0";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $data['username'], $data['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }

    // Insert new member
    $query = "INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, 'member', 'active')";
    $stmt = $conn->prepare($query);
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $data['username'], $data['email'], $hashed_password, $data['full_name']);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Member added successfully'];
    }

    // Provide detailed error message on failure
    return ['success' => false, 'message' => 'Error adding member: ' . $stmt->error];
}

function addMember($conn, $data) {
    // Validate required fields
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        return ['success' => false, 'message' => 'All fields are required'];
    }

    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND permanently_deleted = 0";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $data['username'], $data['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }

    // Insert new member
    $query = "INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, 'member', 'active')";
    $stmt = $conn->prepare($query);
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $data['username'], $data['email'], $hashed_password, $data['full_name']);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Member added successfully'];
    }

    // Provide detailed error message on failure
    return ['success' => false, 'message' => 'Error adding member: ' . $stmt->error];
}

    // Update member
    $query = "UPDATE users SET username = ?, email = ?, full_name = ? WHERE id = ? AND role = 'member'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $data['username'], $data['email'], $data['full_name'], $data['id']);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Member updated successfully'];
    }

    return ['success' => false, 'message' => 'Error updating member'];
}

function getMemberDetails($conn, $data) {
    if (empty($data['id'])) {
        return ['success' => false, 'message' => 'Member ID is required'];
    }

    $query = "SELECT id, username, email, full_name, status FROM users WHERE id = ? AND role = 'member' AND permanently_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($member = $result->fetch_assoc()) {
        return ['success' => true, 'data' => $member];
    }

    return ['success' => false, 'message' => 'Member not found'];
}

function toggleMemberStatus($conn, $data) {
    if (empty($data['id']) || empty($data['status'])) {
        return ['success' => false, 'message' => 'Invalid request'];
    }

    $new_status = $data['status'] === 'active' ? 'inactive' : 'active';
    $query = "UPDATE users SET status = ? WHERE id = ? AND role = 'member'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $data['id']);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Member status updated successfully'];
    }

    return ['success' => false, 'message' => 'Error updating member status'];
}

function deleteMember($conn, $data) {
    if (empty($data['id'])) {
        return ['success' => false, 'message' => 'Member ID is required'];
    }

    $query = "UPDATE users SET permanently_deleted = 1 WHERE id = ? AND role = 'member'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $data['id']);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Member deleted successfully'];
    }

    return ['success' => false, 'message' => 'Error deleting member'];
}