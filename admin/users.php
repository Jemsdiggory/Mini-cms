<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — GameZone Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
session_start();
include("../config/database.php");
include("../config/csrf.php");

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

$me_id   = intval($_SESSION['admin_id']);
$success = '';
$error   = '';

// delete users
if(isset($_GET['delete'])){
    $del_id = intval($_GET['delete']);

    if($del_id === $me_id){
        $error = 'Kamu tidak bisa menghapus akunmu sendiri.';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $del_id);
        mysqli_stmt_execute($stmt);
        $success = 'User berhasil dihapus.';
    }
}

// add users
if(isset($_POST['add_user'])){
    csrf_verify();
    $new_username = trim($_POST['username']);
    $new_password = $_POST['password'];
    $confirm      = $_POST['confirm_password'];

    if(empty($new_username) || empty($new_password)){
        $error = 'Username dan password tidak boleh kosong.';
    } elseif(strlen($new_username) < 3){
        $error = 'Username minimal 3 karakter.';
    } elseif(strlen($new_password) < 6){
        $error = 'Password minimal 6 karakter.';
    } elseif($new_password !== $confirm){
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek duplikat username
        $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($cek, "s", $new_username);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if(mysqli_stmt_num_rows($cek) > 0){
            $error = 'Username sudah dipakai, pilih yang lain.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $ins = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins, "ss", $new_username, $hashed);
            $result = mysqli_stmt_execute($ins);

            if($result){
                $success = 'User <strong>' . htmlspecialchars($new_username) . '</strong> berhasil ditambahkan.';
            } else {
                $error = 'Gagal menyimpan user, coba lagi.';
            }
        }
    }
}

// take all users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
$total = mysqli_num_rows($users);
?>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_post.php">Add Post</a></li>
        <li><a href="users.php" class="active">Users</a></li>
        <li><a href="logout.php" class="nav-logout">Logout</a></li>
    </ul>
</nav>

<div class="container">

    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <div class="dashboard-header">
        <div class="page-header" style="margin-bottom:0;">
            <h2>Manage <span class="accent">Users</span></h2>
            <p class="sub">// ADMIN ACCOUNTS</p>
        </div>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success fade-up"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="alert alert-error fade-up"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- list users -->
    <div class="table-wrapper fade-up" style="margin-bottom:40px;">
        <div class="table-header">
            <span class="table-title">All Admins</span>
            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);letter-spacing:1px;">
                <?php echo $total; ?> USER(S)
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            while($row = mysqli_fetch_assoc($users)):
                $is_me     = ($row['id'] == $me_id);
                $info      = password_get_info($row['password']);
                $is_hashed = isset($info['algo']) && $info['algo'] !== 0 && $info['algo'] !== null;
            ?>
            <tr>
                <td><span class="post-num"><?php echo str_pad($no, 2, '0', STR_PAD_LEFT); ?></span></td>
                <td class="td-title">
                    <?php echo htmlspecialchars($row['username']); ?>
                    <?php if($is_me): ?>
                    <span style="font-family:var(--font-mono);font-size:10px;color:var(--neon-red);margin-left:8px;">[YOU]</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($is_hashed): ?>
                    <span style="font-family:var(--font-mono);font-size:11px;color:#00ff64;">✓ Hashed</span>
                    <?php else: ?>
                    <span style="font-family:var(--font-mono);font-size:11px;color:#ffaa00;">⚠ Plain text</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="td-actions">
                        <?php if(!$is_me): ?>
                        <a class="btn btn-danger"
                           href="users.php?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Hapus user <?php echo htmlspecialchars($row['username']); ?>?')">
                           Delete
                        </a>
                        <?php else: ?>
                        <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);">—</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php $no++; endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- form tambah user -->
    <div class="form-wrapper" style="max-width:520px;margin:0;">
        <div class="form-box fade-up fade-up-2">
            <div class="form-title">Add New User</div>
            <div class="form-subtitle">// Password akan di-hash otomatis sebelum disimpan</div>

            <form method="POST" action="users.php">

                <?php csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input class="form-input" type="text" name="username"
                           placeholder="Minimal 3 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input class="form-input" type="password" name="password"
                           placeholder="Minimal 6 karakter" required id="pw1"
                           oninput="checkMatch()">
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input class="form-input" type="password" name="confirm_password"
                           placeholder="Ulangi password" required id="pw2"
                           oninput="checkMatch()">
                    <p id="match-msg" style="font-family:var(--font-mono);font-size:11px;margin-top:6px;display:none;"></p>
                </div>

                <button class="form-button" type="submit" name="add_user">Create User</button>

            </form>
        </div>
    </div>

</div>

<footer class="footer">
    <div class="footer-brand"><span>Game</span>Zone</div>
    <div class="footer-meta">ADMIN PANEL — © <?php echo date('Y'); ?></div>
</footer>

<script>
function checkMatch(){
    const pw1 = document.getElementById('pw1').value;
    const pw2 = document.getElementById('pw2').value;
    const msg = document.getElementById('match-msg');
    if(pw2.length === 0){ msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if(pw1 === pw2){
        msg.textContent = '✓ Password cocok';
        msg.style.color = '#00ff64';
    } else {
        msg.textContent = '✗ Password tidak cocok';
        msg.style.color = '#ff4444';
    }
}
</script>

</body>
</html>