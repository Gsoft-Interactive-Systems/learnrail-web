<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Edit Profile</h3>
    </div>
    <div class="card-body">
        <form action="/profile/edit" method="POST" data-loading>
            <?= csrf_field() ?>

            <!-- Avatar -->
            <div class="form-group text-center mb-6">
                <div class="avatar avatar-xl mb-3" style="margin: 0 auto;">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= e($user['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('avatar-input').click()">
                    Change Photo
                </button>
                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none;">
            </div>

            <div class="d-flex gap-4">
                <div class="form-group flex-1">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" value="<?= e($user['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group flex-1">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input" value="<?= e($user['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" class="form-input" value="<?= e($user['email'] ?? '') ?>" disabled>
                <div class="form-hint">Email cannot be changed</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-input" value="<?= e($user['phone'] ?? '') ?>" placeholder="+234 xxx xxx xxxx">
            </div>

            <div class="d-flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>
                <a href="/profile" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
