<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Proses update status transaksi
if (isset($_GET['update_status'])) {
    $id_transaksi = intval($_GET['id']);
    $status_baru = sanitize_input($_GET['update_status']);
    
    try {
        $stmt = $conn->prepare("CALL sp_perbarui_status_transaksi(?, ?, ?)");
        $catatan = "Diupdate oleh admin";
        $stmt->bind_param("iss", $id_transaksi, $status_baru, $catatan);
        $stmt->execute();
        
        $_SESSION['success'] = "Status transaksi berhasil diupdate!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal update status: " . $e->getMessage();
    }
    
    $stmt->close();
    header('Location: transactions.php');
    exit;
}

// Ambil detail transaksi
$detail_transaksi = null;
if (isset($_GET['detail'])) {
    $id_transaksi = intval($_GET['detail']);
    $stmt = $conn->prepare("SELECT t.*, p.nama_paket, p.harga, m.nama_metode, u.username, u.email
                           FROM transaksi t
                           JOIN paket_diamond p ON t.id_paket = p.id_paket
                           JOIN metode_pembayaran m ON t.id_metode = m.id_metode
                           JOIN pengguna u ON t.id_pengguna = u.id_pengguna
                           WHERE t.id_transaksi = ?");
    $stmt->bind_param("i", $id_transaksi);
    $stmt->execute();
    $detail_transaksi = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Ambil riwayat log
    $logs = $conn->query("SELECT * FROM log_transaksi WHERE id_transaksi = $id_transaksi ORDER BY changed_at DESC");
}

// Ambil semua transaksi dengan filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$query = "SELECT t.id_transaksi, t.tanggal_transaksi, t.status, t.amount, 
                 p.nama_paket, u.username, m.nama_metode
          FROM transaksi t
          JOIN paket_diamond p ON t.id_paket = p.id_paket
          JOIN pengguna u ON t.id_pengguna = u.id_pengguna
          JOIN metode_pembayaran m ON t.id_metode = m.id_metode
          WHERE 1=1";

if ($status_filter && $status_filter !== 'all') {
    $query .= " AND t.status = '$status_filter'";
}

if ($search_query) {
    $query .= " AND (u.username LIKE '%$search_query%' OR t.id_game LIKE '%$search_query%' OR t.id_transaksi = '$search_query')";
}

$query .= " ORDER BY t.tanggal_transaksi DESC";
$transactions = $conn->query($query);

include '../includes/header.php';
?>

<div class="admin-container">
    <?php if ($detail_transaksi): ?>
        <div class="transaction-detail-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Detail Transaksi #<?= $detail_transaksi['id_transaksi']; ?></h2>
                    <a href="transactions.php" class="close-modal">&times;</a>
                </div>
                
                <div class="modal-body">
                    <div class="detail-row">
                        <div class="detail-group">
                            <h3>Informasi Transaksi</h3>
                            <p><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($detail_transaksi['tanggal_transaksi'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?= $detail_transaksi['status']; ?>">
                                    <?= ucfirst($detail_transaksi['status']); ?>
                                </span>
                            </p>
                            <p><strong>Total Pembayaran:</strong> Rp <?= number_format($detail_transaksi['harga'], 0, ',', '.'); ?></p>
                        </div>
                        
                        <div class="detail-group">
                            <h3>Detail Pembelian</h3>
                            <p><strong>Paket Diamond:</strong> <?= htmlspecialchars($detail_transaksi['nama_paket']); ?></p>
                            <p><strong>Metode Pembayaran:</strong> <?= htmlspecialchars($detail_transaksi['nama_metode']); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-group">
                            <h3>Data Pemesan</h3>
                            <p><strong>Username:</strong> <?= htmlspecialchars($detail_transaksi['username']); ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($detail_transaksi['email']); ?></p>
                            <p><strong>ID Game:</strong> <?= htmlspecialchars($detail_transaksi['id_game']); ?></p>
                            <p><strong>Server ID:</strong> <?= htmlspecialchars($detail_transaksi['id_server']); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <h3>Riwayat Status</h3>
                        <div class="timeline">
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <?= date('d/m/Y H:i', strtotime($log['changed_at'])); ?>
                                    </div>
                                    <div class="timeline-content">
                                        <p>
                                            <span class="status-change">
                                                <?= $log['status_lama'] ? ucfirst($log['status_lama']).' → ' : ''; ?>
                                                <?= ucfirst($log['status_baru']); ?>
                                            </span>
                                            <?= $log['catatan'] ? '<br>'.$log['catatan'] : ''; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="status-actions">
                        <?php if ($detail_transaksi['status'] !== 'selesai'): ?>
                            <a href="transactions.php?update_status=selesai&id=<?= $detail_transaksi['id_transaksi']; ?>" 
                               class="btn-success" onclick="return confirm('Tandai transaksi sebagai selesai?')">
                                <i class="fas fa-check"></i> Tandai Selesai
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($detail_transaksi['status'] !== 'gagal'): ?>
                            <a href="transactions.php?update_status=gagal&id=<?= $detail_transaksi['id_transaksi']; ?>" 
                               class="btn-danger" onclick="return confirm('Tandai transaksi sebagai gagal?')">
                                <i class="fas fa-times"></i> Tandai Gagal
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <a href="transactions.php" class="btn-secondary">Tutup</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="admin-header">
        <h1>Manajemen Transaksi</h1>
        <div class="header-actions">
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="Cari transaksi..." value="<?= htmlspecialchars($search_query); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
    
    <div class="filters">
        <form method="get">
            <div class="filter-group">
                <label for="status">Filter Status:</label>
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    <option value="gagal" <?= $status_filter === 'gagal' ? 'selected' : ''; ?>>Gagal</option>
                </select>
            </div>
            
            <?php if ($search_query): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
            <?php endif; ?>
        </form>
    </div>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Username</th>
                    <th>Paket</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($transaction = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td><?= $transaction['id_transaksi']; ?></td>
                        <td><?= date('d/m/Y', strtotime($transaction['tanggal_transaksi'])); ?></td>
                        <td><?= htmlspecialchars($transaction['username']); ?></td>
                        <td><?= htmlspecialchars($transaction['nama_paket']); ?></td>
                        <td><?= htmlspecialchars($transaction['nama_metode']); ?></td>
                        <td>Rp <?= number_format($transaction['amount'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="status-badge <?= $transaction['status']; ?>">
                                <?= ucfirst($transaction['status']); ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="transactions.php?detail=<?= $transaction['id_transaksi']; ?>" class="btn-action btn-view">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if ($transactions->num_rows === 0): ?>
            <div class="no-results">
                <p>Tidak ada transaksi yang ditemukan</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>