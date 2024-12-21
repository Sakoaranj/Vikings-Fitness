<?php
require_once '../config/config.php';

// Check if user is logged in and is a staff member
if (!isLoggedIn() || !hasRole('staff')) {
    redirect('/login.php');
}

$current_page = 'subscriptions';
$page_title = 'Manage Subscriptions';

// Get all subscriptions with related information
$query = "SELECT s.*, 
          u.username, u.full_name, 
          p.name as plan_name, p.duration, p.price as amount,
          py.status as payment_status
          FROM subscriptions s 
          JOIN users u ON s.user_id = u.id 
          JOIN plans p ON s.plan_id = p.id 
          LEFT JOIN payments py ON s.id = py.subscription_id
          ORDER BY s.created_at DESC";
$result = $conn->query($query);
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
                        <div class="card-title">Subscription Management</div>
                        
                        <!-- Search and Filter -->
                        <div class="row mb-0">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <i class="material-icons prefix">search</i>
                                    <input type="text" id="search-input" onkeyup="filterSubscriptions()">
                                    <label for="search-input">Search by member name...</label>
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="status-filter" onchange="filterSubscriptions()">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="pending">Pending</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                    <label>Filter by Status</label>
                                </div>
                            </div>
                        </div>

                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Plan</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Payment Status</th>
                                    <th class="center-align">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($subscription = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subscription['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></td>
                                        <td>
                                            <span class="status-text <?php 
                                                echo $subscription['status'] === 'active' ? 'status-active' : 
                                                    ($subscription['status'] === 'pending' ? 'status-pending' : 'status-expired'); 
                                            ?>">
                                                <?php echo ucfirst($subscription['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-text <?php 
                                                echo ($subscription['payment_status'] ?? 'pending') === 'paid' ? 'status-paid' : 'status-pending'; 
                                            ?>">
                                                <?php echo ucfirst($subscription['payment_status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td class="center-align">
                                            <div class="action-buttons">
                                                <a href="#" onclick="viewSubscriptionDetails(<?php echo $subscription['id']; ?>)"
                                                   class="btn-small blue waves-effect waves-light tooltipped"
                                                   data-position="top" data-tooltip="View Details">
                                                    <i class="material-icons">visibility</i>
                                                </a>
                                                <?php if ($subscription['status'] === 'pending'): ?>
                                                    <a href="#" onclick="verifySubscription(<?php echo $subscription['id']; ?>)"
                                                       class="btn-small green waves-effect waves-light tooltipped"
                                                       data-position="top" data-tooltip="Verify Subscription">
                                                        <i class="material-icons">check_circle</i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
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
            M.AutoInit();
        });

        function filterSubscriptions() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value.toLowerCase();
            const table = document.querySelector('table tbody');
            const rows = table.getElementsByTagName('tr');

            for (let row of rows) {
                const memberName = row.cells[0].textContent.toLowerCase();
                const status = row.cells[4].textContent.toLowerCase().trim();
                
                const matchesSearch = memberName.includes(searchInput);
                const matchesStatus = !statusFilter || status === statusFilter;

                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            }
        }

        function verifySubscription(subscriptionId) {
            if (confirm('Are you sure you want to verify this subscription?')) {
                processSubscription(subscriptionId, 'verify');
            }
        }

        function processSubscription(subscriptionId, action) {
            fetch('ajax/verify_subscription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subscription_id: subscriptionId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    M.toast({html: data.message, classes: 'green'});
                    setTimeout(() => location.reload(), 1000);
                } else {
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error processing subscription', classes: 'red'});
            });
        }
    </script>

    <style>
        /* Table styles */
        table td, table th {
            padding: 8px 16px !important;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .status-text {
            font-weight: 500;
            font-size: 0.9rem;
        }
        .status-active { color: #4CAF50; }
        .status-pending { color: #FF9800; }
        .status-expired { color: #9E9E9E; }
        .status-paid { color: #4CAF50; }
        .btn-small {
            padding: 0 8px;
            height: 24px;
            line-height: 24px;
        }
        .btn-small i {
            font-size: 1.2rem;
            line-height: 24px;
        }
        .card-title {
            font-size: 24px !important;
            font-weight: 300;
            margin-bottom: 20px;
        }
        .row.mb-0 {
            margin-bottom: 0;
        }
    </style>
</body>
</html> 