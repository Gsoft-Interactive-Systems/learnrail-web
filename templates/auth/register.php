<?php $subtitle = 'Create your account to start learning'; ?>

<form action="/register" method="POST" data-loading>
    <?= csrf_field() ?>

    <div class="d-flex gap-4">
        <div class="form-group flex-1">
            <label class="form-label" for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-input" placeholder="John" value="<?= old('first_name') ?>" required>
        </div>
        <div class="form-group flex-1">
            <label class="form-label" for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Doe" value="<?= old('last_name') ?>" required>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="john@example.com" value="<?= old('email') ?>" required>
    </div>

    <div class="form-group">
        <label class="form-label" for="phone">Phone Number (Optional)</label>
        <input type="tel" id="phone" name="phone" class="form-input" placeholder="+234 xxx xxx xxxx" value="<?= old('phone') ?>">
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required minlength="8">
        <div class="form-hint">Must be at least 8 characters</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Confirm your password" required>
    </div>

    <div class="form-group">
        <label class="form-checkbox">
            <input type="checkbox" name="terms" required>
            <span>I agree to the <a href="https://learnrail.org/terms" target="_blank">Terms of Service</a> and <a href="https://learnrail.org/privacy" target="_blank">Privacy Policy</a></span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">
        Create Account
    </button>
</form>

<div class="auth-footer">
    Already have an account? <a href="/login">Sign in</a>
</div>
