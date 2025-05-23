<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

if (!isset($_SESSION['transaction_id'])) {
    $_SESSION['error'] = 'Tidak ada transaksi yang diproses';
    header('Location: index.php');
    exit;
}

$transaction_id = $_SESSION['transaction_id'];
$user_id = $_SESSION['user_id'];

// Ambil data transaksi
$stmt = $conn->prepare("SELECT t.*, p.nama_paket, p.harga, p.jumlah_diamond, p.bonus_diamond, 
                        m.nama_metode, m.deskripsi as metode_deskripsi,
                        u.username, u.email, dg.nickname, dg.id_game, dg.id_server
                        FROM transaksi t
                        JOIN paket_diamond p ON t.id_paket = p.id_paket
                        JOIN metode_pembayaran m ON t.id_metode = m.id_metode
                        JOIN pengguna u ON t.id_pengguna = u.id_pengguna
                        JOIN data_game_pengguna dg ON t.id_pengguna = dg.id_pengguna
                        WHERE t.id_transaksi = ? AND t.id_pengguna = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    $_SESSION['error'] = 'Transaksi tidak ditemukan atau tidak valid';
    header('Location: index.php');
    exit;
}

$total_payment = $transaction['harga'];
$expiry_time = date('d/m/Y H:i', strtotime('+24 hours'));

// Variabel untuk menyimpan input user
$payment_number = '';
$payment_amount = '';
$show_modal = false;

// Proses konfirmasi pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $payment_number = trim($_POST['payment_number']);
    $payment_amount = trim($_POST['payment_amount']);
    
    // Validasi input
    if (empty($payment_number)) {
        $_SESSION['error'] = 'Nomor pembayaran harus diisi';
    } elseif (!is_numeric($payment_amount) || $payment_amount != $total_payment) {
        $_SESSION['error'] = 'Nominal pembayaran harus sesuai dengan total harga (Rp ' . number_format($total_payment, 0, ',', '.') . ')';
    } else {
        // Jika validasi berhasil, set flag untuk menampilkan modal
        $show_modal = true;
        
        // Update status transaksi menjadi 'menunggu' (jika belum)
        if ($transaction['status'] !== 'menunggu') {
            $stmt = $conn->prepare("UPDATE transaksi SET status = 'menunggu' WHERE id_transaksi = ?");
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $stmt->close();
            
            // Refresh data transaksi
            $stmt = $conn->prepare("SELECT status FROM transaksi WHERE id_transaksi = ?");
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $transaction['status'] = $result['status'];
            $stmt->close();
        }
    }
}

include '../includes/header.php';
?>

<!-- Load Font Awesome dan Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --success-color: #4cc9f0;
    --danger-color: #f72585;
    --warning-color: #f8961e;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

body {
    background-color: #f5f7ff;
    color: #333;
    line-height: 1.6;
    padding: 20px;
}

.payment-container {
    max-width: 640px;
    margin: 30px auto;
}

/* Header Section */
.payment-header {
    text-align: center;
    margin-bottom: 30px;
}

.payment-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.payment-header p {
    color: #6c757d;
    font-size: 16px;
}

/* Payment Card */
.payment-card {
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    margin-bottom: 30px;
}

.payment-card-header {
    background-color: var(--primary-color);
    color: white;
    padding: 18px 25px;
    font-size: 18px;
    font-weight: 600;
}

.payment-card-body {
    padding: 25px;
}

/* Transaction Info */
.transaction-info {
    margin-bottom: 25px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #6c757d;
    font-weight: 500;
}

.info-value {
    font-weight: 600;
    color: var(--dark-color);
    text-align: right;
}

.info-value.amount {
    color: var(--primary-color);
    font-size: 18px;
}

/* Payment Form */
.payment-form .form-group {
    margin-bottom: 20px;
}

.payment-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #495057;
}

.payment-form label.required:after {
    content: " *";
    color: var(--danger-color);
}

.payment-form input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: var(--transition);
    background-color: #f8f9fa;
}

.payment-form input:focus {
    border-color: var(--primary-color);
    outline: none;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    width: 100%;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    margin-top: 15px;
}

.btn-outline:hover {
    background-color: rgba(67, 97, 238, 0.1);
}

/* Error Message */
.alert-error {
    background-color: #fff5f7;
    color: var(--danger-color);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    border-left: 4px solid var(--danger-color);
    display: flex;
    align-items: center;
}

.alert-error i {
    margin-right: 10px;
    font-size: 20px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background-color: white;
    border-radius: var(--border-radius);
    width: 100%;
    max-width: 500px;
    transform: translateY(20px);
    transition: var(--transition);
    position: relative;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.modal-overlay.active .modal-content {
    transform: translateY(0);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
    text-align: center;
}

.modal-icon {
    font-size: 60px;
    color: var(--success-color);
    margin-bottom: 15px;
}

.modal-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #f0f0f0;
    text-align: center;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .payment-container {
        padding: 0 15px;
    }
    
    .payment-card-body {
        padding: 20px;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Additional UI Elements */
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-success {
    background-color: #d4edda;
    color: #155724;
}

/* Input Validation */
.input-error {
    border-color: var(--danger-color) !important;
}

.error-message {
    color: var(--danger-color);
    font-size: 14px;
    margin-top: 5px;
    display: none;
}

.input-container {
    position: relative;
}

.input-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
}
</style>

<div class="payment-container">
    <div class="payment-header">
        <h1>Konfirmasi Pembayaran</h1>
        <p>Silakan lengkapi informasi pembayaran Anda</p>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>
    
    <div class="payment-card">
        <div class="payment-card-header">
            Informasi Transaksi
        </div>
        <div class="payment-card-body">
            <div class="transaction-info">
                <div class="info-row">
                    <span class="info-label">ID Transaksi:</span>
                    <span class="info-value"><?= $transaction_id; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paket Diamond:</span>
                    <span class="info-value"><?= htmlspecialchars($transaction['nama_paket']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Pembayaran:</span>
                    <span class="info-value amount">Rp <?= number_format($total_payment, 0, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Metode Pembayaran:</span>
                    <span class="info-value"><?= htmlspecialchars($transaction['nama_metode']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?= $transaction['status'] === 'menunggu' ? 'pending' : 'success' ?>">
                            <?= ucfirst($transaction['status']); ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <form method="POST" action="" class="payment-form">
                <div class="form-group">
                    <label for="payment_number" class="required">Nomor Tagihan</label>
                    <div class="input-container">
                        <input type="text" id="payment_number" name="payment_number" required
                               pattern="\d{10,}" 
                               title="Nomor tagihan harus berupa angka dan minimal 10 digit"
                               value="<?= htmlspecialchars($payment_number); ?>">
                        <i class="fas fa-receipt input-icon"></i>
                    </div>
                    <small class="error-message">Masukkan nomor tagihan minimal 10 digit angka</small>
                </div>
                
                <div class="form-group">
                    <label for="payment_amount" class="required">Nominal Pembayaran (Rp)</label>
                    <div class="input-container">
                        <input type="number" id="payment_amount" name="payment_amount" required
                               value="<?= htmlspecialchars($payment_amount); ?>">
                        <i class="fas fa-money-bill-wave input-icon"></i>
                    </div>
                    <small class="error-message">Harus sesuai dengan total pembayaran: Rp <?= number_format($total_payment, 0, ',', '.'); ?></small>
                </div>
                
                <button type="submit" name="confirm_payment" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Konfirmasi Pembayaran
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pembayaran -->
<div id="paymentConfirmationModal" class="modal-overlay <?= $show_modal ? 'active' : '' ?>">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="modal-title">Pembayaran Berhasil!</h2>
            <p>Transaksi Anda sedang <strong>menunggu konfirmasi dari admin</strong>.</p>
        </div>
        
        <div class="modal-body">
            <div class="transaction-info">
                <h3>Detail Transaksi</h3>
                <div class="info-row">
                    <span class="info-label">ID Transaksi:</span>
                    <span class="info-value"><?= $transaction_id; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paket Diamond:</span>
                    <span class="info-value"><?= htmlspecialchars($transaction['nama_paket']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Pembayaran:</span>
                    <span class="info-value amount">Rp <?= number_format($total_payment, 0, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Metode Pembayaran:</span>
                    <span class="info-value"><?= htmlspecialchars($transaction['nama_metode']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-pending">
                            <?= ucfirst($transaction['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nomor Referensi:</span>
                    <span class="info-value"><?= htmlspecialchars($payment_number); ?></span>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button id="backToHomeBtn" class="btn btn-primary">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tombol kembali ke beranda
    const backToHomeBtn = document.getElementById('backToHomeBtn');
    if (backToHomeBtn) {
        backToHomeBtn.addEventListener('click', function() {
            window.location.href = 'index.php';
        });
    }
    
    // Validasi nominal pembayaran
    const paymentAmountInput = document.getElementById('payment_amount');
    if (paymentAmountInput) {
        paymentAmountInput.addEventListener('change', function() {
            const requiredAmount = <?= $total_payment; ?>;
            if (parseFloat(this.value) !== requiredAmount) {
                this.classList.add('input-error');
                this.setCustomValidity('Nominal harus tepat Rp ' + requiredAmount.toLocaleString('id-ID'));
            } else {
                this.classList.remove('input-error');
                this.setCustomValidity('');
            }
        });
    }
    
    // Validasi nomor tagihan
    const paymentNumberInput = document.getElementById('payment_number');
    if (paymentNumberInput) {
        paymentNumberInput.addEventListener('input', function() {
            const input = this.value;
            const errorMessage = this.parentElement.nextElementSibling;

            // Cek apakah hanya angka dan panjang minimal 10 digit
            if (!/^\d{10,}$/.test(input)) {
                this.classList.add('input-error');
                errorMessage.style.display = 'block';
                this.setCustomValidity('Nomor tagihan harus berupa angka dan minimal 10 digit');
            } else {
                this.classList.remove('input-error');
                errorMessage.style.display = 'none';
                this.setCustomValidity('');
            }
        });
    }
    
    // Jika ada error, scroll ke atas form
    <?php if (isset($_SESSION['error'])): ?>
        window.scrollTo(0, 0);
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>