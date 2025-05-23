<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM vw_detail_transaksi WHERE id_pengguna = ? ORDER BY id_transaksi DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
/* Tambahan CSS modern */
.container {
    padding: 2rem;
}

h2 {
    font-size: 2rem;
    color: #2563eb; /* Biru */
    margin-bottom: 1.5rem;
    font-weight: bold;
}

.search-container {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.search-box {
    padding: 0.5rem 2.5rem;
    border: 1px solid #ccc;
    border-radius: 9999px;
    width: 100%;
    max-width: 300px;
    position: relative;
}

.search-container::before {
    content: '🔍';
    position: absolute;
    margin-left: 12px;
    color: #2563eb;
}

.styled-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #ccc;
    border-radius: 8px;
    overflow: hidden;
}

.styled-table thead {
    background-color: #1e3a8a;
    color: white;
    text-align: left;
}

.styled-table th, .styled-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #ddd;
}

.badge {
    padding: 5px 12px;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.875rem;
    display: inline-block;
    color: white;
}

.status-menunggu {
    background-color: #f59e0b; /* oranye */
}

.status-selesai {
    background-color: #10b981; /* hijau */
}

.back-link {
    display: inline-block;
    margin-top: 1.5rem;
    text-decoration: none;
    color: #2563eb;
    font-weight: 500;
}
</style>

<div class="container">
    <h2>Riwayat Transaksi</h2>

    <!-- Search box -->
    <div class="search-container relative">
        <input type="text" placeholder="Cari transaksi..." class="search-box" onkeyup="searchTable(this)">
    </div>

    <!-- Table -->
    <table class="styled-table" id="riwayatTable">
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
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id_transaksi'] ?></td>
                    <td><?= $row['tanggal_transaksi'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['nama_paket']) ?></td>
                    <td><?= htmlspecialchars($row['nama_metode']) ?></td>
                    <td><?= $row['id_game'] ?></td>
                    <td><?= isset($row['server']) ? htmlspecialchars($row['server']) : '-' ?></td>
                    <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                    <td>
                        <span class="badge status-<?= strtolower($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="index.php" class="back-link">← Kembali ke Dashboard</a>
</div>

<script>
function searchTable(input) {
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#riwayatTable tbody tr");
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}
</script>

<?php include '../includes/footer.php'; ?>
