<?php // includes/footer.php ?>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= BASE_URL ?>index.php" class="logo logo-light">
                <i class="fas fa-music"></i>
                <span>Melody<strong>Masters</strong></span>
            </a>
            <p>Your premier destination for musical instruments, studio equipment and sheet music. Serving musicians since 2010.</p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
                <li><a href="<?= BASE_URL ?>shop.php">Shop All</a></li>
                <li><a href="<?= BASE_URL ?>register.php">Create Account</a></li>
                <li><a href="<?= BASE_URL ?>login.php">Login</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Categories</h4>
            <ul>
                <?php
                if (!isset($pdo)) { require_once __DIR__.'/../config/database.php'; $pdo = getDB(); }
                $cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
                foreach ($cats as $c):
                ?>
                <li><a href="<?= BASE_URL ?>shop.php?cat=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Contact Us</h4>
            <ul class="contact-list">
                <li><i class="fas fa-map-marker-alt"></i> 12 Harmony Street, London W1D 4AB</li>
                <li><i class="fas fa-phone"></i> +44 207 946 0123</li>
                <li><i class="fas fa-envelope"></i> hello@melodymasters.co.uk</li>
                <li><i class="fas fa-clock"></i> Mon–Sat: 9am – 6pm</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Melody Masters. All rights reserved.</p>
            <div class="payment-icons">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-paypal"></i>
                <i class="fab fa-cc-stripe"></i>
            </div>
        </div>
    </div>
</footer>

<script>
// Sticky navbar
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

// Mobile nav toggle
const navToggle = document.getElementById('navToggle');
const navLinks = document.getElementById('navLinks');
if (navToggle) {
    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('open');
    });
}

// User dropdown
const userBtn = document.querySelector('.btn-user');
const userMenu = document.querySelector('.user-menu');
if (userBtn) {
    userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userMenu.classList.toggle('show');
    });
    document.addEventListener('click', () => userMenu.classList.remove('show'));
}

// Auto-dismiss flash messages
document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 4000);
    setTimeout(() => el.remove(), 4500);
});
</script>
</body>
</html>
