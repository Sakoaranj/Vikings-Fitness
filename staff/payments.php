<?php
require_once '../config/config.php';

// Check if user is logged in and is a staff member
if (!isLoggedIn() || !hasRole('staff')) {
    redirect('/login.php');
}

$current_page = 'payments';
$page_title = 'Payment Management';

// Get all payments with member and subscription details
$query = "SELECT p.id, 
          p.subscription_id,
          p.status,
          p.payment_proof,
          p.created_at,
          COALESCE(p.payment_date, p.created_at) as payment_date,
          COALESCE(p.payment_method, 'Online') as payment_method,
          p.verified_by,
          p.verified_at,
          u.full_name as member_name,
          u.email as member_email,
          s.start_date,
          s.end_date,
          pl.name as plan_name,
          pl.price as plan_price,
          s.id as subscription_id,
          CONCAT(staff.full_name) as verified_by_name
          FROM payments p
          JOIN subscriptions s ON p.subscription_id = s.id
          JOIN users u ON s.user_id = u.id
          JOIN plans pl ON s.plan_id = pl.id
          LEFT JOIN users staff ON p.verified_by = staff.id
          WHERE u.permanently_deleted = 0
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
    <?php include '../includes/staff_nav.php'; ?>

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
                                            <td><?php echo $payment['verified_by_name'] ?? 'Not Verified'; ?></td>
                                            <td class="center-align">
                                                <?php if ($payment['payment_proof']): ?>
                                                    <a href="<?php echo SITE_URL; ?>/uploads/payment_proofs/<?php echo $payment['payment_proof']; ?>" 
                                                       target="_blank"
                                                       class="btn-small blue waves-effect waves-light tooltipped"
                                                       data-position="top" 
                                                       data-tooltip="View Proof">
                                                        <i class="material-icons">image</i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <a href="#" 
                                                       onclick="verifyPayment(<?php echo $payment['subscription_id']; ?>)"
                                                       class="btn-small green waves-effect waves-light tooltipped"
                                                       data-position="top" 
                                                       data-tooltip="Verify Payment">
                                                        <i class="material-icons">verified</i>
                                                    </a>
                                                <?php endif; ?>
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

        function verifyPayment(subscriptionId) {
            if (!confirm('Are you sure you want to verify this payment?')) {
                return;
            }

            fetch(`${SITE_URL}/staff/ajax/verify_payment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    member_id: subscriptionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    M.toast({html: data.message, classes: 'green'});
                    setTimeout(() => location.reload(), 1000);
                } else {
                    M.toast({html: data.message || 'Error verifying payment', classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error verifying payment', classes: 'red'});
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
    </script>

    <style>
        .status-text {
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 3px;
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
    </style>
</body>
</html> 