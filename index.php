<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameZone Blog</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <div class="brand-icon">GZ</div>
        <h1><span>Game</span>Zone</h1>
    </div>
    <ul class="navbar-nav">
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="admin/login.php">Admin</a></li>
    </ul>
</nav>

<div class="container">

    <div class="page-header fade-up">
        <h1 class="glitch">LATEST <span class="accent">DROPS</span></h1>
        <p class="sub">// GAMING NEWS & REVIEWS</p>
    </div>

    <?php include("config/database.php"); ?>

    <!--filt kategori -->
    <?php
    $all_cats    = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
    $active_cat  = isset($_GET['category']) ? trim($_GET['category']) : '';
    $is_search   = isset($_GET['search']) && trim($_GET['search']) !== '';
    $search_term = $is_search ? trim($_GET['search']) : '';
    ?>
    <div class="category-filter fade-up fade-up-1">
        <a href="index.php"
           class="cat-btn <?php echo ($active_cat === '' && !$is_search) ? 'active' : ''; ?>">
           All
        </a>
        <?php while($cat = mysqli_fetch_assoc($all_cats)): ?>
        <a href="index.php?category=<?php echo urlencode($cat['slug']); ?>"
           class="cat-btn <?php echo $active_cat === $cat['slug'] ? 'active' : ''; ?>">
           <?php echo htmlspecialchars($cat['name']); ?>
        </a>
        <?php endwhile; ?>
    </div>

    <!-- search -->
    <div class="search-box fade-up fade-up-1">
        <form method="GET" action="index.php">
            <?php if($active_cat): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($active_cat); ?>">
            <?php endif; ?>
            <input type="text" name="search"
                   placeholder="Search games, reviews..."
                   value="<?php echo $is_search ? htmlspecialchars($search_term) : ''; ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php
    $per_page    = 6;
    $current     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset      = ($current - 1) * $per_page;

    $where_parts  = ["p.status = 'published'"]; 
    $count_types  = '';
    $count_params = [];
    $query_types  = '';
    $query_params = [];

    if($is_search){
        $keyword        = '%' . $search_term . '%';
        $where_parts[]  = "(p.title LIKE ? OR p.content LIKE ?)";
        $count_types   .= 'ss';
        $count_params[] = $keyword;
        $count_params[] = $keyword;
        $query_types   .= 'ss';
        $query_params[] = $keyword;
        $query_params[] = $keyword;
    }

    if($active_cat !== ''){
        $where_parts[]  = "c.slug = ?";
        $count_types   .= 's';
        $count_params[] = $active_cat;
        $query_types   .= 's';
        $query_params[] = $active_cat;
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);

    // Hitung total
    $count_stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) as total FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id $where_sql"
    );
    if($count_types){
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    mysqli_stmt_execute($count_stmt);
    $total_posts = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
    $total_pages = max(1, ceil($total_posts / $per_page));

    if($current > $total_pages){ $current = $total_pages; $offset = ($current - 1) * $per_page; }

    $query_types   .= 'ii';
    $query_params[] = $per_page;
    $query_params[] = $offset;

    $main_stmt = mysqli_prepare($conn,
        "SELECT p.*, c.name as cat_name, c.slug as cat_slug
         FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id
         $where_sql ORDER BY p.id DESC LIMIT ? OFFSET ?"
    );
    mysqli_stmt_bind_param($main_stmt, $query_types, ...$query_params);
    mysqli_stmt_execute($main_stmt);
    $result = mysqli_stmt_get_result($main_stmt);

    if($is_search){
        echo '<p class="fade-up" style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);letter-spacing:2px;margin-bottom:28px;">'
           . '// ' . $total_posts . ' RESULT(S) FOR &quot;' . htmlspecialchars($search_term) . '&quot;</p>';
    }
    ?>

    <div class="posts-grid">
    <?php $no = 1; while($row = mysqli_fetch_assoc($result)): $delay = min($no, 5); ?>

    <div class="post-card fade-up fade-up-<?php echo $delay; ?>">
        <?php if(!empty($row['image']) && file_exists("uploads/".$row['image'])): ?>
        <img class="post-card-img"
             src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
             alt="<?php echo htmlspecialchars($row['title']); ?>">
        <?php else: ?>
        <div class="post-card-img-placeholder">[ NO IMAGE ]</div>
        <?php endif; ?>

        <div class="post-card-body">
            <?php if(!empty($row['cat_name'])): ?>
            <a class="post-tag" href="index.php?category=<?php echo urlencode($row['cat_slug']); ?>"
               style="text-decoration:none;"><?php echo htmlspecialchars($row['cat_name']); ?></a>
            <?php else: ?>
            <div class="post-tag">Uncategorized</div>
            <?php endif; ?>

            <div class="post-title">
                <a href="post.php?<?php echo !empty($row['slug']) ? 'slug='.urlencode($row['slug']) : 'id='.$row['id']; ?>">
                    <?php echo htmlspecialchars($row['title']); ?>
                </a>
            </div>
            <div class="post-date"><?php echo date("d M Y", strtotime($row['created_at'])); ?></div>
            <p class="post-excerpt">
                <?php echo htmlspecialchars(substr(strip_tags($row['content']), 0, 120)) . '...'; ?>
            </p>
            <a class="read-more" href="post.php?<?php echo !empty($row['slug']) ? 'slug='.urlencode($row['slug']) : 'id='.$row['id']; ?>">Read Article</a>
        </div>
    </div>

    <?php $no++; endwhile; ?>

    <?php if($no === 1): ?>
    <div class="empty-state" style="grid-column: 1/-1;">
        <div class="empty-state-icon">◈</div>
        <p>NO ARTICLES FOUND</p>
    </div>
    <?php endif; ?>
    </div>

    <?php if($total_pages > 1):
        $page_base = '?';
        if($active_cat) $page_base .= 'category=' . urlencode($active_cat) . '&';
        if($is_search)  $page_base .= 'search='   . urlencode($search_term) . '&';
        $page_base .= 'page=';
    ?>
    <div class="pagination">
        <?php if($current > 1): ?>
        <a class="page-btn" href="<?php echo $page_base.($current-1); ?>">← Prev</a>
        <?php endif; ?>

        <?php
        $range = 2; $start = max(1,$current-$range); $end = min($total_pages,$current+$range);
        if($start > 1): ?>
        <a class="page-btn" href="<?php echo $page_base.'1'; ?>">1</a>
        <?php if($start > 2): ?><span class="page-ellipsis">...</span><?php endif; ?>
        <?php endif; ?>

        <?php for($i=$start;$i<=$end;$i++): ?>
        <a class="page-btn <?php echo $i===$current?'active':''; ?>"
           href="<?php echo $page_base.$i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if($end < $total_pages):
            if($end < $total_pages-1): ?><span class="page-ellipsis">...</span><?php endif; ?>
        <a class="page-btn" href="<?php echo $page_base.$total_pages; ?>"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <?php if($current < $total_pages): ?>
        <a class="page-btn" href="<?php echo $page_base.($current+1); ?>">Next →</a>
        <?php endif; ?>

        <span class="page-info">
            Page <?php echo $current; ?> of <?php echo $total_pages; ?>
            &nbsp;·&nbsp; <?php echo $total_posts; ?> articles
        </span>
    </div>
    <?php endif; ?>

</div>

<footer class="footer">
    <div class="footer-brand"><span>Game</span>Zone</div>
    <div class="footer-meta">© <?php echo date('Y'); ?> — ALL RIGHTS RESERVED</div>
</footer>
</body>
</html>