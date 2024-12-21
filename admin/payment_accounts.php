<?php
require_once '../config/config.php';

// Define SITE_URL constant
define('SITE_URL', 'http://example.com'); // Replace with your site URL

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('/login.php');
}

$current_page = 'payment_accounts';
$page_title = 'Payment Accounts';

// Get all payment accounts
$query = "SELECT * FROM payment_accounts WHERE deleted_at IS NULL ORDER BY account_type, created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Accounts - VikingsFit Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .sidenav { width: 250px; }
        .sidenav li>a { display: flex; align-items: center; }
        .sidenav li>a>i { margin-right: 10px; }
        main { padding: 20px; margin-left: 250px; }
        @media only screen and (max-width: 992px) {
            main { margin-left: 0; }
        }
        .account-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .status-active { color: #4CAF50; }
        .status-inactive { color: #F44336; }
        .btn-small {
            padding: 0 8px;
            height: 24px;
            line-height: 24px;
        }
        .btn-small i {
            font-size: 1.2rem;
            line-height: 24px;
        }
        table td {
            padding: 8px 5px;
        }
        .account-actions form {
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>

    <main>
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row">
                            <div class="col s12">
                                <div class="right-align">
                                    <button class="btn-floating btn-large blue waves-effect waves-light modal-trigger" data-target="add-account-modal">
                                        <i class="material-icons">add</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th class="center-align">Account Name</th>
                                    <th class="center-align">Account Number</th>
                                    <th class="center-align">Account Type</th>
                                    <th class="center-align">Status</th>
                                    <th class="center-align">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($account = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="center-align"><?php echo htmlspecialchars($account['account_name']); ?></td>
                                            <td class="center-align"><?php echo htmlspecialchars($account['account_number']); ?></td>
                                            <td class="center-align"><?php echo htmlspecialchars($account['account_type']); ?></td>
                                            <td class="center-align">
                                                <span class="status-<?php echo $account['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="center-align">
                                                <div class="account-actions">
                                                    <button class="btn-small blue waves-effect waves-light" 
                                                            onclick="editAccount(<?php echo $account['id']; ?>)">
                                                        <i class="material-icons">edit</i>
                                                    </button>
                                                    <button class="btn-small <?php echo $account['is_active'] ? 'red' : 'green'; ?> waves-effect waves-light" 
                                                            onclick="toggleAccountStatus(<?php echo $account['id']; ?>, <?php echo $account['is_active'] ? 'false' : 'true'; ?>)">
                                                        <i class="material-icons"><?php echo $account['is_active'] ? 'clear' : 'check'; ?></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="center-align">No payment accounts found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Account Modal -->
    <div id="add-account-modal" class="modal">
        <div class="modal-content">
            <h4>Add Payment Account</h4>
            <form id="add-account-form">
                <div class="row">
                    <div class="input-field col s12">
                        <select name="account_type" required>
                            <option value="" disabled selected>Choose account type</option>
                            <option value="Bank">Bank Account</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                        </select>
                        <label>Account Type</label>
                    </div>
                    <div class="input-field col s12">
                        <input type="text" name="account_name" required>
                        <label>Account Name</label>
                    </div>
                    <div class="input-field col s12">
                        <input type="text" name="account_number" required>
                        <label>Account Number</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                    <button type="submit" class="waves-effect waves-green btn blue">Add Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div id="edit-account-modal" class="modal">
        <div class="modal-content">
            <h4>Edit Payment Account</h4>
            <form id="edit-account-form">
                <input type="hidden" name="account_id">
                <div class="row">
                    <div class="input-field col s12">
                        <select name="account_type" required>
                            <option value="Bank">Bank Account</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                        </select>
                        <label>Account Type</label>
                    </div>
                    <div class="input-field col s12">
                        <input type="text" name="account_name" required>
                        <label>Account Name</label>
                    </div>
                    <div class="input-field col s12">
                        <input type="text" name="account_number" required>
                        <label>Account Number</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                    <button type="submit" class="waves-effect waves-green btn blue">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Define functions globally
        function toggleAccountStatus(accountId, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus ? 'activate' : 'deactivate'} this account?`)) {
                return;
            }

            fetch(`${window.location.origin}/fitness_gym/admin/ajax/toggle_payment_account.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    account_id: accountId,
                    is_active: newStatus
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
                M.toast({html: 'Error updating account status', classes: 'red'});
            });
        }

        function editAccount(accountId) {
            fetch(`${window.location.origin}/fitness_gym/admin/ajax/get_payment_account.php?id=${accountId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const form = document.getElementById('edit-account-form');
                        const account = data.account;
                        
                        // Fill form fields
                        form.account_id.value = account.id;
                        form.account_name.value = account.account_name;
                        form.account_number.value = account.account_number;
                        form.account_type.value = account.account_type;
                        
                        // Update form labels
                        M.updateTextFields();
                        
                        // Reinitialize select
                        const select = form.querySelector('select');
                        M.FormSelect.init(select);
                        
                        // Open modal
                        M.Modal.getInstance(document.getElementById('edit-account-modal')).open();
                    } else {
                        M.toast({html: data.message || 'Error fetching account details', classes: 'red'});
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    M.toast({html: 'Error fetching account details', classes: 'red'});
                });
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Materialize components
            M.AutoInit();

            // Add Account Form Handler
            document.getElementById('add-account-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons right">hourglass_empty</i>Adding...';

                fetch(`${window.location.origin}/fitness_gym/admin/ajax/add_payment_account.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        M.toast({html: data.message, classes: 'green'});
                        M.Modal.getInstance(document.getElementById('add-account-modal')).close();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        M.toast({html: data.message, classes: 'red'});
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    M.toast({html: 'Error adding payment account', classes: 'red'});
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Edit form handler
            document.getElementById('edit-account-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons right">hourglass_empty</i>Saving...';

                fetch(`${window.location.origin}/fitness_gym/admin/ajax/edit_payment_account.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        M.toast({html: data.message, classes: 'green'});
                        M.Modal.getInstance(document.getElementById('edit-account-modal')).close();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        M.toast({html: data.message, classes: 'red'});
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    M.toast({html: 'Error updating payment account', classes: 'red'});
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });
    </script>
</body>
</html>