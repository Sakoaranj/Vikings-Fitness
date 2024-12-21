<?php
require_once '../config/config.php';

// Check if user is logged in and is a member
if (!isLoggedIn() || !hasRole('member')) {
    redirect('/login.php');
}

$current_page = 'subscribe';
$page_title = 'Subscribe to Plan';

// Get plan ID from URL
$plan_id = isset($_GET['plan']) ? (int)$_GET['plan'] : 0;

// Get plan details
$plan_query = "SELECT * FROM plans WHERE id = $plan_id AND deleted_at IS NULL";
$plan_result = $conn->query($plan_query);
$plan = $plan_result->fetch_assoc();

if (!$plan) {
    redirect('plans.php');
}

// Check for active subscription
$member_id = $_SESSION['user_id'];
$active_sub_query = "SELECT * FROM subscriptions 
                     WHERE user_id = $member_id 
                     AND status = 'active' 
                     AND end_date >= CURRENT_DATE()";
$active_sub = $conn->query($active_sub_query)->fetch_assoc();

if ($active_sub) {
    $_SESSION['error_message'] = "You already have an active subscription.";
    redirect('plans.php');
}

// Get active payment accounts
$gcash_accounts = $conn->query("SELECT * FROM admin_payment_accounts 
                               WHERE account_type = 'gcash' 
                               AND is_active = 1");

$bank_accounts = $conn->query("SELECT * FROM admin_payment_accounts 
                             WHERE account_type = 'bank' 
                             AND is_active = 1");
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
        <div class="row">
            <!-- Plan Summary Card -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons">fitness_center</i>
                            Plan Summary
                        </span>
                        <div class="plan-details">
                            <p><strong>Plan:</strong> <?php echo htmlspecialchars($plan['name']); ?></p>
                            <p><strong>Duration:</strong> <?php echo $plan['duration']; ?> days</p>
                            <p><strong>Price:</strong> ₱<?php echo number_format($plan['price'], 2); ?></p>
                            <p><strong>Description:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons">payment</i>
                            Payment Details
                        </span>

                        <form id="payment-form" action="ajax/process_subscription.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
                            
                            <div class="payment-method-section">
                                <p>Select Payment Method:</p>
                                <p>
                                    <label>
                                        <input name="payment_method" type="radio" value="gcash" checked />
                                        <span>GCash</span>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input name="payment_method" type="radio" value="bank" />
                                        <span>Bank Transfer</span>
                                    </label>
                                </p>
                            </div>

                            <!-- GCash Details -->
                            <div id="gcash-details" class="payment-details">
                                <div class="account-info">
                                    <?php if ($gcash_accounts->num_rows > 0): ?>
                                        <?php while($account = $gcash_accounts->fetch_assoc()): ?>
                                            <div class="detail-row">
                                                <p>
                                                    <strong>GCash Number:</strong> 
                                                    <?php echo htmlspecialchars($account['account_number']); ?>
                                                </p>
                                                <p>
                                                    <strong>Account Name:</strong> 
                                                    <?php echo htmlspecialchars($account['account_name']); ?>
                                                </p>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="red-text">No GCash accounts available. Please try bank transfer.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Bank Details -->
                            <div id="bank-details" class="payment-details" style="display: none;">
                                <div class="account-info">
                                    <?php if ($bank_accounts->num_rows > 0): ?>
                                        <?php while($account = $bank_accounts->fetch_assoc()): ?>
                                            <div class="detail-row">
                                                <p>
                                                    <strong>Bank:</strong> 
                                                    <?php echo htmlspecialchars($account['bank_name']); ?>
                                                </p>
                                                <p>
                                                    <strong>Account Number:</strong> 
                                                    <?php echo htmlspecialchars($account['account_number']); ?>
                                                </p>
                                                <p>
                                                    <strong>Account Name:</strong> 
                                                    <?php echo htmlspecialchars($account['account_name']); ?>
                                                </p>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="red-text">No bank accounts available. Please try GCash.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Proof Upload -->
                            <div class="file-field input-field">
                                <div class="btn blue">
                                    <span>Upload Proof</span>
                                    <input type="file" name="payment_proof" accept="image/*" required>
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" 
                                           placeholder="Upload your payment screenshot">
                                </div>
                                <span class="helper-text">Please upload a clear screenshot of your payment</span>
                            </div>

                            <!-- Payment Notes -->
                            <div class="input-field">
                                <i class="material-icons prefix">note</i>
                                <textarea id="payment_notes" name="payment_notes" 
                                          class="materialize-textarea"></textarea>
                                <label for="payment_notes">Payment Notes (Optional)</label>
                                <span class="helper-text">Add any additional information about your payment</span>
                            </div>

                            <div class="center-align" style="margin-top: 20px;">
                                <button type="submit" class="btn-large blue waves-effect waves-light">
                                    Submit Payment
                                    <i class="material-icons right">send</i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <style>
        /* Main content padding adjustments */
        main {
            padding-left: 310px;
            padding-right: 30px;
            padding-top: 20px;
        }

        .plan-details p {
            margin: 10px 0;
        }

        .payment-method-section {
            margin: 20px 0;
        }

        .payment-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }

        .detail-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-row p {
            margin: 5px 0;
        }

        .helper-text {
            font-size: 0.8rem;
            color: #757575;
        }

        @media only screen and (max-width: 992px) {
            main {
                padding-left: 0;
                padding-right: 0;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Materialize components
            M.updateTextFields();
            M.textareaAutoResize(document.querySelector('textarea'));

            // Handle payment method toggle
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const gcashDetails = document.getElementById('gcash-details');
            const bankDetails = document.getElementById('bank-details');

            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (this.value === 'gcash') {
                        gcashDetails.style.display = 'block';
                        bankDetails.style.display = 'none';
                    } else {
                        gcashDetails.style.display = 'none';
                        bankDetails.style.display = 'block';
                    }
                });
            });

            // Form validation
            const form = document.getElementById('payment-form');
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                
                const fileInput = form.querySelector('input[type="file"]');
                if (!fileInput.files[0]) {
                    M.toast({html: 'Please upload your payment proof', classes: 'red'});
                    return;
                }

                const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 5) {
                    M.toast({html: 'File size must be less than 5MB', classes: 'red'});
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(fileInput.files[0].type)) {
                    M.toast({html: 'Please upload an image file (JPEG, PNG, or GIF)', classes: 'red'});
                    return;
                }

                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons right">hourglass_empty</i>Processing...';

                // Create FormData and submit
                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        M.toast({
                            html: data.message,
                            classes: 'green',
                            displayLength: 4000
                        });
                        
                        // Store success message in session storage for dashboard
                        sessionStorage.setItem('paymentSuccess', data.message);
                        
                        // Redirect to subscriptions page after delay
                        setTimeout(() => {
                            window.location.href = 'subscriptions.php';
                        }, 2000);
                    } else {
                        // Show error message
                        M.toast({
                            html: data.message || 'Error processing payment',
                            classes: 'red'
                        });
                        // Reset button
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    M.toast({
                        html: 'Error submitting payment. Please try again.',
                        classes: 'red'
                    });
                    // Reset button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });
    </script>
</body>
</html>