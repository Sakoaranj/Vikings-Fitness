<?php
require_once '../config/config.php';

// Check if user is logged in and is a staff member
if (!isLoggedIn() || !hasRole('staff')) {
    redirect('/login.php');
}

$current_page = 'plans';
$page_title = 'Manage Plans';

// Get all active plans
$plans = getStaffPlans();

include 'includes/header.php';
?>

<div class="section">
    <div class="card">
        <div class="card-content">
            <div class="card-title flex-row">
                <span>Membership Plans</span>
            </div>

            <div class="responsive-table">
                <table class="striped highlight">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Features</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th class="center-align">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($plans)):
                            foreach ($plans as $plan): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                <td><?php echo $plan['duration_text']; ?></td>
                                <td><?php echo $plan['formatted_price']; ?></td>
                                <td>
                                    <?php if (!empty($plan['features'])): ?>
                                        <a href="#!" class="view-features blue-text" 
                                           data-features='<?php echo htmlspecialchars(json_encode($plan['features'])); ?>'>
                                            View Features
                                        </a>
                                    <?php else: ?>
                                        <span class="grey-text">No features</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($plan['created_by_name']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($plan['created_at'])); ?></td>
                                <td class="center-align">
                                    <a href="#editPlanModal" class="btn-floating btn-small blue modal-trigger edit-plan tooltipped"
                                       data-position="top" data-tooltip="Edit Plan"
                                       data-id="<?php echo $plan['id']; ?>"
                                       data-name="<?php echo htmlspecialchars($plan['name']); ?>"
                                       data-duration="<?php echo $plan['duration']; ?>"
                                       data-price="<?php echo $plan['price']; ?>"
                                       data-features='<?php echo htmlspecialchars(json_encode($plan['features'])); ?>'>
                                        <i class="material-icons">edit</i>
                                    </a>
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

<!-- Features Modal -->
<div id="featuresModal" class="modal">
    <div class="modal-content">
        <h4>Plan Features</h4>
        <ul class="collection features-list">
        </ul>
    </div>
    <div class="modal-footer">
        <button type="button" class="modal-close waves-effect waves-blue btn-flat">Close</button>
    </div>
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
                    <input type="number" id="edit_price" name="price" required min="0" step="0.01">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltips = document.querySelectorAll('.tooltipped');
    M.Tooltip.init(tooltips);

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
});
</script>

<?php include 'includes/footer.php'; ?> 