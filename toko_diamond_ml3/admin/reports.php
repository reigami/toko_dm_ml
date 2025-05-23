<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

function get_nama_bulan($bulan) {
    $nama_bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $nama_bulan[$bulan] ?? '';
}

$current_year = date('Y');
$selected_year = isset($_GET['tahun']) ? intval($_GET['tahun']) : $current_year;
$selected_package = isset($_GET['paket']) ? (is_numeric($_GET['paket']) ? intval($_GET['paket']) : null) : null;

$packages = $conn->query("SELECT id_paket, nama_paket FROM paket_diamond WHERE aktif = TRUE ORDER BY nama_paket");

$tanggal_mulai = $selected_year . "-01-01";
$tanggal_akhir = $selected_year . "-12-31";

$sql = "SELECT 
            DATE(t.tanggal_transaksi) AS tanggal,
            MONTH(t.tanggal_transaksi) AS bulan,
            t.id_paket,
            pd.nama_paket,
            COUNT(*) AS total_terjual,
            SUM(pd.harga) AS total_pendapatan
        FROM transaksi t
        JOIN paket_diamond pd ON t.id_paket = pd.id_paket
        WHERE t.status = 'selesai'
          AND t.tanggal_transaksi BETWEEN ? AND ?";

if ($selected_package) {
    $sql .= " AND t.id_paket = ?";
}

$sql .= " GROUP BY tanggal, t.id_paket ORDER BY tanggal ASC, pd.nama_paket ASC";

$stmt = $conn->prepare($sql);
if ($selected_package) {
    $stmt->bind_param("ssi", $tanggal_mulai, $tanggal_akhir, $selected_package);
} else {
    $stmt->bind_param("ss", $tanggal_mulai, $tanggal_akhir);
}
$stmt->execute();
$result = $stmt->get_result();

include '../includes/header.php';
?>

<style>
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fb;
    margin: 0;
    padding: 0;
    color: #333;
}
.admin-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}
.btn-back {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 18px;
    background: #607d8b;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.3s ease;
}
.btn-back:hover {
    background: #455a64;
}
h1 {
    font-size: 24px;
    margin-bottom: 30px;
}
.report-filters {
    margin-bottom: 30px;
}
.report-filters form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 200px;
}
.form-group label {
    font-weight: 600;
    margin-bottom: 6px;
}
select {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}
.btn-filter {
    background: #1976d2;
    color: white;
    padding: 10px 20px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    align-self: flex-end;
    transition: background 0.3s ease;
}
.btn-filter:hover {
    background: #1565c0;
}
.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
}
.report-table th, .report-table td {
    padding: 14px 16px;
    text-align: left;
}
.report-table th {
    background: #f1f3f5;
    color: #555;
    font-weight: 600;
}
.report-table tr:nth-child(even) {
    background: #f9f9f9;
}
.report-table tr:hover {
    background-color: #eef2f7;
}
.report-table td {
    color: #444;
}
.report-table tfoot td {
    font-weight: bold;
    background-color: #f0f0f0;
}
.report-export {
    margin-top: 25px;
    display: flex;
    gap: 15px;
}
.btn-export {
    background-color: #43a047;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background 0.3s ease;
}
.btn-export:hover {
    background-color: #388e3c;
}
@media (max-width: 768px) {
    .report-filters form {
        flex-direction: column;
    }
    .report-export {
        flex-direction: column;
    }
}
</style>

<div class="admin-container">
    <a href="../admin/dashboard.php" class="btn-back">
        &larr; Kembali ke Dashboard
    </a>
    <h1>Laporan Penjualan Per Tanggal Tahun <?= htmlspecialchars($selected_year); ?></h1>
    
    <div class="report-filters">
        <form method="get" action="">
            <div class="form-group">
                <label for="tahun">Tahun:</label>
                <select id="tahun" name="tahun">
                    <?php for ($year = $current_year; $year >= 2020; $year--): ?>
                        <option value="<?= $year; ?>" <?= $year == $selected_year ? 'selected' : ''; ?>>
                            <?= $year; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="paket">Paket Diamond:</label>
                <select id="paket" name="paket">
                    <option value="">Semua Paket</option>
                    <?php while ($package = $packages->fetch_assoc()): ?>
                        <option value="<?= $package['id_paket']; ?>" <?= $selected_package == $package['id_paket'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($package['nama_paket']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </form>
    </div>
    
    <div class="report-results">
        <?php if ($result->num_rows > 0): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Bulan</th>
                        <th>Paket Diamond</th>
                        <th>Total Terjual</th>
                        <th>Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total_sold = 0;
                    $grand_total_revenue = 0;

                    while ($row = $result->fetch_assoc()): 
                        $grand_total_sold += $row['total_terjual'];
                        $grand_total_revenue += $row['total_pendapatan'];
                    ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td><?= get_nama_bulan($row['bulan']); ?></td>
                            <td><?= htmlspecialchars($row['nama_paket']); ?></td>
                            <td><?= $row['total_terjual']; ?></td>
                            <td>Rp <?= number_format($row['total_pendapatan'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <tr style="font-weight:bold; background:#f0f0f0;">
                        <td colspan="3">Total Keseluruhan</td>
                        <td><?= $grand_total_sold; ?></td>
                        <td>Rp <?= number_format($grand_total_revenue, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
            <div class="report-export">
                <button class="btn-export" onclick="window.print()">Cetak Laporan</button>
            </div>
        <?php else: ?>
            <p>Tidak ada data penjualan untuk kriteria yang dipilih.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
