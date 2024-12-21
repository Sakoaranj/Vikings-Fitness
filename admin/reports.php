<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('/login.php');
}

$current_page = 'reports';
$page_title = 'Reports';

// Get date range from query parameters or set defaults
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get reports data
$reports_data = [
    'total_revenue' => 0,
    'total_members' => 0,
    'active_subscriptions' => 0,
    'new_members' => 0,
    'payment_methods' => [],
    'subscription_plans' => []
];

// Get total revenue for the period
$revenue_query = "SELECT SUM(pl.price) as total 
                 FROM payments p 
                 JOIN subscriptions s ON p.subscription_id = s.id 
                 JOIN plans pl ON s.plan_id = pl.id 
                 WHERE p.status = 'verified' 
                 AND DATE(p.created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($revenue_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$revenue_result = $stmt->get_result();
$reports_data['total_revenue'] = $revenue_result->fetch_assoc()['total'] ?? 0;

// Get total active members
$members_query = "SELECT COUNT(*) as total FROM users 
                 WHERE role = 'member' 
                 AND status = 'active' 
                 AND permanently_deleted = 0";
$members_result = $conn->query($members_query);
$reports_data['total_members'] = $members_result->fetch_assoc()['total'];

// Get active subscriptions
$subs_query = "SELECT COUNT(*) as total FROM subscriptions 
               WHERE status = 'active' 
               AND start_date <= CURDATE() 
               AND end_date >= CURDATE()";
$subs_result = $conn->query($subs_query);
$reports_data['active_subscriptions'] = $subs_result->fetch_assoc()['total'];

// Get new members for the period
$new_members_query = "SELECT COUNT(*) as total FROM users 
                     WHERE role = 'member' 
                     AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($new_members_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$new_members_result = $stmt->get_result();
$reports_data['new_members'] = $new_members_result->fetch_assoc()['total'];

// Get payment methods distribution
$payment_methods_query = "SELECT payment_method, COUNT(*) as count 
                         FROM payments 
                         WHERE status = 'verified' 
                         AND DATE(created_at) BETWEEN ? AND ? 
                         GROUP BY payment_method";
$stmt = $conn->prepare($payment_methods_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$payment_methods_result = $stmt->get_result();
while ($row = $payment_methods_result->fetch_assoc()) {
    $reports_data['payment_methods'][$row['payment_method']] = $row['count'];
}

// Get subscription plans distribution
$plans_query = "SELECT p.name, COUNT(*) as count 
                FROM subscriptions s 
                JOIN plans p ON s.plan_id = p.id 
                WHERE s.status = 'active' 
                AND DATE(s.created_at) BETWEEN ? AND ? 
                GROUP BY p.id";
$stmt = $conn->prepare($plans_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$plans_result = $stmt->get_result();
while ($row = $plans_result->fetch_assoc()) {
    $reports_data['subscription_plans'][$row['name']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VikingsFit Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>

    <main>
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <!-- Date Range Filter -->
                        <div class="row">
                            <form class="col s12" method="GET">
                                <div class="row">
                                    <div class="input-field col s12 m5">
                                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                        <label for="start_date">Start Date</label>
                                    </div>
                                    <div class="input-field col s12 m5">
                                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                        <label for="end_date">End Date</label>
                                    </div>
                                    <div class="input-field col s12 m2">
                                        <button type="submit" class="btn blue waves-effect waves-light">
                                            Filter <i class="material-icons right">filter_list</i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row">
                            <div class="col s12 m6 l3">
                                <div class="card-panel blue white-text">
                                    <h5>Total Revenue</h5>
                                    <h4>₱<?php echo number_format($reports_data['total_revenue'], 2); ?></h4>
                                </div>
                            </div>
                            <div class="col s12 m6 l3">
                                <div class="card-panel green white-text">
                                    <h5>Active Members</h5>
                                    <h4><?php echo $reports_data['total_members']; ?></h4>
                                </div>
                            </div>
                            <div class="col s12 m6 l3">
                                <div class="card-panel orange white-text">
                                    <h5>Active Subscriptions</h5>
                                    <h4><?php echo $reports_data['active_subscriptions']; ?></h4>
                                </div>
                            </div>
                            <div class="col s12 m6 l3">
                                <div class="card-panel red white-text">
                                    <h5>New Members</h5>
                                    <h4><?php echo $reports_data['new_members']; ?></h4>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row">
                            <div class="col s12 m6">
                                <div class="card">
                                    <div class="card-content">
                                        <span class="card-title">Payment Methods Distribution</span>
                                        <canvas id="paymentMethodsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <div class="card">
                                    <div class="card-content">
                                        <span class="card-title">Subscription Plans Distribution</span>
                                        <canvas id="subscriptionPlansChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            M.AutoInit();

            // Payment Methods Chart
            new Chart(document.getElementById('paymentMethodsChart'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($reports_data['payment_methods'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($reports_data['payment_methods'])); ?>,
                        backgroundColor: ['#2196F3', '#4CAF50', '#FF9800', '#F44336']
                    }]
                }
            });

            // Subscription Plans Chart
            new Chart(document.getElementById('subscriptionPlansChart'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($reports_data['subscription_plans'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($reports_data['subscription_plans'])); ?>,
                        backgroundColor: ['#2196F3', '#4CAF50', '#FF9800', '#F44336']
                    }]
                }
            });
        });
    </script>

    <style>
        .card-panel {
            padding: 20px;
            border-radius: 4px;
        }
        .card-panel h5 {
            margin: 0;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .card-panel h4 {
            margin: 0;
            font-size: 2rem;
            font-weight: 500;
        }
        canvas {
            max-height: 300px;
        }
    </style>
</body>
</html> 