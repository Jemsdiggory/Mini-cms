<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    session_start();
    include("config/database.php");
    include("config/csrf.php");

    // Support ?slug= dan ?id= (backward compat)
    if(!empty($_GET['slug'])){
        $slug = trim($_GET['slug']);
        $stmt = mysqli_prepare($conn,
            "SELECT p.*, c.name as cat_name, c.slug as cat_slug
             FROM posts p LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.slug = ? AND p.status = 'published'"
        );
        mysqli_stmt_bind_param($stmt, "s", $slug);
    } else {
        $id = intval($_GET['id'] ?? 0);
        $stmt = mysqli_prepare($conn,
            "SELECT p.*, c.name as cat_name, c.slug as cat_slug
             FROM posts p LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = ? AND p.status = 'published'"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
    }
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$row){ header("Location: index.php"); exit(); }

    // Redirect ?id= ke ?slug=
    if(empty($_GET['slug']) && !empty($row['slug'])){
        header("Location: post.php?slug=" . urlencode($row['slug']), true, 301);
        exit();
    }

    $post_id    = $row['id'];
    $post_slug  = $row['slug'] ?? '';

    // proses submit comments
    $comment_success = false;
    $comment_error   = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])){
        csrf_verify();

        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $comment = trim($_POST['comment'] ?? '');

        // validate
        if(empty($name) || empty($email) || empty($comment)){
            $comment_error = 'Semua field harus diisi.';
        } elseif(strlen($name) < 2){
            $comment_error = 'Nama minimal 2 karakter.';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $comment_error = 'Format email tidak valid.';
        } elseif(strlen($comment) < 5){
            $comment_error = 'Komentar terlalu pendek.';
        } elseif(strlen($comment) > 2000){
            $comment_error = 'Komentar maksimal 2000 karakter.';
        } else {
            $stmt_ins = mysqli_prepare($conn,
                "INSERT INTO comments (post_id, name, email, comment) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_ins, "isss", $post_id, $name, $email, $comment);
            if(mysqli_stmt_execute($stmt_ins)){
                $comment_success = true;
            } else {
                $comment_error = 'Gagal menyimpan komentar. Coba lagi.';
            }
        }
    }

    // take comments yg approved
    $stmt_cmt = mysqli_prepare($conn,
        "SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC"
    );
    mysqli_stmt_bind_param($stmt_cmt, "i", $post_id);
    mysqli_stmt_execute($stmt_cmt);
    $comments     = mysqli_stmt_get_result($stmt_cmt);
    $comment_count = mysqli_num_rows($comments);
    ?>
    <title><?php echo htmlspecialchars($row['title']); ?> — GameZone</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .article-content h1,.article-content h2,.article-content h3,.article-content h4{
            font-family:var(--font-display);color:var(--text-primary);
            margin:1.6em 0 0.6em;letter-spacing:1px;
        }
        .article-content h2{font-size:24px;} .article-content h3{font-size:20px;}
        .article-content p{margin-bottom:1.2em;}
        .article-content a{color:var(--neon-red);text-decoration:underline;text-underline-offset:3px;}
        .article-content a:hover{text-shadow:var(--glow-red);}
        .article-content ul,.article-content ol{padding-left:1.6em;margin-bottom:1.2em;}
        .article-content li{margin-bottom:0.4em;}
        .article-content blockquote{
            border-left:3px solid var(--neon-red);padding:12px 20px;margin:1.4em 0;
            background:var(--bg-elevated);border-radius:0 4px 4px 0;
            color:var(--text-secondary);font-style:italic;
        }
        .article-content pre,.article-content code{
            font-family:var(--font-mono);font-size:13px;
            background:var(--bg-deep);border:1px solid var(--border-dim);border-radius:4px;
        }
        .article-content pre{padding:16px 20px;overflow-x:auto;margin-bottom:1.2em;}
        .article-content code{padding:2px 7px;}
        .article-content img{max-width:100%;border-radius:4px;border:1px solid var(--border-dim);margin:1em 0;}
        .article-content table{width:100%;border-collapse:collapse;margin-bottom:1.2em;font-size:14px;}
        .article-content th{
            background:var(--bg-elevated);color:var(--text-primary);padding:10px 14px;
            font-family:var(--font-display);font-size:11px;letter-spacing:1px;
            text-transform:uppercase;border-bottom:1px solid var(--border-mid);text-align:left;
        }
        .article-content td{padding:10px 14px;border-bottom:1px solid var(--border-dim);color:var(--text-secondary);}
        .article-content strong{color:var(--text-primary);}
        .article-content hr{border:none;height:1px;background:linear-gradient(90deg,transparent,var(--border-mid),transparent);margin:2em 0;}

        /* ── KOMENTAR ── */
        .comments-section { margin-top: 48px; }

        .comments-header {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 28px;
        }
        .comments-header h3 {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .comments-header .count {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--neon-red);
            letter-spacing: 1px;
        }

        .comment-item {
            background: var(--bg-card);
            border: 1px solid var(--border-dim);
            border-radius: 4px;
            padding: 20px 24px;
            margin-bottom: 16px;
            position: relative;
        }
        .comment-item::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--neon-red);
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .comment-item:hover::before { opacity: 1; }

        .comment-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .comment-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--neon-red);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-size: 12px;
            font-weight: 900;
            color: white;
            flex-shrink: 0;
            box-shadow: var(--glow-red);
        }
        .comment-name {
            font-family: var(--font-display);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-primary);
        }
        .comment-date {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 1px;
            margin-left: auto;
        }
        .comment-text {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .no-comments {
            text-align: center;
            padding: 40px 20px;
            background: var(--bg-card);
            border: 1px dashed var(--border-mid);
            border-radius: 4px;
            margin-bottom: 32px;
        }
        .no-comments p {
            font-family: var(--font-mono);
            font-size: 12px;
            letter-spacing: 2px;
            color: var(--text-muted);
        }

        .comment-form-box {
            background: var(--bg-card);
            border: 1px solid var(--border-dim);
            border-radius: 4px;
            padding: 28px;
            margin-top: 32px;
            position: relative;
            overflow: hidden;
        }
        .comment-form-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--neon-red), var(--neon-purple));
        }
        .comment-form-title {
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .comment-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media(max-width:600px){ .comment-form-row{ grid-template-columns:1fr; } }

        .char-counter {
            font-family: var(--font-mono);
            font-size: 10px;
            color: var(--text-muted);
            text-align: right;
            margin-top: 4px;
            letter-spacing: 1px;
        }
        .char-counter.warn { color: #ffaa00; }
        .char-counter.limit { color: #ff4444; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="index.php">Home</a></li>
        <li><a href="admin/login.php">Admin</a></li>
    </ul>
</nav>

<div class="container">
    <article class="fade-up">
        <div class="article-header">
            <?php if(!empty($row['cat_name'])): ?>
            <a class="post-tag" href="index.php?category=<?php echo urlencode($row['cat_slug']); ?>"
               style="text-decoration:none;"><?php echo htmlspecialchars($row['cat_name']); ?></a>
            <?php else: ?>
            <div class="post-tag">Uncategorized</div>
            <?php endif; ?>

            <h1 class="article-title"><?php echo htmlspecialchars($row['title']); ?></h1>
            <div class="article-meta">
                <span><?php echo date("d M Y", strtotime($row['created_at'])); ?></span>
                <span>GameZone Editorial</span>
                <span><?php echo $comment_count; ?> Comment<?php echo $comment_count !== 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <?php if(!empty($row['image']) && file_exists("uploads/".$row['image'])): ?>
        <img class="article-image"
             src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
             alt="<?php echo htmlspecialchars($row['title']); ?>">
        <?php endif; ?>

        <div class="article-content">
            <?php echo $row['content']; ?>
        </div>
    </article>

    <hr class="divider">

    <!-- comments section -->
    <div class="comments-section fade-up">

        <div class="comments-header">
            <h3>Comments</h3>
            <span class="count"><?php echo $comment_count; ?> approved</span>
        </div>

        <!-- list comments -->
        <?php if($comment_count > 0): ?>
            <?php while($cmt = mysqli_fetch_assoc($comments)): ?>
            <div class="comment-item">
                <div class="comment-meta">
                    <div class="comment-avatar">
                        <?php echo strtoupper(substr($cmt['name'], 0, 1)); ?>
                    </div>
                    <span class="comment-name"><?php echo htmlspecialchars($cmt['name']); ?></span>
                    <span class="comment-date">
                        <?php echo date("d M Y · H:i", strtotime($cmt['created_at'])); ?>
                    </span>
                </div>
                <p class="comment-text"><?php echo nl2br(htmlspecialchars($cmt['comment'])); ?></p>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="no-comments">
            <p>// NO COMMENTS YET — BE THE FIRST</p>
        </div>
        <?php endif; ?>

        <!-- form comments -->
        <div class="comment-form-box" id="comment-form">

            <?php if($comment_success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                ✓ Komentar dikirim! Menunggu persetujuan admin.
            </div>
            <?php endif; ?>

            <?php if($comment_error): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">
                <?php echo htmlspecialchars($comment_error); ?>
            </div>
            <?php endif; ?>

            <div class="comment-form-title">Leave a Comment</div>

            <form method="POST" action="post.php?slug=<?php echo urlencode($post_slug); ?>#comment-form">
                <?php csrf_field(); ?>
                <input type="hidden" name="submit_comment" value="1">

                <div class="comment-form-row">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input class="form-input" type="text" name="name"
                               placeholder="Your name"
                               value="<?php echo isset($_POST['name']) && !$comment_success ? htmlspecialchars($_POST['name']) : ''; ?>"
                               maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:var(--text-muted);font-size:9px;font-family:var(--font-mono);">(tidak ditampilkan)</span></label>
                        <input class="form-input" type="email" name="email"
                               placeholder="your@email.com"
                               value="<?php echo isset($_POST['email']) && !$comment_success ? htmlspecialchars($_POST['email']) : ''; ?>"
                               maxlength="150" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Comment</label>
                    <textarea class="form-textarea" name="comment"
                              placeholder="Write your comment..."
                              maxlength="2000"
                              style="min-height:110px;"
                              oninput="updateCounter(this)"
                              required><?php echo isset($_POST['comment']) && !$comment_success ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                    <div class="char-counter" id="charCounter">0 / 2000</div>
                </div>

                <button class="btn btn-primary" type="submit">Post Comment</button>
            </form>
        </div>

    </div>
    <!-- end comments -->

    <hr class="divider">
    <a class="back-link" href="index.php">Back to Home</a>
</div>

<footer class="footer">
    <div class="footer-brand"><span>Game</span>Zone</div>
    <div class="footer-meta">© <?php echo date('Y'); ?> — ALL RIGHTS RESERVED</div>
</footer>

<script>
function updateCounter(el){
    const len     = el.value.length;
    const max     = 2000;
    const counter = document.getElementById('charCounter');
    counter.textContent = len + ' / ' + max;
    counter.className   = 'char-counter' +
        (len > 1800 ? ' limit' : len > 1500 ? ' warn' : '');
}
</script>

</body>
</html>