<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('/login.php');
}

$current_page = 'payments';
$page_title = 'Payment Management';

// Get all payments with member and subscription details
$query = "SELECT p.*, 
          u.full_name as member_name,
          u.email as member_email,
          s.start_date,
          s.end_date,
          p.payment_proof,
          p.verified,
          p.verified_at,
          CASE 
            WHEN s.status IS NULL THEN 'No Subscription'
            WHEN s.status = 'canceled' THEN 'Canceled'
            ELSE s.status
          END as subscription_status
          FROM payments p 
          JOIN subscriptions s ON p.subscription_id = s.id 
          JOIN users u ON s.user_id = u.id 
          JOIN plans pl ON s.plan_id = pl.id
          WHERE p.status IN ('pending', 'paid', 'canceled')
          ORDER BY p.created_at DESC";

$payments = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VikingsFit Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>

    <main>
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Payment Records</span>
                        
                        <!-- Search and Filter Section -->
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <i class="material-icons prefix">search</i>
                                    <input type="text" id="search-input" onkeyup="filterPayments()">
                                    <label for="search-input">Search by member name or email...</label>
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="status-filter" onchange="filterPayments()">
                                        <option value="">All Status</option>
                                        <option value="paid">Paid</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                    <label>Filter by Status</label>
                                </div>
                            </div>
                        </div>

                        <!-- Payments Table -->
                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                    <th>Payment Proof</th>
                                    <th>Verified By</th>
                                    <th class="center-align">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments && $payments->num_rows > 0): ?>
                                    <?php while($payment = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($payment['member_name']); ?><br>
                                                <small class="grey-text"><?php echo htmlspecialchars($payment['member_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                            <td>₱<?php echo number_format($payment['plan_price'], 2); ?></td>
                                            <td><?php echo $payment['payment_date'] !== 'N/A' ? date('M d, Y', strtotime($payment['payment_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <span class="status-text <?php echo $payment['status'] === 'paid' ? 'status-paid' : 'status-pending'; ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                            <td>
                                                <?php if ($payment['payment_method'] !== 'cash'): ?>
                                                    <?php if ($payment['payment_proof']): ?>
                                                        <button class="btn-small blue" onclick="viewPaymentProof(<?php echo $payment['id']; ?>)">
                                                            <i class="material-icons">receipt</i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="red-text">No proof uploaded</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="grey-text">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['verified_by_name']); ?></td>
                                            <td class="center-align">
                                                <?php if ($payment['payment_method'] !== 'cash' && !$payment['verified'] && $payment['payment_proof']): ?>
                                                    <button class="btn-small green verify-btn" onclick="verifyPayment(<?php echo $payment['id']; ?>, 'verify')">
                                                        <i class="material-icons">check</i>
                                                    </button>
                                                    <button class="btn-small red reject-btn" onclick="verifyPayment(<?php echo $payment['id']; ?>, 'reject')">
                                                        <i class="material-icons">close</i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-small blue-grey" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                                    <i class="material-icons">info</i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="center-align">No payments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="payment-details-modal" class="modal">
        <div class="modal-content">
            <h4>Payment Details</h4>
            <div id="payment-details-content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-blue btn-flat">Close</a>
        </div>
    </div>

    <div id="payment-proof-modal" class="modal">
        <div class="modal-content">
            <h4>Payment Proof</h4>
            <div class="payment-proof-container">
                <img id="payment-proof-image" src="" alt="Payment Proof" class="responsive-img materialboxed">
            </div>
            <div id="verification-buttons" class="verification-buttons center-align" style="margin-top: 20px;">
                <button class="btn green" onclick="verifyPayment(currentPaymentId, 'verify')">
                    <i class="material-icons left">check</i>Verify Payment
                </button>
                <button class="btn red" onclick="verifyPayment(currentPaymentId, 'reject')">
                    <i class="material-icons left">close</i>Reject Payment
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-close waves-effect waves-grey btn-flat">Close</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize select
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);

            // Initialize tooltips
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);
        });

        let currentPaymentId = null;

        function viewPaymentProof(paymentId) {
            currentPaymentId = paymentId;
            fetch(`${SITE_URL}/admin/ajax/get_payment_details.php?id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payment_proof) {
                        const proofImg = document.getElementById('payment-proof-image');
                        proofImg.src = `${SITE_URL}/uploads/payment_proofs/${data.payment_proof}`;
                        
                        // Show/hide verification buttons based on payment status
                        const verificationButtons = document.getElementById('verification-buttons');
                        verificationButtons.style.display = data.verified ? 'none' : 'block';
                        
                        const modal = document.getElementById('payment-proof-modal');
                        M.Modal.getInstance(modal).open();
                        
                        // Initialize materialbox for zoom functionality
                        M.Materialbox.init(proofImg);
                    } else {
                        M.toast({html: 'Payment proof not found', classes: 'red'});
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    M.toast({html: 'Error loading payment proof', classes: 'red'});
                });
        }

        function verifyPayment(paymentId, action) {
            const confirmMsg = action === 'verify' ? 
                'Are you sure you want to verify this payment?' : 
                'Are you sure you want to reject this payment?';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            fetch(`${SITE_URL}/admin/ajax/verify_payment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    payment_id: paymentId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    M.toast({html: data.message, classes: 'green'});
                    // Close modal if open
                    const modal = document.getElementById('payment-proof-modal');
                    M.Modal.getInstance(modal).close();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error processing payment verification', classes: 'red'});
            });
        }

        function filterPayments() {
            const search = document.getElementById('search-input').value.toLowerCase();
            const status = document.getElementById('status-filter').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const memberName = row.cells[0].textContent.toLowerCase();
                const paymentStatus = row.cells[4].textContent.toLowerCase().trim();
                const matchesSearch = memberName.includes(search);
                const matchesStatus = !status || paymentStatus.includes(status);
                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        function viewPaymentDetails(paymentId) {
            fetch(`${SITE_URL}/admin/ajax/get_payment_details.php?id=${paymentId}`)
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            document.getElementById('payment-details-content').innerHTML = data.html;
                            const modal = document.getElementById('payment-details-modal');
                            M.Modal.getInstance(modal).open();
                        } else {
                            M.toast({html: data.message || 'Failed to load payment details', classes: 'red'});
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                        M.toast({html: 'Error loading payment details', classes: 'red'});
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    M.toast({html: 'Error connecting to server', classes: 'red'});
                });
        }
    </script>

    <style>
        .status-text {
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 3px;
            display: inline-block;
        }
        .status-paid {
            color: #4CAF50;
        }
        .status-pending {
            color: #FF9800;
        }
        .btn-small {
            padding: 0 0.8rem;
            margin: 0 2px;
        }
        .btn-small i {
            font-size: 1.2rem;
            line-height: inherit;
        }
        td small {
            display: block;
            line-height: 1.2;
        }
        .btn-small.grey {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .modal {
            max-width: 600px;
            border-radius: 8px;
        }
        .modal .modal-content {
            padding: 24px;
        }
        .modal h4 {
            margin-top: 0;
            color: #1976d2;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        .detail-section {
            margin-bottom: 20px;
        }
        .detail-section h5 {
            color: #1976d2;
            font-size: 1.2rem;
            margin: 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row {
            display: flex;
            margin: 8px 0;
            align-items: center;
        }
        .detail-label {
            width: 140px;
            font-weight: 500;
            color: #757575;
        }
        .detail-value {
            flex: 1;
        }
        .payment-proof-container {
            max-height: 70vh;
            overflow: auto;
            text-align: center;
        }
        .payment-proof-container img {
            max-width: 100%;
            height: auto;
        }
        .verification-buttons button {
            margin: 0 10px;
        }
    </style>
</body>
</html>