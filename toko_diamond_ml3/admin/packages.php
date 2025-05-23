<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

// Tambah paket
if (isset($_POST['add_package'])) {
    $nama = sanitize_input($_POST['nama']);
    $jumlah = intval($_POST['jumlah']);
    $harga = floatval($_POST['harga']);
    $bonus = intval($_POST['bonus']);
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO paket_diamond (nama_paket, jumlah_diamond, harga, bonus_diamond, aktif) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siddi", $nama, $jumlah, $harga, $bonus, $aktif);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "Paket berhasil ditambahkan!";
    header("Location: packages.php");
    exit;
}

// Hapus paket
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM paket_diamond WHERE id_paket = $id");
    $_SESSION['success'] = "Paket berhasil dihapus!";
    header("Location: packages.php");
    exit;
}

// Ambil semua paket
$packages = $conn->query("SELECT * FROM paket_diamond ORDER BY harga ASC");

include '../includes/header.php';
?>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 0;
    color: #333;
}
.admin-container {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}
h1, h2 {
    margin-bottom: 25px;
    color: #222;
}
a.btn-back {
    display: inline-block;
    background: #607d8b;
    color: #fff;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    margin-bottom: 25px;
}
a.btn-back:hover {
    background: #455a64;
}
.form-container {
    background: #f9fbfc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    border: 1px solid #e0e0e0;
}
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.form-group {
    flex: 1;
    min-width: 200px;
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
}
input[type="text"],
input[type="number"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
}
.checkbox-group {
    margin-top: 30px;
    flex-direction: row;
    align-items: center;
    gap: 10px;
}
.form-actions {
    margin-top: 20px;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}
.btn-primary {
    background-color: #1976d2;
    color: white;
}
.btn-primary:hover {
    background-color: #1565c0;
}
.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
}
.admin-table th, .admin-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #ddd;
}
.admin-table th {
    background-color: #f1f3f5;
    font-weight: 600;
    color: #333;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
}
.status-badge.active {
    background-color: #4caf50;
    color: #fff;
}
.status-badge.inactive {
    background-color: #e53935;
    color: #fff;
}
.actions {
    display: flex;
    gap: 10px;
}
.btn-action {
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-edit {
    background-color: #ffa726;
    color: #fff;
}
.btn-edit:hover {
    background-color: #fb8c00;
}
.btn-delete {
    background-color: #e53935;
    color: #fff;
}
.btn-delete:hover {
    background-color: #c62828;
}
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    .actions {
        flex-direction: column;
    }
}
</style>


<div class="admin-container">
    <a href="../admin/dashboard.php" class="btn-back">
    &larr; Kembali ke Dashboard
</a>
    <h1>Kelola Paket Diamond</h1>

    <!-- Form Tambah Paket -->
    <div class="form-container">
        <h2>Tambah Paket Baru</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Paket</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Diamond</label>
                    <input type="number" name="jumlah" required>
                </div>
                <div class="form-group">
                    <label>Bonus Diamond</label>
                    <input type="number" name="bonus" value="0">
                </div>
                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" name="harga" step="100" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="aktif" checked>
                    <label>Aktif</label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_package" class="btn btn-primary">Tambah</button>
            </div>
        </form>
    </div>

    <!-- Daftar Paket -->
    <h2>Daftar Paket</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Jumlah</th>
                <th>Bonus</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $packages->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_paket']); ?></td>
                    <td><?= $row['jumlah_diamond']; ?></td>
                    <td><?= $row['bonus_diamond']; ?></td>
                    <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td>
                        <span class="status-badge <?= $row['aktif'] ? 'active' : 'inactive'; ?>">
                            <?= $row['aktif'] ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="edit_package.php?id=<?= $row['id_paket']; ?>" class="btn-action btn-edit">
                            ✎ Edit
                        </a>
                        <a href="packages.php?delete=<?= $row['id_paket']; ?>" onclick="return confirm('Yakin ingin menghapus paket ini?')" class="btn-action btn-delete">
                            🗑 Hapus
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
