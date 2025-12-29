<?php $subtitle = 'Enter your email to reset your password'; ?>

<form action="/forgot-password" method="POST" data-loading>
    <?= csrf_field() ?>

    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required autofocus>
        <div class="form-hint">We'll send you a link to reset your password</div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">
        Send Reset Link
    </button>
</form>

<div class="auth-footer">
    Remember your password? <a href="/login">Sign in</a>
</div>
