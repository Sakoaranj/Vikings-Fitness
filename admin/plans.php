<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin/staff
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('staff'))) {
    redirect('/login.php');
}

$current_page = 'plans';
$page_title = 'Manage Plans';

// Get all plans including deleted ones for admin
$plans = getActivePlans(true);

include 'includes/header.php';
?>

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <div class="card-title flex-row">
                    <span>Membership Plans</span>
                </div>
                
                <?php if (hasRole('admin')): ?>
                <a href="#addPlanModal" class="btn-floating btn-large halfway-fab waves-effect waves-light blue modal-trigger">
                    <i class="material-icons">add</i>
                </a>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-header">
                        <div class="flex-right">
                            <div class="input-field status-filter">
                                <select id="statusFilter">
                                    <option value="all">All Plans</option>
                                    <option value="active">Active Plans</option>
                                    <option value="inactive">Inactive Plans</option>
                                </select>
                                <label>Filter by Status</label>
                            </div>
                        </div>
                    </div>
                    <table class="striped highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Features</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($plans)):
                                foreach ($plans as $plan): 
                            ?>
                                <tr class="plan-row <?php echo $plan['deleted_at'] ? 'inactive-plan' : 'active-plan'; ?>">
                                    <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                    <td><?php echo $plan['duration_text']; ?></td>
                                    <td><?php echo $plan['formatted_price']; ?></td>
                                    <td>
                                        <?php if (!empty($plan['features'])): ?>
                                            <a href="#!" class="view-features" 
                                               data-features='<?php echo htmlspecialchars(json_encode($plan['features'])); ?>'>
                                                View Features
                                            </a>
                                        <?php else: ?>
                                            <span class="grey-text">No features</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($plan['created_by_name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($plan['created_at'])); ?></td>
                                    <td>
                                        <?php
                                        $status_class = $plan['deleted_at'] ? 'red-text' : 'green-text';
                                        $status_text = $plan['deleted_at'] ? 'Inactive' : 'Active';
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <i class="material-icons tiny"><?php echo $plan['deleted_at'] ? 'cancel' : 'check_circle'; ?></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="#editPlanModal" class="btn-small blue modal-trigger edit-plan"
                                               data-id="<?php echo $plan['id']; ?>"
                                               data-name="<?php echo htmlspecialchars($plan['name']); ?>"
                                               data-duration="<?php echo $plan['duration']; ?>"
                                               data-price="<?php echo $plan['price']; ?>"
                                               data-features='<?php echo htmlspecialchars(json_encode($plan['features'])); ?>'>
                                                <i class="material-icons">edit</i>
                                            </a>
                                            
                                            <button class="btn-small <?php echo $plan['deleted_at'] ? 'green activate-plan' : 'orange deactivate-plan'; ?>"
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($plan['name']); ?>">
                                                <i class="material-icons"><?php echo $plan['deleted_at'] ? 'check_circle' : 'pause_circle_filled'; ?></i>
                                            </button>
                                            
                                            <button class="btn-small red delete-plan"
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($plan['name']); ?>">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="7" class="center-align">No plans found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Plan Modal -->
<div id="addPlanModal" class="modal">
    <form id="addPlanForm">
        <div class="modal-content">
            <h4>Add New Plan</h4>
            <div class="row">
                <div class="input-field col s12">
                    <input type="text" id="name" name="name" required>
                    <label for="name">Plan Name</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="number" id="duration" name="duration" required min="1" max="36">
                    <label for="duration">Duration (Months)</label>
                    <span class="helper-text">Enter number of months (1-36)</span>
                </div>
                <div class="input-field col s12 m6">
                    <input type="number" id="price" name="price" required min="0" step="0.01">
                    <label for="price">Price</label>
                </div>
                <div class="col s12">
                    <label>Features (one per line)</label>
                    <textarea id="features" name="features" class="materialize-textarea" rows="5"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-close waves-effect waves-red btn-flat">Cancel</button>
            <button type="submit" class="waves-effect waves-green btn blue">Add Plan</button>
        </div>
    </form>
</div>

<!-- Edit Plan Modal -->
<div id="editPlanModal" class="modal">
    <form id="editPlanForm">
        <div class="modal-content">
            <h4>Edit Plan</h4>
            <div class="row">
                <input type="hidden" id="edit_id" name="id">
                <div class="input-field col s12">
                    <input type="text" id="edit_name" name="name" required>
                    <label for="edit_name">Plan Name</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="number" id="edit_duration" name="duration" min="1" max="36" required>
                    <label for="edit_duration">Duration (Months)</label>
                    <span class="helper-text">Enter number of months (1-36)</span>
                </div>
                <div class="input-field col s12 m6">
                    <input type="number" id="edit_price" name="price" required min="0">
                    <label for="edit_price">Price</label>
                </div>
                <div class="col s12">
                    <label>Features (one per line)</label>
                    <textarea id="edit_features" name="features" class="materialize-textarea" rows="5"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-close waves-effect waves-red btn-flat">Cancel</button>
            <button type="submit" class="waves-effect waves-green btn blue">Update Plan</button>
        </div>
    </form>
</div>

<!-- Features Modal -->
<div id="featuresModal" class="modal">
    <div class="modal-content">
        <h4>Plan Features</h4>
        <ul class="features-list collection"></ul>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-blue btn-flat">Close</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    // View Features
    document.querySelectorAll('.view-features').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const features = JSON.parse(this.dataset.features);
            const featuresList = document.querySelector('#featuresModal .features-list');
            featuresList.innerHTML = '';
            
            features.forEach(feature => {
                featuresList.innerHTML += `
                    <li class="collection-item">
                        <i class="material-icons tiny blue-text">check</i>
                        ${feature}
                    </li>`;
            });
            
            M.Modal.getInstance(document.querySelector('#featuresModal')).open();
        });
    });

    // Add Plan
    document.getElementById('addPlanForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const features = this.features.value.split('\n').filter(f => f.trim());
        
        fetch('ajax/plan_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add',
                name: this.name.value,
                duration: this.duration.value,
                price: this.price.value,
                features: features
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                M.toast({html: 'Plan added successfully'});
                setTimeout(() => window.location.reload(), 1000);
            } else {
                M.toast({html: data.message || 'Error adding plan', classes: 'red'});
            }
        })
        .catch(error => {
            console.error('Error:', error);
            M.toast({html: 'Error adding plan', classes: 'red'});
        });
    });

    // Edit Plan
    document.querySelectorAll('.edit-plan').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const duration = this.dataset.duration;
            const price = this.dataset.price;
            const features = JSON.parse(this.dataset.features || '[]');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_duration').value = duration;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_features').value = features.join('\n');
            
            // Reinitialize Materialize labels
            M.updateTextFields();
            M.textareaAutoResize(document.getElementById('edit_features'));
        });
    });

    // Update Plan
    document.getElementById('editPlanForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const features = this.features.value.split('\n').filter(f => f.trim());
        
        fetch('ajax/plan_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'edit',
                id: this.id.value,
                name: this.name.value,
                duration: this.duration.value,
                price: this.price.value,
                features: features
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                M.toast({html: 'Plan updated successfully'});
                setTimeout(() => window.location.reload(), 1000);
            } else {
                M.toast({html: data.message || 'Error updating plan', classes: 'red'});
            }
        })
        .catch(error => {
            console.error('Error:', error);
            M.toast({html: 'Error updating plan', classes: 'red'});
        });
    });

    // Deactivate Plan
    document.querySelectorAll('.deactivate-plan').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            if (confirm(`Are you sure you want to deactivate "${name}"?`)) {
                fetch('ajax/plan_operations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'deactivate',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        M.toast({html: 'Plan deactivated successfully'});
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        M.toast({html: data.message || 'Error deactivating plan', classes: 'red'});
                    }
                });
            }
        });
    });

    // Activate Plan
    document.querySelectorAll('.activate-plan').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            if (confirm(`Are you sure you want to activate "${name}"?`)) {
                fetch('ajax/plan_operations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'activate',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        M.toast({html: 'Plan activated successfully'});
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        M.toast({html: data.message || 'Error activating plan', classes: 'red'});
                    }
                });
            }
        });
    });

    // Initialize select
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Status filtering
    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value;
        const rows = document.querySelectorAll('.plan-row');
        
        rows.forEach(row => {
            switch(status) {
                case 'active':
                    row.style.display = row.classList.contains('active-plan') ? '' : 'none';
                    break;
                case 'inactive':
                    row.style.display = row.classList.contains('inactive-plan') ? '' : 'none';
                    break;
                default: // 'all'
                    row.style.display = '';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 