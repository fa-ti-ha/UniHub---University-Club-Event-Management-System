<?php
// ============================================================
// pages/login.php
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in
if (isLoggedIn()) { dashboardRedirect(); }

define('PAGE_TITLE', 'Login');
define('EXTRA_CSS', 'auth.css');
define('EXTRA_JS', 'auth.js');
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="auth-page">
    <!-- Left Panel -->
    <div class="auth-panel-left">
        <div class="auth-left-content">
            <div class="auth-brand">
                <div class="auth-brand-icon"><i class="ri-building-4-line"></i></div>
                UniHub
            </div>
            <div class="auth-illustration">🎓</div>
            <h2>Welcome Back!</h2>
            <p>Log in to manage your clubs, discover events, and stay connected with your university community.</p>
            <div class="auth-features">
                <div class="auth-feature-item"><i class="ri-checkbox-circle-line"></i> Access your student dashboard</div>
                <div class="auth-feature-item"><i class="ri-checkbox-circle-line"></i> Manage club memberships</div>
                <div class="auth-feature-item"><i class="ri-checkbox-circle-line"></i> Register for upcoming events</div>
                <div class="auth-feature-item"><i class="ri-checkbox-circle-line"></i> Get real-time notifications</div>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-panel-right">
        <div class="auth-form-wrapper">
            <div class="auth-form-header">
                <h1>Sign In</h1>
                <p>Don't have an account? <a href="<?= BASE_URL ?>/pages/register.php">Create one free</a></p>
            </div>

            <form id="loginForm" novalidate>
                <input type="hidden" name="action" value="login" />
                <meta name="csrf-token" content="<?= csrfToken() ?>" />

                <div class="form-group">
                    <label class="form-label" for="loginEmail">Email Address <span class="required">*</span></label>
                    <div class="form-control-icon">
                        <i class="ri-mail-line input-icon"></i>
                        <input type="email" id="loginEmail" name="email" class="form-control" placeholder="you@university.edu" required autocomplete="email" />
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-between mb-2">
                        <label class="form-label" for="loginPassword" style="margin:0">Password <span class="required">*</span></label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    <div class="form-control-icon">
                        <i class="ri-lock-line input-icon"></i>
                        <input type="password" id="loginPassword" name="password" class="form-control has-right-icon" placeholder="••••••••" required autocomplete="current-password" />
                        <span class="input-icon-right" data-toggle-password="loginPassword"><i class="ri-eye-line"></i></span>
                    </div>
                </div>

                <div class="flex-between mb-6">
                    <label class="form-check">
                        <input type="checkbox" name="remember" value="1" />
                        <span class="form-check-label">Remember me for 30 days</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">
                    <span class="btn-text"><i class="ri-login-box-line"></i> Sign In</span>
                    <span class="btn-loading"><i class="ri-loader-4-line spin"></i> Signing in...</span>
                </button>


           
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
