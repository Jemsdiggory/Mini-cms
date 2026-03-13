<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Comments — GameZone Admin</title>
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

// approve
if(isset($_GET['approve'])){
    $cid  = intval($_GET['approve']);
    $stmt = mysqli_prepare($conn, "UPDATE comments SET status='approved' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $cid);
    mysqli_stmt_execute($stmt);
    $success = 'Komentar diapprove.';
}

// reject
if(isset($_GET['reject'])){
    $cid  = intval($_GET['reject']);
    $stmt = mysqli_prepare($conn, "UPDATE comments SET status='rejected' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $cid);
    mysqli_stmt_execute($stmt);
    $success = 'Komentar ditolak.';
}

// delete
if(isset($_GET['delete'])){
    $cid  = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $cid);
    mysqli_stmt_execute($stmt);
    $success = 'Komentar dihapus.';
}

// status filter
$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['pending','approved','rejected'])
          ? $_GET['filter'] : '';

// Stats
$stats = [];
foreach(['pending','approved','rejected'] as $s){
    $r          = mysqli_query($conn, "SELECT COUNT(*) as c FROM comments WHERE status='$s'");
    $stats[$s]  = mysqli_fetch_assoc($r)['c'];
}

// Query komentar dengan JOIN ke posts untuk tampilkan judul artikel
if($filter !== ''){
    $stmt = mysqli_prepare($conn,
        "SELECT cm.*, p.title as post_title, p.slug as post_slug
         FROM comments cm
         JOIN posts p ON cm.post_id = p.id
         WHERE cm.status = ?
         ORDER BY cm.created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, "s", $filter);
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT cm.*, p.title as post_title, p.slug as post_slug
         FROM comments cm
         JOIN posts p ON cm.post_id = p.id
         ORDER BY cm.created_at DESC"
    );
}
mysqli_stmt_execute($stmt);
$comments = mysqli_stmt_get_result($stmt);
$total    = mysqli_num_rows($comments);
?>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_post.php">Add Post</a></li>
        <li><a href="categories.php">Categories</a></li>
        <li><a href="comments.php" class="active">Comments</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="logout.php" class="nav-logout">Logout</a></li>
    </ul>
</nav>

<div class="container">

    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <div class="dashboard-header">
        <div class="page-header" style="margin-bottom:0;">
            <h2>Manage <span class="accent">Comments</span></h2>
            <p class="sub">// COMMENT MODERATION</p>
        </div>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success fade-up"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-bar fade-up">
        <div class="stat-card">
            <div class="stat-number" style="color:#ffaa00;text-shadow:0 0 8px #ffaa0088;">
                <?php echo $stats['pending']; ?>
            </div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color:#00ff64;text-shadow:0 0 8px #00ff6488;">
                <?php echo $stats['approved']; ?>
            </div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color:var(--text-muted);">
                <?php echo $stats['rejected']; ?>
            </div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="table-wrapper fade-up fade-up-1">
        <div class="table-header">
            <span class="table-title">All Comments</span>
            <div class="filter-bar">
                <a href="comments.php"
                   class="filter-btn <?php echo $filter===''?'active':''; ?>">All</a>
                <a href="comments.php?filter=pending"
                   class="filter-btn <?php echo $filter==='pending'?'active':''; ?>"
                   style="color:#ffaa00;border-color:rgba(255,170,0,0.3);">
                   Pending <?php if($stats['pending']>0): ?>
                   <span style="background:#ffaa00;color:#000;border-radius:100px;padding:1px 7px;font-size:10px;margin-left:4px;"><?php echo $stats['pending']; ?></span>
                   <?php endif; ?>
                </a>
                <a href="comments.php?filter=approved"
                   class="filter-btn <?php echo $filter==='approved'?'active':''; ?>"
                   style="color:#00ff64;border-color:rgba(0,255,100,0.3);">Approved</a>
                <a href="comments.php?filter=rejected"
                   class="filter-btn <?php echo $filter==='rejected'?'active':''; ?>"
                   style="color:var(--text-muted);">Rejected</a>
            </div>
        </div>

        <?php if($total > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>Article</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; while($cmt = mysqli_fetch_assoc($comments)): ?>
            <tr>
                <td><span class="post-num"><?php echo str_pad($no, 2, '0', STR_PAD_LEFT); ?></span></td>

                <td>
                    <div style="font-weight:600;color:var(--text-primary);font-size:13px;">
                        <?php echo htmlspecialchars($cmt['name']); ?>
                    </div>
                    <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-muted);margin-top:2px;">
                        <?php echo htmlspecialchars($cmt['email']); ?>
                    </div>
                </td>

                <td style="max-width:260px;">
                    <span style="font-size:13px;color:var(--text-secondary);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?php echo htmlspecialchars($cmt['comment']); ?>
                    </span>
                </td>

                <td style="max-width:160px;">
                    <a href="../post.php?slug=<?php echo urlencode($cmt['post_slug']); ?>"
                       target="_blank"
                       style="font-size:12px;color:var(--neon-red);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                       <?php echo htmlspecialchars($cmt['post_title']); ?>
                    </a>
                </td>

                <td>
                    <?php if($cmt['status'] === 'approved'): ?>
                    <span class="status-badge published">Approved</span>
                    <?php elseif($cmt['status'] === 'pending'): ?>
                    <span class="status-badge" style="color:#ffaa00;background:rgba(255,170,0,0.08);border-color:rgba(255,170,0,0.25);">Pending</span>
                    <?php else: ?>
                    <span class="status-badge" style="color:var(--text-muted);background:transparent;border-color:var(--border-dim);">Rejected</span>
                    <?php endif; ?>
                </td>

                <td>
                    <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);">
                        <?php echo date("d M Y", strtotime($cmt['created_at'])); ?>
                    </span>
                </td>

                <td>
                    <div class="td-actions" style="flex-wrap:wrap;gap:6px;">
                        <?php if($cmt['status'] !== 'approved'): ?>
                        <a class="btn btn-edit"
                           style="font-size:10px;padding:6px 12px;color:#00ff64;border-color:rgba(0,255,100,0.3);"
                           href="comments.php?approve=<?php echo $cmt['id']; ?><?php echo $filter?'&filter='.$filter:''; ?>"
                           onclick="return confirm('Approve komentar ini?')">
                           Approve
                        </a>
                        <?php endif; ?>
                        <?php if($cmt['status'] !== 'rejected'): ?>
                        <a class="btn btn-ghost"
                           style="font-size:10px;padding:6px 12px;"
                           href="comments.php?reject=<?php echo $cmt['id']; ?><?php echo $filter?'&filter='.$filter:''; ?>"
                           onclick="return confirm('Tolak komentar ini?')">
                           Reject
                        </a>
                        <?php endif; ?>
                        <a class="btn btn-danger"
                           href="comments.php?delete=<?php echo $cmt['id']; ?><?php echo $filter?'&filter='.$filter:''; ?>"
                           onclick="return confirm('Hapus permanen komentar ini?')">
                           Delete
                        </a>
                    </div>
                </td>
            </tr>
            <?php $no++; endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">◈</div>
            <p>TIDAK ADA KOMENTAR<?php echo $filter ? ' DENGAN STATUS INI' : ''; ?></p>
        </div>
        <?php endif; ?>
    </div>

</div>

<footer class="footer">
    <div class="footer-brand"><span>Game</span>Zone</div>
    <div class="footer-meta">ADMIN PANEL — © <?php echo date('Y'); ?></div>
</footer>

</body>
</html>