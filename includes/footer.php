</main><!-- /main-content -->

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
    <div class="footer-top container">
        <div class="footer-brand">
            <div class="footer-logo"><i class="ri-building-4-line"></i> Uni<strong>Hub</strong></div>
            <p>Connect. Collaborate. Grow.<br>The official University Club &amp; Event Management Platform.</p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="ri-facebook-fill"></i></a>
                <a href="#" aria-label="Twitter"><i class="ri-twitter-x-line"></i></a>
                <a href="#" aria-label="Instagram"><i class="ri-instagram-line"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="ri-linkedin-fill"></i></a>
                <a href="#" aria-label="YouTube"><i class="ri-youtube-fill"></i></a>
            </div>
        </div>
        <div class="footer-links-group">
            <h4>Platform</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= BASE_URL ?>/pages/clubs.php">Clubs</a></li>
                <li><a href="<?= BASE_URL ?>/pages/events.php">Events</a></li>
                <li><a href="<?= BASE_URL ?>/pages/create-club.php">Start a Club</a></li>
            </ul>
        </div>
        <div class="footer-links-group">
            <h4>Account</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/pages/login.php">Login</a></li>
                <li><a href="<?= BASE_URL ?>/pages/register.php">Register</a></li>
                <?php if (isLoggedIn()): ?>
                <li><a href="<?= BASE_URL ?>/dashboard/<?= str_replace('_', '-', currentRole()) ?>/index.php">Dashboard</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-links-group">
            <h4>Info</h4>
            <ul>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>
        <div class="footer-links-group">
            <h4>Contact</h4>
            <ul>
                <li><i class="ri-mail-line"></i> clubs@university.edu</li>
                <li><i class="ri-phone-line"></i> +880-1700-000000</li>
                <li><i class="ri-map-pin-line"></i> UniHub University Campus</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom container">
        <p>&copy; <?= date('Y') ?> UniHub University. All rights reserved.</p>
        <p>Built with <i class="ri-heart-fill" style="color:var(--color-red)"></i> for students.</p>
    </div>
</footer>

<!-- JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (defined('EXTRA_JS') && EXTRA_JS): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= EXTRA_JS ?>"></script>
<?php endif; ?>
<script>
// Pass PHP data to JS
window.BASE_URL = '<?= BASE_URL ?>';
window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
window.CURRENT_ROLE = '<?= currentRole() ?>';
</script>
</body>
</html>