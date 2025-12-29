<?php $subtitle = 'Sign in to continue learning'; ?>

<form action="/login" method="POST" data-loading>
    <?= csrf_field() ?>

    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" value="<?= old('email') ?>" required autofocus>
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
    </div>

    <div class="form-group d-flex justify-between items-center">
        <label class="form-checkbox">
            <input type="checkbox" name="remember">
            <span>Remember me</span>
        </label>
        <a href="/forgot-password" class="text-sm">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">
        Sign In
    </button>
</form>

<div class="auth-footer">
    Don't have an account? <a href="/register">Create one</a>
</div>
