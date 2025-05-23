<?php
include '../includes/config.php';
include '../includes/auth_functions.php';

check_login();

if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];

    // Hapus log transaksi
    $stmt1 = $conn->prepare("
        DELETE lt FROM log_transaksi lt
        INNER JOIN transaksi t ON lt.id_transaksi = t.id_transaksi
        WHERE t.id_pengguna = ?
    ");
    $stmt1->bind_param('i', $hapus_id);
    $stmt1->execute();

    // Hapus transaksi
    $stmt2 = $conn->prepare("DELETE FROM transaksi WHERE id_pengguna = ?");
    $stmt2->bind_param('i', $hapus_id);
    $stmt2->execute();

    // Hapus data game pengguna
    $stmt3 = $conn->prepare("DELETE FROM data_game_pengguna WHERE id_pengguna = ?");
    $stmt3->bind_param('i', $hapus_id);
    $stmt3->execute();

    // Hapus pengguna
    $stmt4 = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
    $stmt4->bind_param('i', $hapus_id);
    $stmt4->execute();

    if ($stmt4->affected_rows > 0) {
        $_SESSION['success_message'] = 'Pengguna berhasil dihapus.';
    } else {
        $_SESSION['error_message'] = 'Gagal menghapus pengguna.';
    }

    header('Location: users.php');
    exit();
}


// Ambil data pengguna beserta data game terkait (jika ada)
$query = "
    SELECT p.id_pengguna, p.username, p.email, p.peran, p.dibuat_pada, dgp.id_game, dgp.id_server, dgp.nickname
    FROM pengguna p
    LEFT JOIN data_game_pengguna dgp ON p.id_pengguna = dgp.id_pengguna
    ORDER BY p.dibuat_pada DESC
";
$result = $conn->query($query);

include '../includes/header.php';
?>

<!-- Inline CSS Modern -->
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Segoe+UI&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f8f9fa;
        color: #2c3e50;
        margin: 0;
        padding: 0;
    }

    .admin-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 25px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    h1 {
        font-size: 28px;
        margin-bottom: 20px;
    }

    .btn-back {
        display: inline-block;
        margin-bottom: 15px;
        padding: 10px 18px;
        background: #555;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        transition: background 0.3s ease;
    }

    .btn-back:hover {
        background: #333;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    }

    .admin-table thead {
        background-color: #2c3e50;
        color: white;
    }

    .admin-table th,
    .admin-table td {
        padding: 14px 18px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .admin-table tbody tr:hover {
        background-color: #f1f1f1;
    }

    .btn-action {
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 14px;
        color: white;
        transition: all 0.2s ease-in-out;
    }

    .btn-delete {
        background-color: #e74c3c;
    }

    .btn-delete:hover {
        background-color: #c0392b;
    }

    .alert {
        padding: 12px 18px;
        margin-bottom: 20px;
        border-radius: 6px;
        font-weight: bold;
    }

    .alert.success {
        background-color: #2ecc71;
        color: white;
    }

    .alert.error {
        background-color: #e74c3c;
        color: white;
    }

    @media (max-width: 768px) {
        .admin-table th, .admin-table td {
            font-size: 13px;
            padding: 10px;
        }

        .btn-back {
            padding: 8px 12px;
            font-size: 14px;
        }

        .btn-action {
            font-size: 12px;
            padding: 6px 10px;
        }
    }
</style>

<div class="admin-container">
    <a href="../admin/dashboard.php" class="btn-back">
        &larr; Kembali ke Dashboard
    </a>
    <h1>Kelola Pengguna</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert error"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID Pengguna</th>
                <th>Username</th>
                <th>Email</th>
                <th>Peran</th>
                <th>Nickname Game</th>
                <th>ID Game</th>
                <th>ID Server</th>
                <th>Dibuat Pada</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id_pengguna']; ?></td>
                    <td><?= htmlspecialchars($user['username']); ?></td>
                    <td><?= htmlspecialchars($user['email']); ?></td>
                    <td><?= htmlspecialchars($user['peran']); ?></td>
                    <td><?= htmlspecialchars($user['nickname'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($user['id_game'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($user['id_server'] ?? '-'); ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($user['dibuat_pada'])); ?></td>
                    <td>
                        <a href="users.php?hapus_id=<?= $user['id_pengguna']; ?>"
                           class="btn-action btn-delete"
                           onclick="return confirm('Yakin ingin menghapus pengguna ini beserta data terkait?');"
                           title="Hapus Pengguna">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
