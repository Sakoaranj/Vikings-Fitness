<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $member_id = $conn->real_escape_string($_GET['id']);
    
    $query = "SELECT u.*, 
              s.status as subscription_status,
              s.id as subscription_id,
              p.name as plan_name,
              p.duration as plan_duration,
              p.price as plan_price,
              s.start_date,
              s.end_date,
              s.created_at as subscription_date,
              COALESCE(py.status, 'pending') as payment_status,
              py.payment_proof,
              py.payment_date,
              py.payment_method,
              py.verified_by,
              py.verified_at,
              CONCAT(staff.full_name) as verified_by_name
              FROM users u 
              LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status IN ('active', 'pending')
              LEFT JOIN plans p ON s.plan_id = p.id
              LEFT JOIN payments py ON s.id = py.subscription_id
              LEFT JOIN users staff ON py.verified_by = staff.id
              WHERE u.id = $member_id AND u.role = 'member'";
              
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $member = $result->fetch_assoc();
        
        // Build HTML for member details
        $html = "
        <div class='detail-section'>
            <h5>Personal Information</h5>
            <div class='detail-row'>
                <span class='detail-label'>Name:</span>
                <span class='detail-value'>{$member['full_name']}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Email:</span>
                <span class='detail-value'>{$member['email']}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Member Since:</span>
                <span class='detail-value'>" . date('F j, Y', strtotime($member['created_at'])) . "</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Status:</span>
                <span class='detail-value'>
                    <span class='status-text " . ($member['subscription_status'] === 'active' ? 'status-active' : 'status-expired') . "'>
                        " . ucfirst($member['subscription_status'] ?? 'Inactive') . "
                    </span>
                </span>
            </div>
        </div>";

        if ($member['subscription_status'] === 'active' || $member['subscription_status'] === 'pending') {
            $html .= "
            <div class='detail-section'>
                <h5>Current Subscription</h5>
                <div class='detail-row'>
                    <span class='detail-label'>Plan:</span>
                    <span class='detail-value'>{$member['plan_name']}</span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Duration:</span>
                    <span class='detail-value'>{$member['plan_duration']} days</span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Price:</span>
                    <span class='detail-value'>₱" . number_format($member['plan_price'], 2) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Start Date:</span>
                    <span class='detail-value'>" . date('F j, Y', strtotime($member['start_date'])) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>End Date:</span>
                    <span class='detail-value'>" . date('F j, Y', strtotime($member['end_date'])) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Payment Status:</span>
                    <span class='detail-value'>
                        <span class='status-text " . ($member['payment_status'] === 'paid' ? 'status-paid' : 'status-pending') . "'>
                            " . ucfirst($member['payment_status']) . "
                        </span>
                    </span>
                </div>";

                if ($member['payment_method']) {
                    $html .= "
                    <div class='detail-row'>
                        <span class='detail-label'>Payment Method:</span>
                        <span class='detail-value'>" . ucfirst($member['payment_method']) . "</span>
                    </div>";
                }

                if ($member['payment_date']) {
                    $html .= "
                    <div class='detail-row'>
                        <span class='detail-label'>Payment Date:</span>
                        <span class='detail-value'>" . date('F j, Y', strtotime($member['payment_date'])) . "</span>
                    </div>";
                }

                if ($member['payment_proof']) {
                    $html .= "
                    <div class='detail-row'>
                        <span class='detail-label'>Payment Proof:</span>
                        <span class='detail-value'>
                            <a href='" . SITE_URL . "/uploads/payment_proofs/" . $member['payment_proof'] . "' 
                               target='_blank' class='btn-small blue'>
                                <i class='material-icons left'>image</i>
                                View Proof
                            </a>
                        </span>
                    </div>";
                }

                if ($member['verified_by']) {
                    $html .= "
                    <div class='detail-row'>
                        <span class='detail-label'>Verified By:</span>
                        <span class='detail-value'>{$member['verified_by_name']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Verified At:</span>
                        <span class='detail-value'>" . date('F j, Y g:i A', strtotime($member['verified_at'])) . "</span>
                    </div>";
                }

            $html .= "</div>";
        }

        // Get subscription history
        $history_query = "SELECT s.*, p.name as plan_name, p.duration, p.price,
                         py.status as payment_status, py.payment_date, py.payment_method
                         FROM subscriptions s
                         JOIN plans p ON s.plan_id = p.id
                         LEFT JOIN payments py ON s.id = py.subscription_id
                         WHERE s.user_id = $member_id
                         ORDER BY s.created_at DESC
                         LIMIT 5";
        $history = $conn->query($history_query);

        if ($history && $history->num_rows > 0) {
            $html .= "
            <div class='detail-section'>
                <h5>Subscription History</h5>
                <table class='striped'>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Duration</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            while ($row = $history->fetch_assoc()) {
                $html .= "
                <tr>
                    <td>{$row['plan_name']}</td>
                    <td>{$row['duration']} days</td>
                    <td>" . date('M d, Y', strtotime($row['start_date'])) . "</td>
                    <td>" . date('M d, Y', strtotime($row['end_date'])) . "</td>
                    <td>
                        <span class='status-text " . ($row['payment_status'] === 'paid' ? 'status-paid' : 'status-pending') . "'>
                            " . ucfirst($row['payment_status']) . "
                        </span>
                    </td>
                </tr>";
            }
            
            $html .= "
                    </tbody>
                </table>
            </div>";
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Member not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
} 