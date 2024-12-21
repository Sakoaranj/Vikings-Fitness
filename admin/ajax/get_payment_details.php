<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $payment_id = $conn->real_escape_string($_GET['id']);
    
    $query = "SELECT p.*, 
              u.full_name as member_name,
              u.email as member_email,
              s.start_date,
              s.end_date,
              pl.name as plan_name,
              pl.duration as plan_duration,
              pl.price as plan_price,
              CONCAT(staff.full_name) as verified_by_name,
              p.payment_proof
              FROM payments p
              JOIN subscriptions s ON p.subscription_id = s.id
              JOIN users u ON s.user_id = u.id
              JOIN plans pl ON s.plan_id = pl.id
              LEFT JOIN users staff ON p.verified_by_id = staff.id
              WHERE p.id = $payment_id";
              
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $payment = $result->fetch_assoc();
        
        $html = "
        <div class='detail-section'>
            <h5>Member Information</h5>
            <div class='detail-row'>
                <span class='detail-label'>Name:</span>
                <span class='detail-value'>{$payment['member_name']}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Email:</span>
                <span class='detail-value'>{$payment['member_email']}</span>
            </div>
        </div>

        <div class='detail-section'>
            <h5>Payment Information</h5>
            <div class='detail-row'>
                <span class='detail-label'>Plan:</span>
                <span class='detail-value'>{$payment['plan_name']} ({$payment['plan_duration']} days)</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Amount:</span>
                <span class='detail-value'>₱" . number_format($payment['plan_price'], 2) . "</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Payment Date:</span>
                <span class='detail-value'>" . ($payment['payment_date'] ? date('F j, Y g:i A', strtotime($payment['payment_date'])) : 'N/A') . "</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Payment Method:</span>
                <span class='detail-value'>" . ucfirst($payment['payment_method']) . "</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Status:</span>
                <span class='detail-value'>
                    <span class='status-text " . ($payment['status'] === 'paid' ? 'status-paid' : 'status-pending') . "'>
                        " . ucfirst($payment['status']) . "
                    </span>
                </span>
            </div>";

        if ($payment['verified_by_name']) {
            $html .= "
            <div class='detail-row'>
                <span class='detail-label'>Verified By:</span>
                <span class='detail-value'>{$payment['verified_by_name']}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Verified At:</span>
                <span class='detail-value'>" . date('F j, Y g:i A', strtotime($payment['verified_at'])) . "</span>
            </div>";
        }

        if ($payment['payment_proof']) {
            $html .= "
            <div class='detail-row'>
                <span class='detail-label'>Payment Proof:</span>
                <span class='detail-value'>
                    <a href='" . SITE_URL . "/uploads/payment_proofs/" . $payment['payment_proof'] . "' 
                       target='_blank' class='btn-small blue'>
                        <i class='material-icons left'>image</i>
                        View Proof
                    </a>
                </span>
            </div>";
        }

        if ($payment['verified']) {
            $verifier_query = "SELECT full_name FROM users WHERE id = {$payment['verified_by']}";
            $verifier = $conn->query($verifier_query)->fetch_assoc();
            
            $html .= "<div class='detail-section'>";
            $html .= "<h5>Verification Details</h5>";
            $html .= "<div class='detail-row'>";
            $html .= "<span class='detail-label'>Status:</span>";
            $html .= "<span class='detail-value status-active'>";
            $html .= "<i class='material-icons tiny'>verified</i> Verified";
            $html .= "</span></div>";
            $html .= "<div class='detail-row'>";
            $html .= "<span class='detail-label'>Verified By:</span>";
            $html .= "<span class='detail-value'>" . htmlspecialchars($verifier['full_name']) . "</span>";
            $html .= "</div>";
            $html .= "<div class='detail-row'>";
            $html .= "<span class='detail-label'>Verified At:</span>";
            $html .= "<span class='detail-value'>" . date('F j, Y g:i A', strtotime($payment['verified_at'])) . "</span>";
            $html .= "</div>";
            $html .= "</div>";
        }

        $html .= "</div>";
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'payment_proof' => $payment['payment_proof']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
} 