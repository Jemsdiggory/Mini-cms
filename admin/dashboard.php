<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — GameZone Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .author-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-mid);
            border-radius: 100px;
            padding: 3px 10px 3px 4px;
        }
        .author-badge .avatar {
            width: 20px; height: 20px;
            border-radius: 50%;
            background: var(--neon-red);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-size: 9px;
            font-weight: 900;
            color: white;
            flex-shrink: 0;
        }
        .author-badge .avatar.is-me {
            background: var(--neon-cyan);
            box-shadow: var(--glow-cyan);
        }
        .author-badge .avatar.unknown {
            background: var(--text-muted);
            box-shadow: none;
        }
        .author-badge span {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-secondary);
            letter-spacing: 1px;
        }
        .author-badge .me-label {
            color: var(--neon-cyan);
        }
        .filter-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-btn {
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 1px;
            padding: 6px 14px;
            border-radius: 2px;
            border: 1px solid var(--border-mid);
            background: var(--bg-elevated);
            color: var(--text-muted);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .filter-btn:hover, .filter-btn.active {
            border-color: var(--neon-red);
            color: var(--neon-red);
            background: rgba(255,0,68,0.08);
        }
    </style>
</head>
<body>

<?php
session_start();
include("../config/database.php");

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

$me_id = intval($_SESSION['admin_id']);

// Filter by author
$filter_author = isset($_GET['author']) ? intval($_GET['author']) : 0;

// Stats
$total_result = mysqli_query($conn,"SELECT COUNT(*) as total FROM posts");
$total_row    = mysqli_fetch_assoc($total_result);
$total_posts  = $total_row['total'];

// Stats artikel mine
$my_result = mysqli_query($conn,"SELECT COUNT(*) as c FROM posts WHERE author_id=$me_id");
$my_row    = mysqli_fetch_assoc($my_result);
$my_posts  = $my_row['c'];

// Ambil semua user untuk filter dropdown
$all_users = mysqli_query($conn,"SELECT id, username FROM users ORDER BY username ASC");
?>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="../index.php">View Blog</a></li>
        <li><a href="add_post.php">Add Post</a></li>
        <li><a href="categories.php">Categories</a></li>
        <li><a href="comments.php">Comments</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="logout.php" class="nav-logout">Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="dashboard-header">
        <div class="page-header" style="margin-bottom:0;">
            <h2>Dashboard <span class="accent">Admin</span></h2>
            <p class="sub">// CONTENT MANAGEMENT SYSTEM</p>
        </div>
        <a href="add_post.php" class="btn btn-primary fade-up">+ New Post</a>
    </div>

    <div class="welcome-badge fade-up">
        <div class="dot"><?php echo strtoupper(substr($_SESSION['admin'],0,1)); ?></div>
        <div class="text">Logged in as <span class="name"><?php echo htmlspecialchars($_SESSION['admin']); ?></span></div>
    </div>

    <!-- stats -->
    <div class="stats-bar fade-up fade-up-1">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_posts; ?></div>
            <div class="stat-label">Total Articles</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color:var(--neon-cyan);text-shadow:var(--glow-cyan);">
                <?php echo $my_posts; ?>
            </div>
            <div class="stat-label">My Articles</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color:var(--neon-purple);text-shadow:0 0 8px #9d00ff88;">
                <?php
                $r2 = mysqli_query($conn,"SELECT COUNT(*) as c FROM posts WHERE DATE(created_at) = CURDATE()");
                $d2 = mysqli_fetch_assoc($r2);
                echo $d2['c'];
                ?>
            </div>
            <div class="stat-label">Posted Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color:#ffaa00;text-shadow:0 0 8px #ffaa0088;">
                <?php
                $r3 = mysqli_query($conn,"SELECT COUNT(DISTINCT author_id) as c FROM posts WHERE author_id IS NOT NULL");
                $d3 = mysqli_fetch_assoc($r3);
                echo $d3['c'];
                ?>
            </div>
            <div class="stat-label">Active Authors</div>
        </div>
    </div>

    <!-- table -->
    <div class="table-wrapper fade-up fade-up-2">
        <div class="table-header">
            <span class="table-title">All Articles</span>

            <!-- Filter by author -->
            <div class="filter-bar">
                <a href="dashboard.php"
                   class="filter-btn <?php echo $filter_author === 0 ? 'active' : ''; ?>">
                   All
                </a>
                <a href="dashboard.php?author=<?php echo $me_id; ?>"
                   class="filter-btn <?php echo $filter_author === $me_id ? 'active' : ''; ?>">
                   Mine
                </a>
                <?php
                // Tampilkan filter per user lain
                mysqli_data_seek($all_users, 0);
                while($u = mysqli_fetch_assoc($all_users)):
                    if($u['id'] == $me_id) continue; 
                    $uid = intval($u['id']);
                ?>
                <a href="dashboard.php?author=<?php echo $uid; ?>"
                   class="filter-btn <?php echo $filter_author === $uid ? 'active' : ''; ?>">
                   <?php echo htmlspecialchars($u['username']); ?>
                </a>
                <?php endwhile; ?>
            </div>

            <!-- Filter status -->
            <div class="filter-bar" style="margin-top:8px;">
                <?php $filter_status = isset($_GET['status']) ? $_GET['status'] : ''; ?>
                <a href="<?php echo 'dashboard.php' . ($filter_author ? '?author='.$filter_author : ''); ?>"
                   class="filter-btn <?php echo $filter_status===''?'active':''; ?>">All Status</a>
                <a href="<?php echo 'dashboard.php?status=published' . ($filter_author ? '&author='.$filter_author : ''); ?>"
                   class="filter-btn <?php echo $filter_status==='published'?'active':''; ?>" style="color:#00ff64;border-color:rgba(0,255,100,0.3);">● Published</a>
                <a href="<?php echo 'dashboard.php?status=draft' . ($filter_author ? '&author='.$filter_author : ''); ?>"
                   class="filter-btn <?php echo $filter_status==='draft'?'active':''; ?>" style="color:#ffaa00;border-color:rgba(255,170,0,0.3);">● Draft</a>
            </div>
        </div>

        <?php
        $filter_status = isset($_GET['status']) && in_array($_GET['status'],['draft','published']) ? $_GET['status'] : '';

        // Build WHERE clause
        $w = [];
        if($filter_author > 0)  $w[] = "p.author_id = $filter_author";
        if($filter_status !== '') $w[] = "p.status = '$filter_status'";
        $where = count($w) ? 'WHERE ' . implode(' AND ', $w) : '';

        $query    = mysqli_query($conn,
            "SELECT p.*, u.username as author_name
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             $where
             ORDER BY p.id DESC"
        );
        $has_rows = mysqli_num_rows($query) > 0;
        $shown    = mysqli_num_rows($query);
        ?>

        <?php if($has_rows): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; while($row = mysqli_fetch_assoc($query)):
                $is_mine   = ($row['author_id'] == $me_id);
                $has_author = !empty($row['author_name']);
                $initial    = $has_author ? strtoupper(substr($row['author_name'], 0, 1)) : '?';
            ?>
            <tr>
                <td><span class="post-num"><?php echo str_pad($no, 2, '0', STR_PAD_LEFT); ?></span></td>

                <td class="td-title"><?php echo htmlspecialchars($row['title']); ?></td>

                <td>
                    <div class="author-badge">
                        <div class="avatar <?php echo $is_mine ? 'is-me' : ($has_author ? '' : 'unknown'); ?>">
                            <?php echo $initial; ?>
                        </div>
                        <?php if($has_author): ?>
                            <span <?php echo $is_mine ? 'class="me-label"' : ''; ?>>
                                <?php echo htmlspecialchars($row['author_name']); ?>
                                <?php echo $is_mine ? ' (you)' : ''; ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">Unknown</span>
                        <?php endif; ?>
                    </div>
                </td>

                <td>
                    <?php if($row['status'] === 'published'): ?>
                    <span class="status-badge published">Published</span>
                    <?php else: ?>
                    <span class="status-badge draft">Draft</span>
                    <?php endif; ?>
                </td>

                <td>
                    <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);">
                        <?php echo date("d M Y", strtotime($row['created_at'])); ?>
                    </span>
                </td>

                <td>
                    <div class="td-actions">
                        <a class="btn btn-edit" href="edit_post.php?id=<?php echo $row['id']; ?>">Edit</a>
                        <a class="btn btn-danger" href="delete_post.php?id=<?php echo $row['id']; ?>"
                           onclick="return confirm('Delete this post?')">Delete</a>
                        <a class="btn btn-ghost" style="font-size:10px;padding:7px 14px;"
                           href="../post.php?id=<?php echo $row['id']; ?>" target="_blank">View</a>
                    </div>
                </td>
            </tr>
            <?php $no++; endwhile; ?>
            </tbody>
        </table>

        <?php if($filter_author > 0): ?>
        <div style="padding:14px 24px;font-family:var(--font-mono);font-size:11px;color:var(--text-muted);letter-spacing:1px;border-top:1px solid var(--border-dim);">
            SHOWING <?php echo $shown; ?> ARTICLE(S) —
            <a href="dashboard.php" style="color:var(--neon-red);">CLEAR FILTER</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">◈</div>
            <?php if($filter_author > 0): ?>
            <p>NO ARTICLES FROM THIS AUTHOR — <a href="dashboard.php" style="color:var(--neon-red);">CLEAR FILTER</a></p>
            <?php else: ?>
            <p>NO ARTICLES YET — <a href="add_post.php" style="color:var(--neon-red);">ADD ONE NOW</a></p>
            <?php endif; ?>
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