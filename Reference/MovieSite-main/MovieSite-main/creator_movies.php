<?php
session_start();
$base_path = "";
include("database/DBconn.php");
$con = getConnection();

$per_page     = 10;
$page         = max(1, intval($_GET['page'] ?? 1));

/* Total count */
$count_res   = mysqli_query($con, "
    SELECT COUNT(*) AS total FROM dbProj_movies
    WHERE status = 'published' AND (director IS NULL OR director != 'TMDB')
");
$total        = (int) mysqli_fetch_assoc($count_res)['total'];
$total_pages  = max(1, (int) ceil($total / $per_page));
$page         = min($page, $total_pages);
$offset       = ($page - 1) * $per_page;

/* Fetch page of movies */
$stmt = $con->prepare("
    SELECT
        m.movie_id, m.title, m.description, m.director, m.release_year,
        u.username AS creator,
        COALESCE(ROUND(AVG(r.stars),1), 0) AS avg_rating,
        (SELECT file_url FROM dbProj_media WHERE movie_id = m.movie_id AND media_type = 'image' LIMIT 1) AS poster_url,
        GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ') AS genre_names
    FROM dbProj_movies m
    LEFT JOIN dbProj_users u         ON m.created_by  = u.user_id
    LEFT JOIN dbProj_ratings r       ON m.movie_id    = r.movie_id
    LEFT JOIN dbProj_movie_genres mg ON m.movie_id    = mg.movie_id
    LEFT JOIN dbProj_genres g        ON mg.genre_id   = g.genre_id
    WHERE m.status = 'published'
      AND (m.director IS NULL OR m.director != 'TMDB')
    GROUP BY m.movie_id, m.title, m.description, m.director, m.release_year, u.username
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$movies = $stmt->get_result();
$v = filemtime(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Movies — The Binge Box</title>
    <link rel="stylesheet" href="shared.css?v=<?= $v ?>">
    <link rel="stylesheet" href="style.css?v=<?= $v ?>">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<main>

    <div class="section-header" style="margin-bottom:32px;">
        <h2 class="section-title">All Creator Movies</h2>
        <?php if ($total > 0): ?>
        <span style="font-size:14px; color:#888;"><?= $total ?> movie<?= $total !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <?php if ($movies->num_rows > 0): ?>
    <div class="movie-grid">
        <?php while ($movie = $movies->fetch_assoc()):
            $poster    = !empty($movie['poster_url']) ? $movie['poster_url'] : 'movies_images/no_image.jpg';
            $desc      = $movie['description'] ?: 'No description available.';
            $short_desc = mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc;
            $rating    = $movie['avg_rating'] > 0 ? number_format($movie['avg_rating'], 1) : null;
        ?>
        <a class="movie-card" href="movie/?custom_id=<?= $movie['movie_id'] ?>">
            <div class="card-img-wrap">
                <img src="<?= htmlspecialchars($poster) ?>"
                     alt="<?= htmlspecialchars($movie['title']) ?>" />
            </div>
            <div class="card-body">
                <h3 class="card-title"><?= htmlspecialchars($movie['title']) ?></h3>
                <p class="card-date">
                    Release: <?= htmlspecialchars($movie['release_year']) ?>
                    <?php if (!empty($movie['genre_names'])): ?>
                    &bull; <?= htmlspecialchars($movie['genre_names']) ?>
                    <?php endif; ?>
                </p>
                <p class="card-desc"><?= htmlspecialchars($short_desc) ?></p>
                <div class="card-footer">
                    <?php if ($rating): ?>
                    <span class="card-rating"><span class="star">&#9733;</span> <?= $rating ?></span>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>
                    <span class="view-more-btn">View More &rarr;</span>
                </div>
            </div>
        </a>
        <?php endwhile; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <a class="page-btn<?= $page <= 1 ? ' disabled' : '' ?>"
           href="?page=<?= $page - 1 ?>">&larr; Prev</a>

        <div class="page-numbers">
            <?php
            $start_p = max(1, $page - 2);
            $end_p   = min($total_pages, $page + 2);
            if ($start_p > 1): ?><a href="?page=1" class="page-num">1</a><?php
                if ($start_p > 2) echo '<span class="page-ellipsis">&hellip;</span>';
            endif;
            for ($i = $start_p; $i <= $end_p; $i++): ?>
                <a href="?page=<?= $i ?>" class="page-num<?= $i === $page ? ' active' : '' ?>"><?= $i ?></a>
            <?php endfor;
            if ($end_p < $total_pages):
                if ($end_p < $total_pages - 1) echo '<span class="page-ellipsis">&hellip;</span>';
                ?><a href="?page=<?= $total_pages ?>" class="page-num"><?= $total_pages ?></a><?php
            endif; ?>
        </div>

        <a class="page-btn<?= $page >= $total_pages ? ' disabled' : '' ?>"
           href="?page=<?= $page + 1 ?>">Next &rarr;</a>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center; padding:80px 20px; color:#888;">
        <p style="font-size:1.2rem; margin-bottom:12px;">No creator movies added yet.</p>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'creator'): ?>
        <a href="creator/" style="color:rgb(53,87,121); font-weight:600;">Add the first movie &rarr;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<footer>
    <p>&copy; 2026 The Binge Box. All rights reserved.</p>
</footer>

</body>
</html>
