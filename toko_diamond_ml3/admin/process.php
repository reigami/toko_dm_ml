<?php
session_start();
include '../includes/config.php';
include '../includes/auth_functions.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        process_login();
        break;

    case 'register':
        process_register();
        break;

    case 'update_game_data':
        update_game_data();
        break;

    case 'process_payment':
        process_payment();
        break;

    case 'confirm_payment':
        confirm_payment();
        break;

    case 'cancel_payment':
        cancel_payment();
        break;

    default:
        $_SESSION['error'] = 'Aksi tidak valid';
        header('Location: login.php');
        exit;
}

// Cek jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_game = $_POST['id_game'];
    $id_paket = $_POST['id_paket'];
    $metode = $_POST['metode'];

    // Ambil detail paket
    $query = $conn->prepare("SELECT nama_paket, harga FROM paket_diamond WHERE id_paket = ?");
    $query->bind_param("i", $id_paket);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $paket = $result->fetch_assoc();

        // Generate kode unik
        $kode_unik = rand(100, 999);
        $total_harga = $paket['harga'] + $kode_unik;

        // Simpan transaksi ke database
        $stmt = $conn->prepare("INSERT INTO transaksi (id_game, id_paket, metode_pembayaran, kode_unik, total_bayar, status, tanggal_transaksi) VALUES (?, ?, ?, ?, ?, 'Menunggu Pembayaran', NOW())");
        $stmt->bind_param("sisii", $id_game, $id_paket, $metode, $kode_unik, $total_harga);

        if ($stmt->execute()) {
            // Simpan data transaksi untuk ditampilkan di popup
            $_SESSION['popup_transaksi'] = [
                'id_game' => $id_game,
                'paket' => $paket['nama_paket'],
                'harga' => $total_harga
            ];

            // Redirect ke halaman paket untuk memunculkan popup
            header("Location: packages.php");
            exit;
        } else {
            $_SESSION['error'] = "Gagal menyimpan transaksi.";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Paket tidak ditemukan.";
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}

function process_login() {
    global $conn;

    $username = sanitize_input($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Trim password

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username dan password harus diisi';
        header('Location: login.php');
        exit;
    }

    $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE $field = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Pastikan password dari database tidak memiliki spasi tersembunyi
        $db_password = trim($user['password']);

        if ($password === $db_password) {
            $peran = strtolower(trim($user['peran']));

            if ($peran === 'admin') {
                $_SESSION['user_id'] = $user['id_pengguna'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin';
                header('Location: dashboard.php');
                exit;

            } elseif ($peran === 'user') {
                $_SESSION['user_id'] = $user['id_pengguna'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'user';
                header('Location: index.php');
                exit;

            } else {
                $_SESSION['error'] = 'Peran tidak diizinkan untuk login';
            }
        } else {
            $_SESSION['error'] = 'Password salah';
        }
    } else {
        $_SESSION['error'] = 'Username atau email tidak ditemukan';
    }

    header('Location: login.php');
    exit;
}



function process_register() {
    global $conn;

    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Konfirmasi password tidak cocok';
        header('Location: register.php');
        exit;
    }

    // Simpan password secara langsung (plain text)
    try {
        $stmt = $conn->prepare("CALL sp_daftar_pengguna(?, ?, ?, 'user')");
        $stmt->bind_param("sss", $username, $password, $email);
        $stmt->execute();

        $_SESSION['success'] = 'Pendaftaran berhasil! Silakan login';
        header('Location: login.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = strpos($e->getMessage(), 'Username') !== false ? 
            'Username sudah digunakan' : 'Email sudah terdaftar';
        header('Location: register.php');
        exit;
    }
}


function update_game_data() {
    global $conn;

    check_login();

    $user_id = $_SESSION['user_id'];
    $game_id = sanitize_input($_POST['game_id']);
    $server_id = sanitize_input($_POST['server_id']);
    $nickname = sanitize_input($_POST['nickname']);

    try {
        $stmt = $conn->prepare("CALL sp_tambah_data_game(?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $game_id, $server_id, $nickname);
        $stmt->execute();

        $_SESSION['success'] = 'Data game berhasil diperbarui';
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

function cancel_payment() {
    global $conn;

    check_login();

    $transaction_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_transaksi = ? AND id_pengguna = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$transaction) {
        $_SESSION['error'] = 'Transaksi tidak ditemukan';
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare("UPDATE transaksi SET status = 'gagal' WHERE id_transaksi = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $stmt->close();

    $conn->query("INSERT INTO log_transaksi (id_transaksi, status_lama, status_baru, catatan) 
                 VALUES ($transaction_id, 'menunggu', 'gagal', 'Dibatalkan oleh pengguna')");

    $_SESSION['success'] = 'Transaksi telah dibatalkan';
    unset($_SESSION['transaction_id']);
    header('Location: index.php');
    exit;
}

// Tambahkan fungsi baru
function process_payment() {
    global $conn;
    
    check_login();
    
    $user_id = $_SESSION['user_id'];
    $package_id = intval($_POST['package_id']);
    $method_id = intval($_POST['method_id']);
    
    // Validasi data game
    $stmt = $conn->prepare("SELECT * FROM data_game_pengguna WHERE id_pengguna = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $game_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$game_data) {
        $_SESSION['error'] = 'Data game tidak ditemukan';
        header('Location: index.php');
        exit;
    }
    
    try {
        // Gunakan stored procedure untuk membuat transaksi
        $stmt = $conn->prepare("CALL sp_buat_transaksi(?, ?, ?, ?, ?, @id_transaksi)");
        $stmt->bind_param("iiiss", $user_id, $package_id, $method_id, $game_data['id_game'], $game_data['id_server']);
        $stmt->execute();
        
        // Dapatkan ID transaksi yang baru dibuat
        $result = $conn->query("SELECT @id_transaksi AS id_transaksi");
        $transaction_id = $result->fetch_assoc()['id_transaksi'];
        
        // Simpan ID transaksi di session
        $_SESSION['transaction_id'] = $transaction_id;
        
        header('Location: payment.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Gagal memproses pembayaran: ' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

function confirm_payment() {
    global $conn;
    
    check_login();
    
    $transaction_id = intval($_POST['transaction_id']);
    $user_id = $_SESSION['user_id'];
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Validasi kepemilikan transaksi
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_transaksi = ? AND id_pengguna = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$transaction) {
        $_SESSION['error'] = 'Transaksi tidak ditemukan';
        header('Location: index.php');
        exit;
    }
    
    // Proses upload bukti transfer jika ada
    $proof_path = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/payment_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $proof_filename = "proof_{$transaction_id}_{$user_id}." . $file_ext;
        $proof_path = $upload_dir . $proof_filename;
        
        // Validasi file
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['proof']['type'], $allowed_types)) {
            $_SESSION['error'] = 'Format file tidak didukung. Hanya JPG, PNG, atau PDF';
            header("Location: payment.php");
            exit;
        }
        
        if ($_FILES['proof']['size'] > $max_size) {
            $_SESSION['error'] = 'Ukuran file terlalu besar. Maksimal 2MB';
            header("Location: payment.php");
            exit;
        }
        
        if (!move_uploaded_file($_FILES['proof']['tmp_name'], $proof_path)) {
            $_SESSION['error'] = 'Gagal mengupload bukti transfer';
            header("Location: payment.php");
            exit;
        }
    }
    
    // Update status transaksi
    try {
        $stmt = $conn->prepare("CALL sp_perbarui_status_transaksi(?, 'selesai', ?)");
        $catatan = "Pembayaran dikonfirmasi oleh user" . ($notes ? " - Catatan: $notes" : "");
        $stmt->bind_param("is", $transaction_id, $catatan);
        $stmt->execute();
        
        // Simpan bukti transfer jika ada
        if ($proof_path) {
            $conn->query("UPDATE transaksi SET payment_proof = '$proof_path' WHERE id_transaksi = $transaction_id");
        }
        
        $_SESSION['success'] = 'Pembayaran berhasil dikonfirmasi! Diamond akan segera diproses.';
        unset($_SESSION['transaction_id']);
        header('Location: payment.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Gagal mengkonfirmasi pembayaran: ' . $e->getMessage();
        header('Location: payment.php');
        exit;
    }
}
