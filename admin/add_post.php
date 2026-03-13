<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Post — GameZone Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
session_start();
include("../config/database.php");
include("../config/csrf.php");
include("../config/slug.php");

if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit(); }

$error = '';

if(isset($_POST['title'])){
    csrf_verify();

    $title       = trim($_POST['title']);
    $content     = trim($_POST['content']);
    $author_id   = intval($_SESSION['admin_id']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $status      = isset($_POST['status']) && $_POST['status'] === 'draft' ? 'draft' : 'published';

    // generate slug dari judul
    $slug = unique_slug($conn, generate_slug($title));

    $image = '';
    if(!empty($_FILES['image']['name'])){
        $image = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO posts (title, slug, content, image, author_id, category_id, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "ssssiss", $title, $slug, $content, $image, $author_id, $category_id, $status);

    if(mysqli_stmt_execute($stmt)){ header("Location: dashboard.php"); exit(); }
    else { $error = 'Gagal menyimpan artikel. Coba lagi.'; }
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
?>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_post.php" class="active">Add Post</a></li>
        <li><a href="categories.php">Categories</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="logout.php" class="nav-logout">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <?php if($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-wrapper" style="max-width:780px;">
        <div class="form-box fade-up">
            <div class="form-title">New Article</div>
            <div class="form-subtitle">// CREATE NEW CONTENT</div>

            <form method="POST" action="add_post.php" enctype="multipart/form-data">

                <?php csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label">Article Title</label>
                    <input class="form-input" type="text" name="title"
                           id="titleInput"
                           placeholder="Enter article title..."
                           oninput="previewSlug(this.value)"
                           required>
                </div>

                <!-- Preview slug real-time -->
                <div class="form-group">
                    <label class="form-label">URL Slug Preview</label>
                    <div class="slug-preview" id="slugPreview">
                        <span class="slug-base">post.php?slug=</span><span id="slugValue">—</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-input form-select" name="category_id">
                        <option value="">— Uncategorized —</option>
                        <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Content</label>
                    <textarea id="editor" name="content"
                              placeholder="Write article content here..."
                              style="min-height:320px;"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Cover Image</label>
                    <div class="form-file-wrapper" onclick="document.getElementById('fileInput').click()">
                        <input type="file" name="image" id="fileInput" accept="image/*"
                               onchange="document.getElementById('fileName').textContent = this.files[0] ? this.files[0].name : 'Choose file...'">
                        <span class="form-file-label">
                            <span id="fileName">Choose file...</span><br>
                            <span style="color:var(--text-muted);font-size:11px;">Click to <span>browse</span> — JPG, PNG, WEBP</span>
                        </span>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-ghost" type="submit" name="status" value="draft">Save as Draft</button>
                    <button class="btn btn-primary" type="submit" name="status" value="published">Publish Now</button>
                </div>

            </form>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="footer-brand"><span>Game</span>Zone</div>
    <div class="footer-meta">ADMIN PANEL — © <?php echo date('Y'); ?></div>
</footer>

<script src="https://cdn.tiny.cloud/1/seots5ng6jehh2l1242db0oo1phr3d0ohpuxm7bcee9g7l5p/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>

function generateSlug(val){
    return val.toLowerCase()
              .replace(/[áàâäãå]/g,'a').replace(/[éèêë]/g,'e')
              .replace(/[íìîï]/g,'i').replace(/[óòôöõ]/g,'o')
              .replace(/[úùûü]/g,'u').replace(/[ý]/g,'y')
              .replace(/[ñ]/g,'n').replace(/[ç]/g,'c')
              .replace(/[^a-z0-9\s-]/g,'')
              .trim()
              .replace(/[\s-]+/g,'-')
              .replace(/^-+|-+$/g,'');
}

function previewSlug(val){
    const slug = generateSlug(val);
    document.getElementById('slugValue').textContent = slug || '—';
}

tinymce.init({
    selector: '#editor',
    plugins: 'lists link image code table codesample emoticons',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | '
           + 'forecolor backcolor | alignleft aligncenter alignright | '
           + 'bullist numlist | link image | table codesample | emoticons | code',
    skin: 'oxide-dark',
    content_css: 'dark',
    content_style: `
        body { font-family:'Rajdhani',sans-serif; font-size:16px;
               background:#0d0d16; color:#e8e8f0; padding:16px 20px; line-height:1.8; }
        p { margin: 0 0 1em 0; } h1,h2,h3,h4 { color:#fff; }
        a { color:#ff0044; } pre { background:#08080e; padding:12px; border-radius:4px; }
    `,
    menubar: false, branding: false, promotion: false, resize: true, min_height: 360,
    setup: function(editor){ editor.on('change', function(){ editor.save(); }); }
});
</script>

</body>
</html>