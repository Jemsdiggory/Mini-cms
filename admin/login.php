<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — GameZone</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
session_start();
include("../config/database.php");
include("../config/csrf.php");

$error = '';

if(isset($_POST['login'])){
    csrf_verify();
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // prepared statement untuk keamanan dari SQL injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data   = mysqli_fetch_assoc($result);

    if($data){
        $stored    = $data['password'];
        $valid     = false;
        $info      = password_get_info($stored);
        $is_hashed = isset($info['algo']) && $info['algo'] !== 0 && $info['algo'] !== null;

        if($is_hashed){
            // pw udah di hash, verifikasi pake password_verify
            $valid = password_verify($password, $stored);
        } else {
            // plain text
            $valid = ($password === $stored);

            // if cocok, lgsg hash
            if($valid){
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $uid    = intval($data['id']);
                $upd    = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($upd, "si", $hashed, $uid);
                mysqli_stmt_execute($upd);
            }
        }

        if($valid){
            $_SESSION['admin']    = $data['username'];
            $_SESSION['admin_id'] = $data['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Username atau password salah.';
        }

    } else {
        $error = 'Username atau password salah.';
    }
}
?>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
</nav>

<div class="container">
    <div class="form-wrapper">

        <?php if($error): ?>
        <div class="alert alert-error fade-up"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-box fade-up">
            <div class="form-title">Admin Login</div>
            <div class="form-subtitle">// RESTRICTED ACCESS — AUTHORIZED ONLY</div>

            <form method="POST" action="login.php">

                <?php csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input class="form-input" type="text" name="username"
                           placeholder="Enter username" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input class="form-input" type="password" name="password"
                           placeholder="••••••••" required>
                </div>

                <button class="form-button" type="submit" name="login">
                    Access System
                </button>

            </form>
        </div>

        <p style="text-align:center;margin-top:20px;">
            <a href="../index.php" class="back-link">Back to Blog</a>
        </p>

    </div>
</div>

</body>
</html>