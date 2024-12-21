<?php
require_once '../config/config.php';

// Check if user is logged in and is a member
if (!isLoggedIn() || !hasRole('member')) {
    redirect('/login.php');
}

$current_page = 'plans';
$page_title = 'Membership Plans';

// Check for active subscription
$user_id = $_SESSION['user_id'];
$active_sub_query = "SELECT s.*, p.name as plan_name, 
                            DATEDIFF(s.end_date, CURRENT_DATE()) as days_remaining 
                     FROM subscriptions s 
                     JOIN plans p ON s.plan_id = p.id 
                     WHERE s.user_id = $user_id 
                     AND s.status = 'active' 
                     AND s.end_date >= CURRENT_DATE()";
$active_sub_result = $conn->query($active_sub_query);
$has_active_subscription = $active_sub_result && $active_sub_result->num_rows > 0;
$active_subscription = $has_active_subscription ? $active_sub_result->fetch_assoc() : null;

// Get available plans
$plans_query = "SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price ASC";
$plans = $conn->query($plans_query);

// Get payment accounts for online payment
$payment_accounts_query = "SELECT * FROM payment_accounts WHERE is_active = 1 AND deleted_at IS NULL ORDER BY account_type";
$payment_accounts = $conn->query($payment_accounts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VikingsFit Gym</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body>
    <?php include '../includes/member_nav.php'; ?>

    <main>
        <?php if ($has_active_subscription): ?>
            <div class="row">
                <div class="col s12">
                    <div class="card orange lighten-4">
                        <div class="card-content">
                            <span class="card-title">Active Subscription</span>
                            <p>You currently have an active subscription to the <strong><?php echo htmlspecialchars($active_subscription['plan_name']); ?></strong> plan.</p>
                            <p>Days remaining: <strong><?php echo $active_subscription['days_remaining']; ?></strong> days</p>
                            <p>You cannot subscribe to a new plan until your current subscription expires.</p>
                            <br>
                            <a href="subscriptions.php" class="btn blue waves-effect waves-light">
                                <i class="material-icons left">visibility</i>
                                View My Subscription
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if ($plans && $plans->num_rows > 0):
                while($plan = $plans->fetch_assoc()): ?>
                    <div class="col s12 m6 l4">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title"><?php echo htmlspecialchars($plan['name']); ?></span>
                                <div class="price">₱<?php echo number_format($plan['price'], 2); ?></div>
                                <p class="duration"><?php echo $plan['duration']; ?> Days</p>
                                <p class="description"><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                            </div>
                            <div class="card-action">
                                <?php if ($has_active_subscription): ?>
                                    <button class="btn disabled grey" disabled>
                                        <i class="material-icons left">block</i>
                                        Cannot Subscribe
                                    </button>
                                <?php else: ?>
                                    <button class="btn blue waves-effect waves-light subscribe-btn" data-plan='<?php echo json_encode($plan); ?>'>
                                        <i class="material-icons left">fitness_center</i>
                                        Subscribe Now
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="col s12">
                    <div class="card">
                        <div class="card-content center-align grey-text">
                            <p>No membership plans available at the moment.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Subscribe Modal -->
    <div id="subscribeModal" class="modal">
        <form id="subscribeForm" enctype="multipart/form-data">
            <div class="modal-content">
                <h4>Subscribe to <span id="planName"></span></h4>
                
                <div class="row">
                    <div class="col s12">
                        <p>Amount to Pay: ₱<span id="planPrice"></span></p>
                        <input type="hidden" name="plan_id" id="planId">
                    </div>
                </div>

                <div class="row">
                    <div class="input-field col s12">
                        <select name="payment_method" id="paymentMethod" required>
                            <option value="" disabled selected>Choose your payment method</option>
                            <option value="cash">Cash</option>
                            <option value="online">Online Payment</option>
                        </select>
                        <label>Payment Method</label>
                    </div>
                </div>

                <div id="onlinePaymentDetails" style="display: none;">
                    <div class="row">
                        <div class="col s12">
                            <h5>Payment Account Details</h5>
                            <?php if ($payment_accounts && $payment_accounts->num_rows > 0): ?>
                                <?php while($account = $payment_accounts->fetch_assoc()): ?>
                                    <div class="payment-account-card">
                                        <p>
                                            <strong><?php echo htmlspecialchars($account['account_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($account['account_number']); ?><br>
                                            <?php echo htmlspecialchars($account['account_type']); ?>
                                        </p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="red-text">No payment accounts available. Please contact admin.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12">
                            <div class="file-field input-field">
                                <div class="btn">
                                    <span>Payment Proof</span>
                                    <input type="file" name="payment_proof" id="paymentProof" accept="image/*">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" placeholder="Upload your payment proof">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancel</a>
                <button type="submit" class="waves-effect waves-light btn blue">
                    <i class="material-icons left">check</i>
                    Submit Payment
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <style>
        main {
            padding-left: 310px;
            padding-right: 30px;
            padding-top: 20px;
        }

        .card .price {
            font-size: 2rem;
            font-weight: 500;
            color: #1565C0;
            margin: 1rem 0;
        }

        .card .duration {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .card .description {
            color: #666;
            min-height: 100px;
        }

        .payment-account-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f5f5f5;
        }

        .payment-account-card p {
            margin: 0;
        }

        @media only screen and (max-width: 992px) {
            main {
                padding-left: 0;
                padding-right: 0;
            }
        }

        @media only screen and (max-width: 600px) {
            .card .description {
                min-height: auto;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            M.AutoInit();

            // Handle payment method change
            const paymentMethod = document.getElementById('paymentMethod');
            const onlinePaymentDetails = document.getElementById('onlinePaymentDetails');
            const paymentProof = document.getElementById('paymentProof');

            paymentMethod.addEventListener('change', function() {
                if (this.value === 'online') {
                    onlinePaymentDetails.style.display = 'block';
                    paymentProof.setAttribute('required', 'required');
                } else {
                    onlinePaymentDetails.style.display = 'none';
                    paymentProof.removeAttribute('required');
                }
            });

            // Handle subscribe button click
            document.querySelectorAll('.subscribe-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    var plan = JSON.parse(this.getAttribute('data-plan'));
                    document.getElementById('planName').textContent = plan.name;
                    document.getElementById('planPrice').textContent = parseFloat(plan.price).toFixed(2);
                    document.getElementById('planId').value = plan.id;
                    var subscribeModal = M.Modal.getInstance(document.getElementById('subscribeModal'));
                    subscribeModal.open();
                });
            });

            // Handle form submission
            document.getElementById('subscribeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                
                fetch('ajax/process_subscription.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        M.toast({
                            html: data.message,
                            classes: 'green'
                        });
                        // Store success message in session storage
                        sessionStorage.setItem('paymentSuccess', data.message);
                        // Redirect to subscriptions page
                        window.location.href = 'subscriptions.php';
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    M.toast({
                        html: error.message,
                        classes: 'red'
                    });
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="material-icons left">check</i>Submit Payment';
                });
            });
        });
    </script>
</body>
</html>