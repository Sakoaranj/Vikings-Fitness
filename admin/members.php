<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('/login.php');
}

$current_page = 'members';
$page_title = 'Manage Members';

// Get search parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';

// Build query
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
          u.created_at as member_since,
          u.verified,
          u.verified_at,
          u.verified_by,
          u.staff_notes
          FROM users u 
          LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active' 
          LEFT JOIN plans p ON s.plan_id = p.id 
          LEFT JOIN payments py ON s.id = py.subscription_id
          WHERE u.role = 'member' 
          AND u.permanently_deleted = 0";

if ($search) {
    $query .= " AND (username LIKE '%$search%' OR email LIKE '%$search%' OR full_name LIKE '%$search%')";
}

if ($status !== 'all') {
    if ($status === 'active') {
        $query .= " AND s.status = 'active'";
    } else if ($status === 'inactive') {
        $query .= " AND (s.status IS NULL OR s.status = 'inactive')";
    }
}

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
    <?php include '../includes/admin_nav.php'; ?>

    <main>
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <!-- Search and Filter -->
                        <div class="row">
                            <div class="col s12 m6">
                                <input type="text" id="search" placeholder="Search members..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col s12 m6">
                                <select id="status-filter" onchange="updateFilters()">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
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
                                                    <th>Verified</th>
                                                    <th class="center-align">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($result && $result->num_rows > 0): ?>
                                                    <?php while($member = $result->fetch_assoc()): ?>
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
                                                            <td>
                                                                <?php if ($member['verified']): ?>
                                                                    <span class="status-text status-active">
                                                                        <i class="material-icons tiny">verified</i> Verified
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="status-text status-pending">
                                                                        <i class="material-icons tiny">pending</i> Unverified
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="center-align">
                                                                <div class="action-buttons">
                                                                    <a href="#" onclick="viewMemberDetails(<?php echo $member['id']; ?>)" 
                                                                       class="btn-small blue waves-effect waves-light tooltipped"
                                                                       data-position="top" data-tooltip="View Details">
                                                                        <i class="material-icons">visibility</i>
                                                                    </a>
                                                                    <?php if (!$member['verified']): ?>
                                                                        <button class="btn-small blue verify-btn" onclick="verifyMember(<?php echo $member['id']; ?>)">
                                                                            <i class="material-icons">verified_user</i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <button class="btn-small red delete-member tooltipped"
                                                                            data-id="<?php echo $member['id']; ?>"
                                                                            data-name="<?php echo htmlspecialchars($member['full_name']); ?>"
                                                                            data-position="top" 
                                                                            data-tooltip="Permanently Delete">
                                                                        <i class="material-icons">delete_forever</i>
                                                                    </button>
                                                                </div>
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
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Member Modal -->
    <div id="edit-member-modal" class="modal">
        <div class="modal-content">
            <h4>Edit Member</h4>
            <form id="edit-member-form">
                <input type="hidden" id="edit_member_id" name="id">
                <div class="row">
                    <div class="col s12">
                        <div class="input-field">
                            <input type="text" id="edit_full_name" name="full_name" required>
                            <label for="edit_full_name">Full Name</label>
                        </div>
                    </div>
                    <div class="col s12">
                        <div class="input-field">
                            <input type="text" id="edit_username" name="username" required>
                            <label for="edit_username">Username</label>
                        </div>
                    </div>
                    <div class="col s12">
                        <div class="input-field">
                            <input type="email" id="edit_email" name="email" required>
                            <label for="edit_email">Email</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-close waves-effect waves-red btn-flat">Cancel</button>
                    <button type="submit" class="waves-effect waves-green btn blue">Update Member</button>
                </div>
            </form>
        </div>
    </div>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Define SITE_URL for JavaScript
        const SITE_URL = '<?php echo SITE_URL; ?>';
    </script>

    <!-- Add the JavaScript for member operations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all Materialize components
            M.AutoInit();
            
            // Initialize modals with specific options
            var elems = document.querySelectorAll('.modal');
            var instances = M.Modal.init(elems, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 300,
                outDuration: 200,
                startingTop: '4%',
                endingTop: '10%'
            });

            // Initialize form labels
            M.updateTextFields();

            // Edit Member Form Submit
            const editMemberForm = document.getElementById('edit-member-form');
            if (editMemberForm) {
                editMemberForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = {
                        action: 'edit',
                        id: document.getElementById('edit_member_id').value,
                        username: document.getElementById('edit_username').value,
                        email: document.getElementById('edit_email').value,
                        full_name: document.getElementById('edit_full_name').value
                    };
                    
                    fetch(`${SITE_URL}/admin/ajax/member_operations.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            var modal = M.Modal.getInstance(document.getElementById('edit-member-modal'));
                            modal.close();
                            
                            // Show success message
                            M.toast({html: data.message, classes: 'green'});
                            
                            // Reload page after delay
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            M.toast({html: data.message, classes: 'red'});
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        M.toast({html: 'Error updating member', classes: 'red'});
                    });
                });
            }

            // Initialize all modals
            var modals = document.querySelectorAll('.modal');
            M.Modal.init(modals, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 300,
                outDuration: 200,
                startingTop: '4%',
                endingTop: '10%'
            });

            // Initialize tooltips
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);

            // Delete Member
            document.querySelectorAll('.delete-member').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    
                    if (confirm(`WARNING: Are you sure you want to permanently delete "${name}"?\n\nThis action will:\n- Delete all member data\n- Remove subscription history\n- Remove payment records\n\nThis action CANNOT be undone!`)) {
                        fetch('ajax/member_operations.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                id: id
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                M.toast({html: 'Member deleted successfully'});
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                M.toast({html: data.message || 'Error deleting member', classes: 'red'});
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            M.toast({html: 'Error deleting member', classes: 'red'});
                        });
                    }
                });
            });
        });

        function editMember(memberId) {
            fetch(`${SITE_URL}/admin/ajax/member_operations.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get',
                    id: memberId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const member = data.data;
                    document.getElementById('edit_member_id').value = member.id;
                    document.getElementById('edit_username').value = member.username;
                    document.getElementById('edit_email').value = member.email;
                    document.getElementById('edit_full_name').value = member.full_name;
                    
                    // Reinitialize Materialize labels
                    M.updateTextFields();
                    
                    // Open modal
                    const modal = M.Modal.getInstance(document.getElementById('edit-member-modal'));
                    modal.open();
                } else {
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error getting member details', classes: 'red'});
            });
        }

        function toggleMemberStatus(memberId, currentStatus) {
            if (confirm(`Are you sure you want to ${currentStatus === 'active' ? 'deactivate' : 'activate'} this member?`)) {
                fetch(`${SITE_URL}/admin/ajax/member_operations.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'toggle_status',
                        id: memberId,
                        status: currentStatus
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
                    M.toast({html: 'Error updating member status', classes: 'red'});
                });
            }
        }

        function deleteMember(memberId, permanent = false) {
            const confirmMsg = permanent ? 
                'Are you sure you want to permanently delete this member? This cannot be undone!' : 
                'Are you sure you want to deactivate this member?';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            fetch(`${SITE_URL}/admin/ajax/delete_member.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    member_id: memberId,
                    permanent: permanent
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
                M.toast({html: 'Error processing request', classes: 'red'});
            });
        }

        function reactivateMember(memberId) {
            if (!confirm('Are you sure you want to reactivate this member?')) {
                return;
            }
            
            fetch(`${SITE_URL}/admin/ajax/reactivate_member.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
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
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error processing request', classes: 'red'});
            });
        }

        function updateFilters() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            window.location.href = `members.php?search=${encodeURIComponent(search)}&status=${status}`;
        }

        function viewMemberDetails(memberId) {
            // Initialize the modal first
            const modal = document.getElementById('member-details-modal');
            const instance = M.Modal.init(modal);

            fetch(`${SITE_URL}/admin/ajax/get_member_details.php?id=${memberId}`)
                .then(response => response.text())
                .then(text => {
                    try {
                        // Log the raw response for debugging
                        console.log('Server response:', text);
                        const data = JSON.parse(text);
                        
                        if (data.success) {
                            document.getElementById('member-details-content').innerHTML = data.html;
                            instance.open();
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

        function verifyMember(memberId) {
            if (!confirm('Are you sure you want to verify this member?')) {
                return;
            }
            
            fetch(`${SITE_URL}/admin/ajax/verify_member.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
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
                    M.toast({html: data.message, classes: 'red'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                M.toast({html: 'Error verifying member', classes: 'red'});
            });
        }
    </script>

    <style>
        /* Table styles */
        table td, table th {
            padding: 8px 16px !important;
        }
        
        .member-actions {
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
        .member-actions form {
            margin: 0;
        }
        .modal {
            max-width: 500px;
            border-radius: 8px;
        }
        .modal .modal-content {
            padding: 24px;
        }
        .modal .modal-content h4 {
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 1.8rem;
            color: #1565C0;
        }
        .modal .input-field {
            margin-bottom: 16px;
        }
        .modal .input-field input {
            padding-left: 8px;
        }
        .modal .input-field label {
            left: 8px;
        }
        .modal .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #ddd;
            background-color: #f5f5f5;
        }
        .modal .btn {
            margin-left: 8px;
        }
        .modal .btn-flat {
            color: #757575;
        }
        .modal form {
            margin-bottom: 0;
        }
        /* Update the status color styles */
        .status-text {
            font-weight: 500;
            font-size: 0.9rem;
        }
        .status-active { color: #4CAF50; }
        .status-pending { color: #FF9800; }
        .status-expired { color: #F44336; } /* Changed to red */
        .status-paid { color: #4CAF50; }
        .status-inactive { color: #F44336; } /* Changed to red */
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
        #member-details-modal table {
            margin-top: 16px;
        }
        #member-details-modal table th,
        #member-details-modal table td {
            padding: 12px 8px;
        }
    </style>
</body>
</html> 