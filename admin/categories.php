<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories — GameZone Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
session_start();
include("../config/database.php");
include("../config/csrf.php");

if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit(); }

$success = '';
$error   = '';

// delete karegori
if(isset($_GET['delete'])){
    $del_id = intval($_GET['delete']);

    // cek kategori
    $cek = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM posts WHERE category_id = ?");
    mysqli_stmt_bind_param($cek, "i", $del_id);
    mysqli_stmt_execute($cek);
    $cek_row = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

    if($cek_row['total'] > 0){
        $error = 'Kategori masih dipakai oleh ' . $cek_row['total'] . ' artikel. Pindahkan artikelnya dulu sebelum hapus.';
    } else {
        $del = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $del_id);
        mysqli_stmt_execute($del);
        $success = 'Kategori berhasil dihapus.';
    }
}

// add katefori
if(isset($_POST['add_category'])){
    csrf_verify();
    $name = trim($_POST['name']);

    if(empty($name)){
        $error = 'Nama kategori tidak boleh kosong.';
    } elseif(strlen($name) < 2){
        $error = 'Nama kategori minimal 2 karakter.';
    } else {
        // generate slug dari yg aneh2 jadi normal
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', trim($slug));

        // Cek duplikat slug
        $cek = mysqli_prepare($conn, "SELECT id FROM categories WHERE slug = ?");
        mysqli_stmt_bind_param($cek, "s", $slug);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if(mysqli_stmt_num_rows($cek) > 0){
            $error = 'Kategori dengan nama tersebut sudah ada.';
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO categories (name, slug) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins, "ss", $name, $slug);
            if(mysqli_stmt_execute($ins)){
                $success = 'Kategori <strong>' . htmlspecialchars($name) . '</strong> berhasil ditambahkan.';
            } else {
                $error = 'Gagal menyimpan kategori.';
            }
        }
    }
}

// take all kategori with post count
$cats = mysqli_query($conn,
    "SELECT c.*, COUNT(p.id) as post_count
     FROM categories c
     LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name ASC"
);
$total = mysqli_num_rows($cats);
?>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_post.php">Add Post</a></li>
        <li><a href="categories.php" class="active">Categories</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="logout.php" class="nav-logout">Logout</a></li>
    </ul>
</nav>

<div class="container">

    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <div class="dashboard-header">
        <div class="page-header" style="margin-bottom:0;">
            <h2>Manage <span class="accent">Categories</span></h2>
            <p class="sub">// CONTENT CATEGORIES</p>
        </div>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success fade-up"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-error fade-up"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- list kategori -->
    <div class="table-wrapper fade-up" style="margin-bottom:40px;">
        <div class="table-header">
            <span class="table-title">All Categories</span>
            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);letter-spacing:1px;"><?php echo $total; ?> CATEGORIES</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Articles</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; while($row = mysqli_fetch_assoc($cats)): ?>
            <tr>
                <td><span class="post-num"><?php echo str_pad($no, 2, '0', STR_PAD_LEFT); ?></span></td>
                <td class="td-title"><?php echo htmlspecialchars($row['name']); ?></td>
                <td>
                    <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);">
                        <?php echo htmlspecialchars($row['slug']); ?>
                    </span>
                </td>
                <td>
                    <span style="font-family:var(--font-display);font-size:13px;color:<?php echo $row['post_count'] > 0 ? 'var(--neon-red)' : 'var(--text-muted)'; ?>;">
                        <?php echo $row['post_count']; ?>
                    </span>
                </td>
                <td>
                    <div class="td-actions">
                        <?php if($row['post_count'] == 0): ?>
                        <a class="btn btn-danger"
                           href="categories.php?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Hapus kategori <?php echo htmlspecialchars($row['name']); ?>?')">
                           Delete
                        </a>
                        <?php else: ?>
                        <span style="font-family:var(--font-mono);font-size:10px;color:var(--text-muted);">
                            Hapus artikel dulu
                        </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php $no++; endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- form tambah kategori -->
    <div class="form-wrapper" style="max-width:440px;margin:0;">
        <div class="form-box fade-up fade-up-2">
            <div class="form-title">Add Category</div>
            <div class="form-subtitle">// Slug di-generate otomatis dari nama</div>

            <form method="POST" action="categories.php">

                <?php csrf_field(); ?>
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input class="form-input" type="text" name="name"
                           placeholder="e.g. Tips &amp; Tricks"
                           oninput="previewSlug(this.value)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug Preview</label>
                    <div style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);
                                padding:10px 14px;background:var(--bg-input);border:1px solid var(--border-dim);
                                border-radius:3px;letter-spacing:1px;" id="slug-preview">
                        —
                    </div>
                </div>
                <button class="form-button" type="submit" name="add_category">Add Category</button>
            </form>
        </div>
    </div>

</div>

<footer class="footer">
    <div class="footer-brand"><span>Game</span>Zone</div>
    <div class="footer-meta">ADMIN PANEL — © <?php echo date('Y'); ?></div>
</footer>

<script>
function previewSlug(val){
    let slug = val.toLowerCase()
                  .replace(/[^a-z0-9\s-]/g, '')
                  .trim()
                  .replace(/[\s-]+/g, '-');
    document.getElementById('slug-preview').textContent = slug || '—';
}
</script>

</body>
</html>