<div class="grid grid-cols-2">
    <!-- Account Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account Settings</h3>
        </div>
        <div class="card-body">
            <a href="/profile/edit" class="d-flex justify-between items-center p-4 rounded hover:bg-gray-50" style="margin: -16px; margin-bottom: 0; text-decoration: none; color: inherit;">
                <div class="d-flex gap-3 items-center">
                    <i class="iconoir-user text-primary"></i>
                    <div>
                        <div class="font-medium">Edit Profile</div>
                        <div class="text-sm text-secondary">Update your personal information</div>
                    </div>
                </div>
                <i class="iconoir-nav-arrow-right text-secondary"></i>
            </a>
        </div>
    </div>

    <!-- Security -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Security</h3>
        </div>
        <div class="card-body">
            <button class="w-100 d-flex justify-between items-center p-4 rounded" style="margin: -16px; background: none; border: none; cursor: pointer; text-align: left;" onclick="Modal.open('change-password-modal')">
                <div class="d-flex gap-3 items-center">
                    <i class="iconoir-lock text-primary"></i>
                    <div>
                        <div class="font-medium">Change Password</div>
                        <div class="text-sm text-secondary">Update your password</div>
                    </div>
                </div>
                <i class="iconoir-nav-arrow-right text-secondary"></i>
            </button>
        </div>
    </div>

    <!-- Subscription -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Subscription</h3>
        </div>
        <div class="card-body">
            <a href="/subscription" class="d-flex justify-between items-center p-4 rounded hover:bg-gray-50" style="margin: -16px; margin-bottom: 0; text-decoration: none; color: inherit;">
                <div class="d-flex gap-3 items-center">
                    <i class="iconoir-star text-warning"></i>
                    <div>
                        <div class="font-medium">Manage Subscription</div>
                        <div class="text-sm text-secondary">View or upgrade your plan</div>
                    </div>
                </div>
                <i class="iconoir-nav-arrow-right text-secondary"></i>
            </a>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title text-danger">Danger Zone</h3>
        </div>
        <div class="card-body">
            <p class="text-sm text-secondary mb-4">Once you delete your account, there is no going back. Please be certain.</p>
            <button class="btn btn-danger btn-sm" onclick="if(confirm('Are you sure you want to delete your account? This action cannot be undone.')) { /* handle delete */ }">
                Delete Account
            </button>
        </div>
    </div>
</div>

<!-- Logout -->
<div class="mt-6">
    <a href="/logout" class="btn btn-outline btn-block">
        <i class="iconoir-log-out"></i>
        Logout
    </a>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="change-password-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Change Password</h3>
            <button class="modal-close" onclick="Modal.close('change-password-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="change-password-form">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" class="form-input" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('change-password-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="changePassword()">Update Password</button>
        </div>
    </div>
</div>

<script>
async function changePassword() {
    const form = document.getElementById('change-password-form');
    const formData = new FormData(form);

    try {
        const response = await API.post('/auth/change-password', {
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password'),
            new_password_confirmation: formData.get('new_password_confirmation')
        });

        if (response.success) {
            Toast.success('Password updated successfully');
            Modal.close('change-password-modal');
            form.reset();
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to update password');
    }
}
</script>
