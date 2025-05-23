<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

// Cek notifikasi dari session
$notifikasi = '';
if (isset($_SESSION['notifikasi'])) {
    $notifikasi = $_SESSION['notifikasi'];
    unset($_SESSION['notifikasi']);
}

// Total transaksi
$total_transaksi = $conn->query("SELECT COUNT(*) AS total FROM transaksi")->fetch_assoc()['total'] ?? 0;

// Pendapatan bulan ini
$pendapatan_bulan_ini = $conn->query("
    SELECT SUM(t.jumlah) AS total
    FROM transaksi t
    WHERE t.status = 'selesai'
    AND MONTH(t.tanggal_transaksi) = MONTH(CURRENT_DATE())
    AND YEAR(t.tanggal_transaksi) = YEAR(CURRENT_DATE())
")->fetch_assoc()['total'] ?? 0;

// Total pengguna
$total_pengguna = $conn->query("SELECT COUNT(*) AS total FROM pengguna")->fetch_assoc()['total'] ?? 0;

// Transaksi terbaru
$transaksi_terbaru = $conn->query("
    SELECT t.id_transaksi, u.username, pd.nama_paket, t.tanggal_transaksi, t.status 
    FROM transaksi t
    JOIN pengguna u ON t.id_pengguna = u.id_pengguna
    JOIN paket_diamond pd ON t.id_paket = pd.id_paket
    ORDER BY t.tanggal_transaksi DESC 
    LIMIT 5
");

include '../includes/header.php';
?>
<!-- Tombol Logout -->
<div style="display: flex; justify-content: flex-end; padding: 20px 30px;">
    <button class="btn-logout" onclick="showLogoutModal()">Logout</button>
</div>

<!-- Modal Konfirmasi Logout -->
<div id="logoutModal" class="modal-overlay">
    <div class="modal-content">
        <h3>Konfirmasi Logout</h3>
        <p>Apakah Anda yakin ingin logout dari sistem?</p>
        <div class="modal-buttons">
            <button onclick="confirmLogout()" class="btn-yes">Ya, Logout</button>
            <button onclick="hideLogoutModal()" class="btn-cancel">Batal</button>
        </div>
    </div>
</div>


<link rel="stylesheet" href="../assets/css/admin.css">
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

/* Logout Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
}

.modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 12px; /* BULAT */
    text-align: center;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    width: 90%;
    max-width: 400px;
}

.modal-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.modal-buttons .btn-yes,
.modal-buttons .btn-cancel {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: bold;
    border: none;
    cursor: pointer;
}

.modal-buttons .btn-yes {
    background-color: #f44336;
    color: white;
}

.modal-buttons .btn-yes:hover {
    background-color: #d32f2f;
}

.modal-buttons .btn-cancel {
    background-color: #ccc;
    color: #333;
}

.modal-buttons .btn-cancel:hover {
    background-color: #bbb;
}

/* Widget Dashboard */
.admin-container {
    padding: 30px;
}
.admin-widgets {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 40px;
}
.widget {
    background: #fff;
    padding: 20px;
    border-radius: 12px; /* BULAT */
    flex: 1;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    min-width: 250px;
}
.widget-icon {
    font-size: 24px;
    padding: 15px;
    border-radius: 50%; /* BULAT */
    color: #fff;
}

/* Table */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.admin-table th, .admin-table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}
.admin-table th {
    background-color: #f0f0f0;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 0.85rem;
    display: inline-block;
}
.status-badge.selesai {
    background-color: #4CAF50;
    color: white;
}
.status-badge.pending {
    background-color: #FFC107;
    color: black;
}
.status-badge.dibatalkan {
    background-color: #F44336;
    color: white;
}

/* Card Menu Cepat */
.action-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.action-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px; /* BULAT */
    flex: 1;
    min-width: 220px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.action-card:hover {
    transform: translateY(-5px);
}
.action-icon {
    font-size: 28px;
    padding: 15px;
    border-radius: 50%; /* BULAT */
    margin-bottom: 10px;
    color: white;
    display: inline-block;
}

/* Tombol Logout */
.btn-logout {
    background-color: #f44336;
    color: white;
    padding: 10px 20px;
    font-weight: bold;
    border: none;
    border-radius: 8px; /* BULAT */
    cursor: pointer;
    transition: background 0.3s;
}
.btn-logout:hover {
    background-color: #d32f2f;
}

/* Responsif */
@media (max-width: 768px) {
    .admin-widgets, .action-cards {
        flex-direction: column;
    }
}

/* Popup Notifikasi */
.popup-notifikasi {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background-color: #4CAF50;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: fadeIn 0.3s ease-in-out;
}
.popup-notifikasi.error {
    background-color: #f44336;
}
.popup-konten {
    display: flex;
    align-items: center;
    gap: 12px;
}
.tutup-popup {
    background: transparent;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}
.sembunyi {
    display: none;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<!-- Popup Notifikasi -->
<div id="popup-notifikasi" class="popup-notifikasi sembunyi">
  <div class="popup-konten">
    <span id="popup-pesan">Notifikasi</span>
    <button class="tutup-popup" onclick="tutupPopup()">×</button>
  </div>
</div>

<div class="admin-container">
    <h1>Dashboard Admin</h1>

    <div class="admin-widgets">
        <div class="widget">
            <div class="widget-icon" style="background-color: #4CAF50;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="widget-info">
                <h3>Total Transaksi</h3>
                <p><?= number_format($total_transaksi, 0, ',', '.'); ?></p>
            </div>
        </div>

        <div class="widget">
            <div class="widget-icon" style="background-color: #2196F3;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="widget-info">
                <h3>Pendapatan Bulan Ini</h3>
                <p>Rp <?= number_format($pendapatan_bulan_ini ?: 0, 0, ',', '.'); ?></p>
            </div>
        </div>

        <div class="widget">
            <div class="widget-icon" style="background-color: #FF5722;">
                <i class="fas fa-users"></i>
            </div>
            <div class="widget-info">
                <h3>Pengguna Terdaftar</h3>
                <p><?= number_format($total_pengguna, 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>

    <div class="admin-sections">
        <div class="recent-transactions">
            <h2>Transaksi Terbaru</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Username</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaksi = $transaksi_terbaru->fetch_assoc()): ?>
                        <tr>
                            <td><?= $transaksi['id_transaksi']; ?></td>
                            <td><?= htmlspecialchars($transaksi['username']); ?></td>
                            <td><?= htmlspecialchars($transaksi['nama_paket']); ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])); ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($transaksi['status']); ?>">
                                    <?= ucfirst($transaksi['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="transactions.php?detail=<?= $transaksi['id_transaksi']; ?>" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="view-all">
                <a href="all_transaction.php" class="btn-view-all">Lihat Semua Transaksi</a>
            </div>
        </div>

        <div class="quick-actions">

            <!-- Statistik Paket Populer -->
            <div class="dashboard-section">
                <h2>Paket Diamond Terpopuler</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nama Paket</th>
                            <th>Total Terjual</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $populer = $conn->query("SELECT * FROM vw_popularitas_paket ORDER BY total_terjual DESC LIMIT 5");
                        while ($row = $populer->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_paket']); ?></td>
                                <td><?= number_format($row['total_terjual'], 0, ',', '.'); ?></td>
                                <td>Rp <?= number_format($row['total_pendapatan'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Metode Pembayaran Aktif -->
            <div class="dashboard-section">
                <h2>Metode Pembayaran Tersedia</h2>
                <ul>
                    <?php
                    $metode = $conn->query("SELECT * FROM vw_metode_pembayaran_tersedia");
                    while ($row = $metode->fetch_assoc()):
                    ?>
                        <li><strong><?= htmlspecialchars($row['nama_metode']); ?>:</strong> <?= htmlspecialchars($row['deskripsi']); ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Pengguna Aktif -->
            <div class="dashboard-section">
                <h2>Pengguna Aktif & Riwayat Pembelian</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nickname Game</th>
                            <th>Total Pembelian</th>
                            <th>Total Pengeluaran</th>
                            <th>Pembelian Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pengguna_aktif = $conn->query("SELECT * FROM vw_riwayat_pembelian ORDER BY total_pengeluaran DESC LIMIT 5");
                        while ($row = $pengguna_aktif->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td><?= htmlspecialchars($row['nickname']); ?></td>
                                <td><?= $row['total_pembelian']; ?></td>
                                <td>Rp <?= number_format($row['total_pengeluaran'], 0, ',', '.'); ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['pembelian_terakhir'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Menu Cepat -->
            <h2>Menu Cepat</h2>
            <div class="action-cards">
                <a href="packages.php" class="action-card">
                    <div class="action-icon" style="background-color: #9C27B0;">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3>Kelola Paket Diamond</h3>
                    <p>Tambah, edit, atau hapus paket diamond</p>
                </a>

                <a href="manage_transaction.php" class="action-card">
                    <div class="action-icon" style="background-color: #3F51B5;">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>Kelola Transaksi</h3>
                    <p>Lihat dan verifikasi transaksi</p>
                </a>

                <a href="reports.php" class="action-card">
                    <div class="action-icon" style="background-color: #009688;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Laporan Penjualan</h3>
                    <p>Analisis performa penjualan</p>
                </a>

                <a href="users.php" class="action-card">
                    <div class="action-icon" style="background-color: #607D8B;">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>Kelola Pengguna</h3>
                    <p>Manajemen akun pengguna</p>
                </a>
            </div>
        </div>
    </div>
</div>


<!-- JS Notifikasi -->
<script>
function tampilkanPopup(pesan, tipe = 'success') {
    const popup = document.getElementById('popup-notifikasi');
    const teks = document.getElementById('popup-pesan');

    teks.innerText = pesan;
    popup.classList.remove('sembunyi', 'error');
    if (tipe === 'error') popup.classList.add('error');

    setTimeout(() => {
        popup.classList.add('sembunyi');
    }, 3000);
}

function tutupPopup() {
    document.getElementById('popup-notifikasi').classList.add('sembunyi');
}

// Tampilkan notifikasi jika ada
<?php if ($notifikasi): ?>
    document.addEventListener('DOMContentLoaded', () => {
        tampilkanPopup("<?= $notifikasi; ?>");
    });
<?php endif; ?>
</script>

<!-- CSS Notifikasi -->
<style>
.popup-notifikasi {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background-color: #4CAF50;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: fadeIn 0.3s ease-in-out;
}
.popup-notifikasi.error {
    background-color: #f44336;
}
.popup-konten {
    display: flex;
    align-items: center;
    gap: 12px;
}
.tutup-popup {
    background: transparent;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}
.sembunyi {
    display: none;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}

function hideLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function confirmLogout() {
    window.location.href = "logout.php";
}
</script>


<?php include '../includes/footer.php'; ?>
