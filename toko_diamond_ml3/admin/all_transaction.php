<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Ambil filter dan pencarian
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Kueri transaksi
$query = "SELECT t.id_transaksi, t.tanggal_transaksi, t.status, t.jumlah,
                 u.username, t.id_game, t.id_server,
                 pd.nama_paket, mp.nama_metode
          FROM transaksi t
          JOIN pengguna u ON t.id_pengguna = u.id_pengguna
          JOIN paket_diamond pd ON t.id_paket = pd.id_paket
          JOIN metode_pembayaran mp ON t.id_metode = mp.id_metode
          WHERE 1=1";

if ($status_filter && $status_filter !== 'all') {
    $query .= " AND t.status = '$status_filter'";
}

if ($search_query) {
    $query .= " AND (
        u.username LIKE '%$search_query%' OR 
        t.id_game LIKE '%$search_query%' OR 
        t.id_server LIKE '%$search_query%' OR 
        t.id_transaksi = '$search_query'
    )";
}

$query .= " ORDER BY t.tanggal_transaksi DESC";

$transactions = $conn->query($query);

include '../includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
}

/* Admin Container */
.admin-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 30px;
    background-color: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

/* Header Styles */
.admin-container h1 {
    font-size: 28px;
    margin-bottom: 25px;
    color: var(--primary);
    font-weight: 600;
}

/* Back Button */
.btn-back {
    display: inline-flex;
    align-items: center;
    padding: 10px 15px;
    background-color: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: var(--border-radius);
    font-weight: 500;
    transition: var(--transition);
    margin-bottom: 20px;
}

.btn-back:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
}

/* Filter and Search */
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background-color: var(--light);
    border-radius: var(--border-radius);
}

.search-form {
    display: flex;
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-form input {
    width: 100%;
    padding: 12px 15px 12px 40px;
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    font-size: 16px;
    transition: var(--transition);
}

.search-form input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.search-form button {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--gray);
    cursor: pointer;
}

/* Status Filter */
select#status {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    font-size: 16px;
    background-color: white;
    cursor: pointer;
    transition: var(--transition);
}

select#status:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Table Styles */
.admin-table-container {
    overflow-x: auto;
    border-radius: var(--border-radius);
    box-shadow: 0 0 0 1px #eee;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.admin-table thead {
    background-color: var(--primary);
    color: white;
}

.admin-table th {
    padding: 15px;
    text-align: left;
    font-weight: 500;
}

.admin-table tbody tr {
    border-bottom: 1px solid #eee;
    transition: var(--transition);
}

.admin-table tbody tr:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

.admin-table td {
    padding: 15px;
    vertical-align: middle;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    text-transform: capitalize;
}

.status-badge.menunggu {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.selesai {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.gagal {
    background-color: #f8d7da;
    color: #721c24;
}

/* Action Buttons */
.btn-action {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    gap: 5px;
}

.btn-view {
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary);
}

.btn-view:hover {
    background-color: rgba(67, 97, 238, 0.2);
}

/* Empty State */
.admin-table tbody tr td[colspan] {
    text-align: center;
    padding: 30px;
    color: var(--gray);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .admin-container {
        padding: 20px;
    }
    
    .admin-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form {
        min-width: 100%;
    }
    
    select#status {
        width: 100%;
    }
}
</style>

<div class="admin-container">
    <a href="../admin/dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>
    <h1>Semua Transaksi</h1>

    <!-- Filter dan pencarian -->
    <div class="admin-header">
        <form method="get" class="search-form">
            <input type="text" name="search" placeholder="Cari username / ID Transaksi / Game" value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <form method="get" style="display: flex; align-items: center; gap: 10px;">
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

    <!-- Tabel transaksi -->
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
                    <?php while ($row = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id_transaksi']; ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td><?= htmlspecialchars($row['nama_paket']); ?></td>
                            <td><?= htmlspecialchars($row['nama_metode']); ?></td>
                            <td><?= htmlspecialchars($row['id_game']); ?></td>
                            <td><?= htmlspecialchars($row['id_server']); ?></td>
                            <td>Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge <?= $row['status']; ?>">
                                    <?= ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="transactions.php?detail=<?= $row['id_transaksi']; ?>" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
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
</div>

<?php include '../includes/footer.php'; ?>