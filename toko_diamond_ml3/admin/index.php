<?php 
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$game_data = null;
$stmt = $conn->prepare("SELECT * FROM data_game_pengguna WHERE id_pengguna = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $game_data = $result->fetch_assoc();
}
$stmt->close();

$packages = $conn->query("SELECT * FROM vw_paket_aktif");
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #4361ee;
    --primary-dark: #3a56d4;
    --secondary: #3f37c9;
    --success: #4cc9f0;
    --danger: #f72585;
    --warning: #f8961e;
    --light: #f8f9fa;
    --dark: #212529;
    --gray: #6c757d;
    --light-gray: #e9ecef;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

/* Base Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

body {
    background-color: #f5f7ff;
    color: var(--dark);
    line-height: 1.6;
    padding: 0;
    min-height: 100vh;
}

/* Dark Mode */
body.dark-mode {
    background-color: #121212;
    color: #eee;
}

.dark-mode .dashboard-container {
    background-color: #1e1e1e;
}

.dark-mode .card,
.dark-mode .game-data,
.dark-mode .modal-content,
.dark-mode .method-card {
    background-color: #2d2d2d;
    color: #eee;
    border-color: #444;
}

.dark-mode .form-group input {
    background-color: #333;
    color: #eee;
    border-color: #444;
}

.dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4 {
    color: #fff;
}

/* Dark Mode Toggle */
.dark-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(0,0,0,0.1);
    color: var(--dark);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.dark-toggle:hover {
    transform: scale(1.1);
}

body.dark-mode .dark-toggle {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

/* Dashboard Layout */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
    background-color: white;
    min-height: 100vh;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
}

/* User Profile Section */
.user-profile {
    text-align: center;
    margin-bottom: 40px;
    padding: 20px;
    border-radius: var(--border-radius);
    background-color: var(--light);
}

.user-profile h2 {
    font-size: 24px;
    margin-bottom: 15px;
    color: var(--primary);
}

/* Game Data Section */
.game-data {
    background-color: white;
    padding: 20px;
    border-radius: var(--border-radius);
    margin: 20px auto;
    max-width: 500px;
    box-shadow: var(--box-shadow);
    text-align: center;
}

.game-data p {
    margin: 10px 0;
    font-size: 16px;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    margin: 5px;
}

.btn-save {
    background-color: var(--primary);
    color: white;
}

.btn-save:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
}

.btn-cancel {
    background-color: var(--light-gray);
    color: var(--dark);
}

.btn-cancel:hover {
    background-color: #ddd;
}

.btn-buy {
    background-color: var(--success);
    color: white;
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    font-weight: 600;
}

.btn-buy:hover {
    background-color: #3ab7d8;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
}

.btn-buy.disabled {
    background-color: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Game Data Form */
.game-data-form {
    background-color: white;
    padding: 25px;
    border-radius: var(--border-radius);
    margin: 20px auto;
    max-width: 500px;
    box-shadow: var(--box-shadow);
    display: none;
}

.game-data-form h3 {
    margin-bottom: 20px;
    text-align: center;
    color: var(--primary);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark);
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: var(--transition);
}

.form-group input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
}

/* Diamond Packages */
.diamond-packages {
    margin-top: 40px;
}

.diamond-packages h2 {
    text-align: center;
    margin-bottom: 30px;
    color: var(--primary);
    font-size: 24px;
}

.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.package-card {
    background-color: white;
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    text-align: center;
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.package-card h3 {
    color: var(--primary);
    margin-bottom: 15px;
    font-size: 18px;
}

.diamond-amount {
    font-size: 24px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 10px;
}

.diamond-amount .bonus {
    font-size: 14px;
    color: var(--success);
    display: block;
    margin-top: 5px;
}

.price {
    font-size: 22px;
    font-weight: 700;
    color: var(--primary);
    margin: 15px 0;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 500px;
    padding: 30px;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal .close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    cursor: pointer;
    color: var(--gray);
}

.modal h2 {
    text-align: center;
    margin-bottom: 25px;
    color: var(--primary);
}

.payment-methods {
    margin-bottom: 25px;
}

.method-card {
    background-color: var(--light);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: var(--transition);
    border: 1px solid #ddd;
}

.method-card:hover, .method-card.selected {
    border-color: var(--primary);
    background-color: rgba(67, 97, 238, 0.05);
}

.method-card h4 {
    color: var(--primary);
    margin-bottom: 5px;
}

.method-card p {
    color: var(--gray);
    font-size: 14px;
}

.btn-confirm-payment {
    background-color: var(--primary);
    color: white;
    width: 100%;
    padding: 14px;
    font-weight: 600;
    margin-top: 15px;
}

.btn-confirm-payment:hover {
    background-color: var(--primary-dark);
}

/* Processing Modal */
#paymentProcessingModal .modal-content {
    text-align: center;
    padding: 40px 30px;
}

.loader {
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--primary);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 20px 15px;
    }
    
    .packages-grid {
        grid-template-columns: 1fr;
    }
    
    .user-profile, .game-data, .game-data-form {
        padding: 15px;
    }
}

/* Animations */
.animate__animated {
    animation-duration: 0.5s;
}
</style>

<button id="darkToggle" class="dark-toggle" aria-label="Toggle Dark Mode" title="Toggle Dark Mode">🌙</button>

<div class="dashboard-container animate__animated animate__fadeIn">
    <div class="user-profile">
        <h2>Selamat datang, <?= htmlspecialchars($user['username']); ?></h2>

        <div class="game-data">
            <?php if ($game_data): ?>
                <p>ID Game: <?= htmlspecialchars($game_data['id_game']); ?></p>
                <p>Server: <?= htmlspecialchars($game_data['id_server']); ?></p>
                <p>Nickname: <?= htmlspecialchars($game_data['nickname']); ?></p>
                <button id="edit-game-data" class="btn btn-save">Edit Data Game</button>
            <?php else: ?>
                <p>Anda belum mengatur data game</p>
                <button id="add-game-data" class="btn btn-save">Tambah Data Game</button>
            <?php endif; ?>
                
            <div style="margin-top: 20px;">
                <a href="riwayat.php" class="btn btn-save" style="margin-right: 10px;">Riwayat Transaksi</a>
                <a href="logout.php" class="btn btn-cancel">Logout</a>
            </div>
        </div>
    </div>

    <div class="game-data-form" id="gameDataForm" style="display:none;">
        <h3><?= $game_data ? 'Edit' : 'Tambah'; ?> Data Game</h3>
        <form action="process.php?action=update_game_data" method="post">
            <div class="form-group">
                <label for="game_id">ID Game</label>
                <input type="text" id="game_id" name="game_id" value="<?= $game_data ? htmlspecialchars($game_data['id_game']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="server_id">Server ID</label>
                <input type="text" id="server_id" name="server_id" value="<?= $game_data ? htmlspecialchars($game_data['id_server']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="nickname">Nickname</label>
                <input type="text" id="nickname" name="nickname" value="<?= $game_data ? htmlspecialchars($game_data['nickname']) : ''; ?>" required>
            </div>
            <button type="submit" class="btn btn-save">Simpan</button>
            <button type="button" id="cancel-game-data" class="btn btn-cancel">Batal</button>
        </form>
    </div>
    

    <div class="diamond-packages">
        <h2>Pilihan Paket Diamond</h2>
        <div class="packages-grid">
            <?php while ($package = $packages->fetch_assoc()): ?>
                <div class="package-card animate__animated animate__zoomIn">
                    <h3><?= htmlspecialchars($package['nama_paket']); ?></h3>
                    <div class="diamond-amount">
                        <span><?= $package['jumlah_diamond']; ?></span>
                        <?php if ($package['bonus_diamond'] > 0): ?>
                            <span class="bonus">+<?= $package['bonus_diamond']; ?> Bonus</span>
                        <?php endif; ?>
                    </div>
                    <div class="price">Rp <?= number_format($package['harga'], 0, ',', '.'); ?></div>
                    <?php if ($game_data): ?>
                        <button class="btn btn-buy" data-package-id="<?= $package['id_paket']; ?>">Beli Sekarang</button>
                    <?php else: ?>
                        <button class="btn btn-buy disabled" disabled title="Harap isi data game terlebih dahulu">Beli Sekarang</button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="paymentModal" class="modal">
        <div class="modal-content animate__animated animate__fadeInUp">
            <span class="close">&times;</span>
            <h2>Metode Pembayaran</h2>
            <div class="payment-methods">
                <?php
                $methods = $conn->query("SELECT * FROM vw_metode_pembayaran_tersedia");
                while ($method = $methods->fetch_assoc()): ?>
                    <div class="method-card" data-method-id="<?= $method['id_metode']; ?>">
                        <h4><?= htmlspecialchars($method['nama_metode']); ?></h4>
                        <p><?= htmlspecialchars($method['deskripsi']); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
            <form id="paymentForm" action="process.php?action=process_payment" method="post">
                <input type="hidden" id="package_id" name="package_id">
                <input type="hidden" id="method_id" name="method_id">
                <button type="submit" class="btn btn-confirm-payment">Konfirmasi Pembayaran</button>
            </form>
        </div>
    </div>

    <div id="paymentProcessingModal" class="modal" style="display:none;">
        <div class="modal-content animate__animated animate__fadeInUp">
            <div class="processing-content">
                <div class="loader"></div>
                <h3>Memproses Pembayaran...</h3>
                <p>Harap tunggu sebentar</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle game data form
    document.getElementById('<?= $game_data ? "edit-game-data" : "add-game-data"; ?>').addEventListener('click', function() {
        document.getElementById('gameDataForm').style.display = 'block';
    });

    document.getElementById('cancel-game-data').addEventListener('click', function() {
        document.getElementById('gameDataForm').style.display = 'none';
    });

    // Payment modal
    const modal = document.getElementById("paymentModal");
    const span = document.getElementsByClassName("close")[0];

    document.querySelectorAll('.btn-buy:not(.disabled)').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('package_id').value = this.getAttribute('data-package-id');
            modal.style.display = "flex";
        });
    });

    span.onclick = function() {
        modal.style.display = "none";
    }

    document.querySelectorAll('.method-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.method-card').forEach(c => {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            document.getElementById('method_id').value = this.getAttribute('data-method-id');
        });
    });

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Dark mode toggle with icon change
    const darkToggleBtn = document.getElementById('darkToggle');

    // Load saved mode from localStorage
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
        darkToggleBtn.textContent = '☀️';
    }

    darkToggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        if(document.body.classList.contains('dark-mode')) {
            darkToggleBtn.textContent = '☀️';
            localStorage.setItem('darkMode', 'enabled');
        } else {
            darkToggleBtn.textContent = '🌙';
            localStorage.setItem('darkMode', 'disabled');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>