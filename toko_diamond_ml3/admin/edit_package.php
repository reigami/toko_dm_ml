<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();
if (!is_admin()) {
    header('Location: ../index.php');
    exit;
}

$id = intval($_GET['id']);
$paket = $conn->query("SELECT * FROM paket_diamond WHERE id_paket = $id")->fetch_assoc();

if (!$paket) {
    $_SESSION['error'] = "Paket tidak ditemukan!";
    header("Location: packages.php");
    exit;
}

if (isset($_POST['update_package'])) {
    $nama = sanitize_input($_POST['nama']);
    $jumlah = intval($_POST['jumlah']);
    $bonus = intval($_POST['bonus']);
    $harga = floatval($_POST['harga']);
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE paket_diamond SET nama_paket=?, jumlah_diamond=?, bonus_diamond=?, harga=?, aktif=? WHERE id_paket=?");
    $stmt->bind_param("siddii", $nama, $jumlah, $bonus, $harga, $aktif, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Paket berhasil diperbarui!";
    header("Location: packages.php");
    exit;
}

include '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <h1>Edit Paket Diamond</h1>

    <div class="form-container">
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Paket</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($paket['nama_paket']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Diamond</label>
                    <input type="number" name="jumlah" value="<?= $paket['jumlah_diamond']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Bonus Diamond</label>
                    <input type="number" name="bonus" value="<?= $paket['bonus_diamond']; ?>">
                </div>
                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" name="harga" step="100" value="<?= $paket['harga']; ?>" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="aktif" <?= $paket['aktif'] ? 'checked' : ''; ?>>
                    <label>Aktif</label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_package" class="btn btn-success">Simpan</button>
                <a href="packages.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
