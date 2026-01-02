<?php $subtitle = 'Create a new password for your account'; ?>

<form action="/reset-password" method="POST" data-loading>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="form-group">
        <label class="form-label" for="password">New Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Enter new password" required autofocus minlength="8">
        <div class="form-hint">Must be at least 8 characters</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Confirm new password" required minlength="8">
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">
        Reset Password
    </button>
</form>

<div class="auth-footer">
    Remember your password? <a href="/login">Sign in</a>
</div>
