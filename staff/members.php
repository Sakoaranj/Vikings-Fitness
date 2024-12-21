<?php
require_once '../config/config.php';

// Check if user is logged in and is a staff member
if (!isLoggedIn() || !hasRole('staff')) {
    redirect('/login.php');
}

$current_page = 'members';
$page_title = 'Manage Members';

// Get all members with their subscription status
$query = "SELECT u.*, 
          CASE 
            WHEN s.id IS NULL THEN 'Inactive'
            WHEN s.status = 'active' THEN 'Active'
            WHEN s.status = 'pending' THEN 'Pending'
            ELSE 'Inactive'
          END as subscription_status,
          p.name as plan_name,
          s.end_date as subscription_end,
          CASE 
            WHEN s.id IS NULL THEN NULL
            ELSE COALESCE(py.status, 'pending')
          END as payment_status,
          u.created_at as member_since
          FROM users u 
          LEFT JOIN (
              SELECT * FROM subscriptions 
              WHERE status IN ('active', 'pending')
          ) s ON u.id = s.user_id 
          LEFT JOIN plans p ON s.plan_id = p.id
          LEFT JOIN payments py ON s.id = py.subscription_id
          WHERE u.role = 'member' 
          AND u.status = 'active'
          AND u.permanently_deleted = 0
          ORDER BY u.created_at DESC";

$members = $conn->query($query);
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
        <!-- Search and Filter Section -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="card-title">Members Management</div>
                        <div class="row mb-0">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <i class="material-icons prefix">search</i>
                                    <input type="text" id="search-input" onkeyup="filterMembers()">
                                    <label for="search-input">Search by name or email...</label>
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <select id="status-filter" onchange="filterMembers()">
                                        <option value="">All Status</option>
                                        <option value="Active">Active</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                    <label>Filter by Status</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a href="#add-member-modal" class="btn blue waves-effect waves-light modal-trigger">
                            <i class="material-icons left">add</i>Add New Member
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Table -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <table class="striped responsive-table" id="members-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Current Plan</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Valid Until</th>
                                    <th class="center-align">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($members && $members->num_rows > 0): ?>
                                    <?php while($member = $members->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo htmlspecialchars($member['plan_name'] ?? 'No Plan'); ?></td>
                                            <td>
                                                <span class="status-text <?php 
                                                    echo $member['subscription_status'] === 'Active' ? 'status-active' : 
                                                        ($member['subscription_status'] === 'Pending' ? 'status-pending' : 'status-expired'); 
                                                ?>">
                                                    <?php echo $member['subscription_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($member['subscription_status'] !== 'Inactive'): ?>
                                                    <span class="status-text <?php 
                                                        echo $member['payment_status'] === 'paid' ? 'status-paid' : 'status-pending'; 
                                                    ?>">
                                                        <?php echo ucfirst($member['payment_status']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-text status-expired">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($member['subscription_status'] === 'Inactive') {
                                                    echo 'N/A';
                                                } else {
                                                    echo date('M d, Y', strtotime($member['subscription_end']));
                                                }
                                                ?>
                                            </td>
                                            <td class="center-align">
                                                <a href="#" onclick="viewMemberDetails(<?php echo $member['id']; ?>)" 
                                                   class="btn-small blue waves-effect waves-light tooltipped"
                                                   data-position="top" data-tooltip="View Details">
                                                    <i class="material-icons">visibility</i>
                                                </a>
                                                <?php if ($member['payment_status'] === 'pending' && $member['payment_proof']): ?>
                                                    <a href="#" onclick="verifyPayment(<?php echo $member['id']; ?>)"
                                                       class="btn-small green waves-effect waves-light tooltipped"
                                                       data-position="top" data-tooltip="Verify Payment">
                                                        <i class="material-icons">verified</i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($member['status'] === 'active'): ?>
                                                    <a href="#" onclick="deactivateMember(<?php echo $member['id']; ?>)"
                                                       class="btn-small orange waves-effect waves-light tooltipped"
                                                       data-position="top" data-tooltip="Deactivate">
                                                        <i class="material-icons">block</i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="#" onclick="reactivateMember(<?php echo $member['id']; ?>)"
                                                       class="btn-small green waves-effect waves-light tooltipped"
                                                       data-position="top" data-tooltip="Reactivate">
                                                        <i class="material-icons">check_circle</i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="center-align">No members found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Member Details Modal -->
    <div id="member-details-modal" class="modal">
        <div class="modal-content">
            <h4>Member Details</h4>
            <div id="member-details-content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-blue btn-flat">Close</a>
        </div>
    </div>

    <!-- Add this after the Member Details Modal -->
    <div id="add-member-modal" class="modal">
        <form id="add-member-form" onsubmit="return addMember(event)">
            <div class="modal-content">
                <h4>Add New Member</h4>
                <div class="row">
                    <!-- Personal Information -->
                    <div class="col s12">
                        <h5 class="form-section-title">Personal Information</h5>
                    </div>
                    <div class="input-field col s12 m6">
                        <input type="text" id="add-full-name" name="full_name" required>
                        <label for="add-full-name">Full Name</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input type="email" id="add-email" name="email" required>
                        <label for="add-email">Email</label>
                    </div>
                    <div class="input-field col s12">
                        <input type="text" id="add-address" name="address">
                        <label for="add-address">Address</label>
                    </div>

                    <!-- Subscription Details -->
                    <div class="col s12">
                        <h5 class="form-section-title">Subscription Details</h5>
                    </div>
                    <div class="input-field col s12 m6">
                        <select id="add-plan" name="plan" required>
                            <option value="" disabled selected>Choose a plan</option>
                            <?php
                            $plans_query = "SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price ASC";
                            $plans = $conn->query($plans_query);
                            while ($plan = $plans->fetch_assoc()) {
                                echo "<option value='{$plan['id']}' data-duration='{$plan['duration']}' data-price='{$plan['price']}'>{$plan['name']} - ₱{$plan['price']} ({$plan['duration']} days)</option>";
                            }
                            ?>
                        </select>
                        <label>Membership Plan</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <select id="add-payment-method" name="payment_method" required>
                            <option value="cash" selected>Cash</option>
                        </select>
                        <label>Payment Method</label>
                    </div>

                    <!-- Payment Summary -->
                    <div class="col s12">
                        <div class="card grey lighten-4">
                            <div class="card-content">
                                <span class="card-title">Payment Summary</span>
                                <div class="row mb-0">
                                    <div class="col s6">Plan Price:</div>
                                    <div class="col s6 right-align" id="summary-price">₱0.00</div>
                                </div>
                                <div class="row mb-0">
                                    <div class="col s6">Duration:</div>
                                    <div class="col s6 right-align" id="summary-duration">0 days</div>
                                </div>
                                <div class="row mb-0">
                                    <div class="col s6">Valid Until:</div>
                                    <div class="col s6 right-align" id="summary-valid-until">-</div>
                                </div>
                            </div>
                        </div>
                    </div>


            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                <button type="submit" class="waves-effect waves-green btn blue">Add Member</button>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            M.AutoInit();
            
            // Initialize tooltips for the add button
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips, {
                enterDelay: 200,
                exitDelay: 0
            });
        });

        function filterMembers() {
            const searchInput = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const table = document.getElementById('members-table');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header row
                const row = rows[i];
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const status = row.cells[3].textContent.trim(); // Status column

                const matchesSearch = name.includes(searchInput) || email.includes(searchInput);
                const matchesStatus = !statusFilter || status === statusFilter;

                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            }
        }

        const SITE_URL = '<?php echo SITE_URL; ?>';

        function viewMemberDetails(memberId) {
            fetch(`${SITE_URL}/staff/ajax/get_member_details.php?id=${memberId}`)
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            document.getElementById('member-details-content').innerHTML = data.html;
                            const modal = document.getElementById('member-details-modal');
                            M.Modal.getInstance(modal).open();
                        } else {
                            M.toast({html: data.message || 'Failed to load member details', classes: 'red'});
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                        M.toast({html: 'Error loading member details', classes: 'red'});
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    M.toast({html: 'Error connecting to server', classes: 'red'});
                });
        }

        function toggleOnlineAccess() {
            const onlineFields = document.getElementById('online-access-fields');
            const enabled = document.getElementById('enable-online-access').checked;
            const usernameInput = document.getElementById('add-username');
            const passwordInput = document.getElementById('add-password');
            const confirmPasswordInput = document.getElementById('add-confirm-password');
            
            onlineFields.style.display = enabled ? 'block' : 'none';
            
            // Toggle required attribute
            usernameInput.required = enabled;
            passwordInput.required = enabled;
            confirmPasswordInput.required = enabled;
        }
    
        function addMember(event) {
    event.preventDefault(); // Prevent the default form submission

    // Gather data from the form
    const fullName = document.getElementById('add-full-name').value;
    const username = document.getElementById('add-username').value; // Ensure this input exists
    const email = document.getElementById('add-email').value;
    const password = document.getElementById('add-password').value; // Ensure this input exists
    const planId = document.getElementById('add-plan').value; // Ensure this input exists

    const data = {
        full_name: fullName,
        username: username,
        email: email,
        password: password,
        plan: planId,
        action: 'add' // Specify the action for the server
    };

    // Send data to the server
    fetch('ajax/member_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            M.toast({ html: 'Member added successfully!' });
            document.getElementById('add-member-modal').modal('close');
            location.reload(); // Reload the page to see the new member
        } else {
            M.toast({ html: result.message }); // Show error message
        }
    })
    .catch(error => {
        console.error('Error:', error);
        M.toast({ html: 'Error adding member' });
    });
}

        document.getElementById('add-plan').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price;
            const duration = selectedOption.dataset.duration;
            
            document.getElementById('summary-price').textContent = `₱${parseFloat(price).toFixed(2)}`;
            document.getElementById('summary-duration').textContent = `${duration} days`;
            
            // Calculate valid until date
            const validUntil = new Date();
            validUntil.setDate(validUntil.getDate() + parseInt(duration));
            document.getElementById('summary-valid-until').textContent = validUntil.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        });

        function deactivateMember(memberId) {
            if (!confirm('Are you sure you want to deactivate this member?')) {
                return;
            }

            fetch('ajax/deactivate_member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    member_id: memberId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    M.toast({html: data.message, classes: 'green'});
                    location.reload();
                } else {
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error deactivating member', classes: 'red'});
            });
        }

        function reactivateMember(memberId) {
            if (!confirm('Are you sure you want to reactivate this member?')) {
                return;
            }

            fetch('ajax/reactivate_member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    member_id: memberId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    M.toast({html: data.message, classes: 'green'});
                    location.reload();
                } else {
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error reactivating member', classes: 'red'});
            });
        }

        // Initialize Materialize components
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize select
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);

            // Initialize tooltips
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);

            // Initialize modals
            var modals = document.querySelectorAll('.modal');
            M.Modal.init(modals);
        });

        // Function to clear form
        function clearAddMemberForm() {
            document.getElementById('add-member-form').reset();
            const onlineFields = document.getElementById('online-access-fields');
            if (onlineFields) {
                onlineFields.style.display = 'none';
            }
            M.updateTextFields(); // Update Materialize form fields
        }

        // Add event listener to modal triggers to clear form
        document.querySelectorAll('.modal-trigger').forEach(trigger => {
            trigger.addEventListener('click', clearAddMemberForm);
        });

        function verifyPayment(memberId) {
            if (!confirm('Are you sure you want to verify this payment?')) {
                return;
            }

            fetch(`${SITE_URL}/staff/ajax/verify_payment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    member_id: memberId
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

        document.addEventListener('DOMContentLoaded', function() {
            M.AutoInit();

            // Handle Add Member Form Submission
            document.getElementById('add-member-form').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                data.action = 'add'; // Set action to add

                fetch(`${SITE_URL}/admin/ajax/member_operations.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        M.toast({html: 'Member added successfully!'});
                        document.getElementById('add-member-modal').modal('close');
                        location.reload(); // Reload the page to see the new member
                    } else {
                        M.toast({html: result.message}); // Show error message
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    M.toast({html: 'Error adding member'});
                });
            });
        });
    </script>

    <style>
        .card-title {
            font-size: 24px !important;
            font-weight: 300;
            margin-bottom: 20px;
        }
        .status-text {
            font-weight: 500;
            font-size: 0.9rem;
        }
        .status-active { color: #4CAF50; }
        .status-pending { color: #FF9800; }
        .status-expired { color: #F44336; }
        .status-paid { color: #4CAF50; }
        .status-inactive { color: #F44336; }
        .btn-small {
            padding: 0 0.8rem;
            margin: 0 2px;
        }
        .btn-small i {
            font-size: 1.2rem;
            line-height: inherit;
        }
        .card .card-action {
            border-top: 1px solid rgba(160,160,160,0.2);
            padding: 16px 24px;
        }
        .card .card-action .btn {
            margin-right: 0;
        }
        .input-field .prefix {
            font-size: 1.5rem;
            top: 0.5rem;
        }
        .input-field .prefix ~ input {
            margin-left: 3rem;
            width: calc(100% - 3rem);
        }
        .row.mb-0 {
            margin-bottom: 0;
        }
        table.striped > tbody > tr:nth-child(odd) {
            background-color: rgba(242,242,242,0.5);
        }
        .modal {
            max-width: 700px;
        }
        .modal .modal-content {
            padding: 24px;
        }
        .form-section-title {
            color: #1976d2;
            font-size: 1.2rem;
            margin: 1.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-section {
            margin-bottom: 24px;
        }
        .detail-section h5 {
            color: #1976d2;
            font-size: 1.2rem;
            margin: 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        .detail-label {
            font-weight: 500;
            width: 120px;
            color: #757575;
        }
        .detail-value {
            flex: 1;
        }
        #member-details-modal {
            max-width: 800px;
            max-height: 90%;
        }
        #member-details-modal .modal-content {
            padding: 24px;
        }
    </style>
</body>
</html>