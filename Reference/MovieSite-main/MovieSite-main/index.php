<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
include("database/DBconn.php");
$con = getConnection();

// Pagination params
$tmdb_page    = isset($_GET['tmdb_page'])    ? max(1, intval($_GET['tmdb_page']))    : 1;
$creator_page = isset($_GET['creator_page']) ? max(1, intval($_GET['creator_page'])) : 1;
$max_tmdb_pages = 5;
$per_page = 4;

// Creator movies total count
$count_res = mysqli_query($con, "
    SELECT COUNT(*) AS total
    FROM dbProj_movies
    WHERE status = 'published'
      AND (director IS NULL OR director != 'TMDB')
");
$total_creator       = $count_res ? (int)mysqli_fetch_assoc($count_res)['total'] : 0;
$total_creator_pages = max(1, (int)ceil($total_creator / $per_page));
$creator_page        = min($creator_page, $total_creator_pages);
$creator_offset      = ($creator_page - 1) * $per_page;

// Fetch creator movies
$query_creator = "
    SELECT
        m.movie_id,
        m.title,
        m.description,
        m.director,
        m.release_year,
        (SELECT file_url FROM dbProj_media WHERE movie_id = m.movie_id LIMIT 1) AS poster_url,
        COALESCE(ROUND(AVG(r.stars), 1), 0) AS avg_rating
    FROM dbProj_movies m
    LEFT JOIN dbProj_ratings r ON m.movie_id = r.movie_id
    WHERE m.status = 'published'
      AND (m.director IS NULL OR m.director != 'TMDB')
    GROUP BY m.movie_id, m.title, m.description, m.director, m.release_year
    ORDER BY m.created_at DESC
    LIMIT $per_page OFFSET $creator_offset
";
$result_creator     = mysqli_query($con, $query_creator);
$has_creator_movies = $result_creator && mysqli_num_rows($result_creator) > 0;

// Fetch TMDB popular movies
$api_key    = "a80e29ac528ddd8cf4409afced5495e1";
$tmdb_page  = min($tmdb_page, $max_tmdb_pages);
$tmdb_url   = "https://api.themoviedb.org/3/movie/popular?api_key={$api_key}&language=en-US&page={$tmdb_page}";
$tmdb_data  = @file_get_contents($tmdb_url);
$tmdb_resp  = $tmdb_data ? json_decode($tmdb_data, true) : [];
$tmdb_movies = $tmdb_resp['results'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>The Binge Box</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="shared.css?v=<?php echo filemtime(__DIR__ . '/shared.css'); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
</head>
<body>

<?php $base_path = ""; include "includes/navbar.php"; ?>

<!-- Hero -->
<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Discover. Rate. Discuss.</h1>
        <p class="hero-subtitle">Your ultimate destination for movie reviews and recommendations.</p>
        <form class="hero-search" action="search/" method="GET">
            <div class="search-icon">&#128269;</div>
            <input type="text" name="init" placeholder="Search movies, directors, genres..." autocomplete="off" />
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<!-- Main -->
<main>

    <!-- Recently Added -->
    <?php if ($has_creator_movies): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Recently Added</h2>
            <a href="creator_movies.php" class="see-all">See All &rarr;</a>
        </div>
        <div class="movie-grid">
            <?php while ($movie = mysqli_fetch_assoc($result_creator)):
                $poster     = !empty($movie['poster_url']) ? $movie['poster_url'] : 'movies_images/no_image.jpg';
                $desc       = $movie['description'] ?: 'No description available.';
                $short_desc = mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc;
                $rating     = $movie['avg_rating'] > 0 ? number_format($movie['avg_rating'], 1) : null;
            ?>
            <a class="movie-card" href="movie/?custom_id=<?php echo $movie['movie_id']; ?>">
                <div class="card-img-wrap">
                    <img src="<?php echo htmlspecialchars($poster); ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>" />
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <p class="card-date">Release: <?php echo htmlspecialchars($movie['release_year']); ?></p>
                    <p class="card-desc"><?php echo htmlspecialchars($short_desc); ?></p>
                    <div class="card-footer">
                        <?php if ($rating): ?>
                        <span class="card-rating"><span class="star">&#9733;</span> <?php echo $rating; ?></span>
                        <?php else: ?>
                        <span></span>
                        <?php endif; ?>
                        <span class="card-view-more">View More &rarr;</span>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>

        <?php if ($total_creator_pages > 1): ?>
        <div class="pagination">
            <a class="page-btn<?php echo $creator_page <= 1 ? ' disabled' : ''; ?>"
               href="?tmdb_page=<?php echo $tmdb_page; ?>&creator_page=<?php echo $creator_page - 1; ?>">
               &larr; Prev
            </a>
            <div class="page-numbers">
                <?php for ($i = 1; $i <= $total_creator_pages; $i++): ?>
                <a href="?tmdb_page=<?php echo $tmdb_page; ?>&creator_page=<?php echo $i; ?>"
                   class="page-num<?php echo $i === $creator_page ? ' active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <a class="page-btn<?php echo $creator_page >= $total_creator_pages ? ' disabled' : ''; ?>"
               href="?tmdb_page=<?php echo $tmdb_page; ?>&creator_page=<?php echo $creator_page + 1; ?>">
               Next &rarr;
            </a>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Popular Right Now -->
    <?php if (!empty($tmdb_movies)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Popular Right Now</h2>
        </div>
        <div class="movie-grid">
            <?php foreach ($tmdb_movies as $movie):
                $overview = $movie['overview'] ?? '';
                $short_ov = mb_strlen($overview) > 100 ? mb_substr($overview, 0, 100) . '...' : $overview;
                $release  = !empty($movie['release_date']) ? $movie['release_date'] : 'Unknown';
                $score    = isset($movie['vote_average']) ? round($movie['vote_average'], 1) : null;
            ?>
            <a class="movie-card" href="movie/?id=<?php echo $movie['id']; ?>">
                <div class="card-img-wrap">
                    <img src="https://image.tmdb.org/t/p/w500<?php echo $movie['poster_path']; ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>" />
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <p class="card-date">Release: <?php echo htmlspecialchars($release); ?></p>
                    <p class="card-desc"><?php echo htmlspecialchars($short_ov); ?></p>
                    <div class="card-footer">
                        <?php if ($score): ?>
                        <span class="card-rating"><span class="star">&#9733;</span> <?php echo $score; ?></span>
                        <?php else: ?>
                        <span></span>
                        <?php endif; ?>
                        <span class="card-view-more">View More &rarr;</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <a class="page-btn<?php echo $tmdb_page <= 1 ? ' disabled' : ''; ?>"
               href="?tmdb_page=<?php echo $tmdb_page - 1; ?>&creator_page=<?php echo $creator_page; ?>">
               &larr; Prev
            </a>
            <div class="page-numbers">
                <?php for ($i = 1; $i <= $max_tmdb_pages; $i++): ?>
                <a href="?tmdb_page=<?php echo $i; ?>&creator_page=<?php echo $creator_page; ?>"
                   class="page-num<?php echo $i === $tmdb_page ? ' active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <a class="page-btn<?php echo $tmdb_page >= $max_tmdb_pages ? ' disabled' : ''; ?>"
               href="?tmdb_page=<?php echo $tmdb_page + 1; ?>&creator_page=<?php echo $creator_page; ?>">
               Next &rarr;
            </a>
        </div>
    </section>
    <?php endif; ?>

</main>

<footer>
    <p>&copy; 2026 The Binge Box. All rights reserved.</p>
</footer>


</body>
</html>
