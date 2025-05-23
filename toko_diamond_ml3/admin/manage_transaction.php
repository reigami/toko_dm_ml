<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id_transaksi = intval($_GET['id']);
    $status_baru = sanitize_input($_GET['update_status']);
    $catatan = "Diperbarui oleh admin";

    $allowed_statuses = ['menunggu', 'selesai', 'gagal'];
    if (in_array($status_baru, $allowed_statuses)) {
        try {
            $stmt = $conn->prepare("CALL sp_perbarui_status_transaksi(?, ?, ?)");
            $stmt->bind_param("iss", $id_transaksi, $status_baru, $catatan);
            $stmt->execute();
            $_SESSION['success'] = "Status berhasil diperbarui.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal memperbarui status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Status tidak valid.";
    }
    header('Location: manage_transaction.php');
    exit;
}

// Filter dan pencarian
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$query = "SELECT t.id_transaksi, t.tanggal_transaksi, t.status, t.jumlah, 
                 p.nama_paket, u.username, m.nama_metode, 
                 t.id_game, t.id_server
          FROM transaksi t
          JOIN pengguna u ON t.id_pengguna = u.id_pengguna
          JOIN paket_diamond p ON t.id_paket = p.id_paket
          JOIN metode_pembayaran m ON t.id_metode = m.id_metode
          WHERE 1=1";

if ($status_filter && $status_filter !== 'all') {
    $query .= " AND t.status = '$status_filter'";
}

if ($search_query) {
    $query .= " AND (
        u.username LIKE '%$search_query%' OR 
        t.id_transaksi = '$search_query' OR 
        t.id_game LIKE '%$search_query%' OR 
        t.id_server LIKE '%$search_query%'
    )";
}

$query .= " ORDER BY t.tanggal_transaksi DESC";
$transactions = $conn->query($query);

include '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <a href="../admin/dashboard.php" class="btn-back" style="display:inline-block; margin-bottom: 15px; padding: 8px 15px; background:#555; color:#fff; text-decoration:none; border-radius:4px;">
        &larr; Kembali ke Dashboard
    </a>
    <h1>Kelola Transaksi</h1>

    <!-- Filter dan Search -->
    <div class="admin-header">
        <form method="get" class="search-form">
            <input type="text" name="search" placeholder="Cari transaksi..." value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <form method="get" style="display:flex;align-items:center;gap:10px;">
            <label for="status">Status:</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                <option value="gagal" <?= $status_filter === 'gagal' ? 'selected' : ''; ?>>Gagal</option>
            </select>
            <?php if ($search_query): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Transaksi -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Username</th>
                    <th>Paket</th>
                    <th>Metode</th>
                    <th>ID Game</th>
                    <th>Server</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while ($trx = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?= $trx['id_transaksi']; ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])); ?></td>
                            <td><?= htmlspecialchars($trx['username']); ?></td>
                            <td><?= htmlspecialchars($trx['nama_paket']); ?></td>
                            <td><?= htmlspecialchars($trx['nama_metode']); ?></td>
                            <td><?= htmlspecialchars($trx['id_game']); ?></td>
                            <td><?= htmlspecialchars($trx['id_server']); ?></td>
                            <td>Rp <?= number_format($trx['jumlah'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge <?= $trx['status']; ?>">
                                    <?= ucfirst($trx['status']); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if ($trx['status'] !== 'selesai'): ?>
                                    <a href="?update_status=selesai&id=<?= $trx['id_transaksi']; ?>" class="btn-action btn-success" >
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($trx['status'] !== 'gagal'): ?>
                                    <a href="?update_status=gagal&id=<?= $trx['id_transaksi']; ?>" class="btn-action btn-danger" >
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">Tidak ada transaksi ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Konfirmasi -->
    <div id="confirmModal" class="modal">
      <div class="modal-content">
        <p id="confirmText">Apakah Anda yakin ingin mengubah status transaksi ini?</p>
        <div class="modal-buttons">
          <button id="confirmYes" class="btn btn-success">Ya</button>
          <button id="confirmNo" class="btn btn-danger">Tidak</button>
        </div>
      </div>
    </div>

    <style>
body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f6f9fc;
    color: #2c3e50;
    margin: 0;
    padding: 0;
}

.admin-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* Tombol kembali */
.btn-back {
    display:inline-block;
    margin-bottom: 15px;
    padding: 8px 15px;
    background:#555;
    color:#fff;
    text-decoration:none;
    border-radius:6px;
    transition: background 0.2s ease-in-out;
}

.btn-back:hover {
    background: #333;
}

/* Header & Filter */
.admin-header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 20px;
    gap: 10px;
    align-items: center;
}

.search-form input[type="text"] {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    width: 220px;
}

.search-form button {
    padding: 10px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.search-form button:hover {
    background-color: #2980b9;
}

select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* Tabel */
.admin-table-container {
    overflow-x: auto;
    margin-top: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.admin-table thead {
    background-color: #2c3e50;
    color: #ecf0f1;
}

.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.admin-table tbody tr:hover {
    background-color: #f8f9fa;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    display: inline-block;
    text-transform: capitalize;
}

.status-badge.menunggu {
    background-color: #f39c12;
}

.status-badge.selesai {
    background-color: #2ecc71;
}

.status-badge.gagal {
    background-color: #e74c3c;
}

/* Tombol Aksi */
.actions a.btn-action {
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    margin: 0 4px;
    display: inline-block;
    transition: all 0.2s ease-in-out;
}

.actions a.btn-success {
    background-color: #27ae60;
    color: #fff;
}

.actions a.btn-danger {
    background-color: #c0392b;
    color: #fff;
}

.actions a.btn-action:hover {
    opacity: 0.85;
}

/* Modal Konfirmasi */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    width: 320px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    text-align: center;
    font-size: 16px;
}

.modal-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.modal-buttons .btn {
    padding: 10px 18px;
    font-weight: bold;
    font-size: 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}

.modal-buttons .btn-success {
    background-color: #2ecc71;
    color: white;
}

.modal-buttons .btn-danger {
    background-color: #e74c3c;
    color: white;
}
</style>

    <script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('confirmModal');
  const confirmText = document.getElementById('confirmText');
  const btnYes = document.getElementById('confirmYes');
  const btnNo = document.getElementById('confirmNo');

  let urlToRedirect = '';

  document.querySelectorAll('.btn-action').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();

      const href = this.getAttribute('href'); // ?update_status=selesai&id=29

      // Path direktori sekarang (misalnya: /toko_diamond_ml/admin/manage_transactions.php)
      const currentPath = window.location.pathname;
      const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);

      // ✅ Redirect ke file manage_transactions.php (bukan ke /admin/)
      urlToRedirect = basePath + 'manage_transaction.php' + href;

      // Ubah teks konfirmasi sesuai tombol
      if (this.classList.contains('btn-success')) {
        confirmText.textContent = 'Tandai transaksi sebagai selesai?';
      } else if (this.classList.contains('btn-danger')) {
        confirmText.textContent = 'Tandai transaksi sebagai gagal?';
      }

      modal.style.display = 'block';
    });
  });

  btnYes.addEventListener('click', function() {
    modal.style.display = 'none';
    window.location.href = urlToRedirect;
  });

  btnNo.addEventListener('click', function() {
    modal.style.display = 'none';
    urlToRedirect = '';
  });

  window.addEventListener('click', function(event) {
    if (event.target == modal) {
      modal.style.display = 'none';
      urlToRedirect = '';
    }
  });
});
</script>

</div>

<?php include '../includes/footer.php'; ?>
