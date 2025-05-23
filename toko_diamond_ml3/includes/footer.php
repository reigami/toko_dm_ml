<footer>
    &copy; <?= date('Y'); ?> Toko Diamond ML.
    <?php if (isset($_SESSION['username'])): ?>
        <br>Login sebagai: <?= htmlspecialchars($_SESSION['username']); ?>
    <?php endif; ?>
</footer>
