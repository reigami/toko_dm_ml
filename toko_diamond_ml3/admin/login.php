<?php include '../includes/config.php'; ?>
<?php include '../includes/header.php'; ?>
<?
session_start();
?>
<link rel="stylesheet" href="../assets/css/style.css">

<div class="login-container">
    <div class="login-box">
        
        <h1 class="judul-text">Diamond Store Mobile Legends</h1>
        <div class="gambar-wrapper">
        <img src="../assets/images/background.gif" alt="Dekorasi" class="gambar-di-bawah">
    </div>
    </div>
    <div class="login-box">
        <h2>Masuk ke Akun Anda</h2>
        <br>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="process.php?action=login" method="post">
            <div class="form-group">
                <label for="username">Username/E-mail</label>
                <input type="text" id="username" name="username" placeholder="example@yourmail.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Input password Anda" required>
            </div>
            <button type="submit" class="btn-login">Masuk</button>
        </form>
        
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>