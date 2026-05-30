<?php
include("../database/DBconn.php");
$con = getConnection();

$searchTerm = trim($_GET['search']     ?? "");
$creator    = trim($_GET['creator']    ?? "");
$year       = trim($_GET['year']       ?? "");
$sort       = trim($_GET['sort']       ?? "");
$min_rating = floatval($_GET['min_rating'] ?? 0);
$page       = max(1, intval($_GET['page']  ?? 1));
$per_page   = 12;

/* ============================================================
   TMDB API SEARCH  (only when a search term is provided)
   ============================================================ */
if (!empty($searchTerm)) {
    $api_key  = "a80e29ac528ddd8cf4409afced5495e1";
    $url      = "https://api.themoviedb.org/3/search/movie?api_key={$api_key}&query=" . urlencode($searchTerm);
    $response = @file_get_contents($url);
    $data     = $response ? json_decode($response, true) : null;

    if ($data && !empty($data['results'])) {
        $tmdb_results = array_filter($data['results'], function ($m) use ($year) {
            if (empty($year)) return true;
            return !empty($m['release_date']) && substr($m['release_date'], 0, 4) == $year;
        });

        if (!empty($tmdb_results)) {
            echo '<h2 class="results-title">TMDB Results</h2>';
            echo '<div class="movie-list">';
            foreach ($tmdb_results as $movie) {
                $poster   = !empty($movie['poster_path'])
                    ? "https://image.tmdb.org/t/p/w500" . $movie['poster_path']
                    : "../movies_images/no_image.jpg";
                $release  = htmlspecialchars($movie['release_date'] ?? 'Unknown');
                $score    = isset($movie['vote_average']) ? round($movie['vote_average'], 1) : null;
                $title    = htmlspecialchars($movie['title']);
                $overview = htmlspecialchars(
                    mb_strlen($movie['overview'] ?? '') > 100
                        ? mb_substr($movie['overview'], 0, 100) . '...'
                        : ($movie['overview'] ?? '')
                );
                ?>
                <a class="movie-card" href="../movie/?id=<?= $movie['id'] ?>">
                    <img class="movie-image" src="<?= $poster ?>" alt="<?= $title ?>">
                    <div class="movie-info">
                        <h3><?= $title ?></h3>
                        <p class="movie-date">Release: <?= $release ?></p>
                        <p class="movie-desc"><?= $overview ?></p>
                        <?php if ($score): ?>
                        <p class="movie-rating"><span class="star">&#9733;</span> <?= $score ?></p>
                        <?php endif; ?>
                        <span class="view-more">View More &rarr;</span>
                    </div>
                </a>
                <?php
            }
            echo '</div>';
        }
    }
}

/* ============================================================
   DATABASE SEARCH  (creator-uploaded movies)
   ============================================================ */
$like = "%" . $searchTerm . "%";

/* Full-text search via MATCH AGAINST when possible, LIKE as fallback */
if (!empty($searchTerm)) {
    $text_cond = "MATCH(m.title, m.description, m.director) AGAINST (? IN BOOLEAN MODE)";
    $text_val  = $searchTerm . '*';   /* prefix wildcard for partial words */
} else {
    $text_cond = "(m.title LIKE ? OR m.director LIKE ? OR m.description LIKE ?)";
    $text_val  = null;
}

/* --- Shared WHERE conditions --- */
$where  = "WHERE m.status = 'published'
             AND (m.director IS NULL OR m.director != 'TMDB')
             AND $text_cond";

if (!empty($searchTerm)) {
    $params = [$text_val];
    $types  = "s";
} else {
    $params = [$like, $like, $like];
    $types  = "sss";
}

if (!empty($year)) {
    $where   .= " AND m.release_year = ?";
    $params[] = (int)$year;
    $types   .= "i";
}

if (!empty($creator)) {
    $where   .= " AND u.username LIKE ?";
    $params[] = "%" . $creator . "%";
    $types   .= "s";
}

/* --- Separate COUNT query (reliable; no subquery wrapping) --- */
$count_sql  = "SELECT COUNT(DISTINCT m.movie_id) AS total
               FROM dbProj_movies m
               LEFT JOIN dbProj_users u ON m.created_by = u.user_id
               $where";
$count_stmt = $con->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = (int) $count_stmt->get_result()->fetch_assoc()['total'];

/* --- Main SELECT with aggregates + HAVING for min-rating --- */
$main_sql = "
    SELECT
        m.movie_id,
        m.title,
        m.description,
        m.director,
        m.release_year,
        g.name                  AS genre_name,
        u.username              AS creator_username,
        ROUND(AVG(r.stars), 1) AS avg_rating,
        (SELECT file_url FROM dbProj_media
         WHERE movie_id = m.movie_id AND media_type = 'image'
         LIMIT 1)               AS poster_url
    FROM dbProj_movies m
    LEFT JOIN dbProj_ratings r       ON m.movie_id   = r.movie_id
    LEFT JOIN dbProj_movie_genres mg ON m.movie_id   = mg.movie_id
    LEFT JOIN dbProj_genres g        ON mg.genre_id  = g.genre_id
    LEFT JOIN dbProj_users u         ON m.created_by = u.user_id
    $where
    GROUP BY m.movie_id, m.title, m.description, m.director, m.release_year,
             g.name, u.username, poster_url
";

/* HAVING for minimum rating filter */
if ($min_rating > 0) {
    $main_sql .= " HAVING avg_rating >= ?";
    $params[]  = $min_rating;
    $types    .= "d";
    /* Recount with rating filter applied */
    $recount = $con->prepare("SELECT COUNT(*) AS total FROM ($main_sql) AS sub");
    $recount->bind_param($types, ...$params);
    $recount->execute();
    $total_rows = (int) $recount->get_result()->fetch_assoc()['total'];
}

$total_pages = max(1, (int)ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

/* ORDER BY */
switch ($sort) {
    case 'rating_desc': $main_sql .= " ORDER BY avg_rating DESC";    break;
    case 'rating_asc':  $main_sql .= " ORDER BY avg_rating ASC";     break;
    case 'latest':      $main_sql .= " ORDER BY m.release_year DESC"; break;
    case 'oldest':      $main_sql .= " ORDER BY m.release_year ASC";  break;
    default:            $main_sql .= " ORDER BY m.created_at DESC";
}

$main_sql .= " LIMIT $per_page OFFSET $offset";

$stmt = $con->prepare($main_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<h2 class="results-title">Creator Uploads</h2>';
    echo '<div class="movie-list">';

    while ($movie = $result->fetch_assoc()) {
        $poster = !empty($movie['poster_url'])
            ? '../' . $movie['poster_url']
            : '../movies_images/no_image.jpg';
        $title  = htmlspecialchars($movie['title']);
        $desc   = $movie['description'] ?? '';
        $short  = htmlspecialchars(mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc);
        $rating = $movie['avg_rating'] ? number_format($movie['avg_rating'], 1) : null;
        ?>
        <a class="movie-card" href="../movie/?custom_id=<?= $movie['movie_id'] ?>">
            <img class="movie-image" src="<?= $poster ?>" alt="<?= $title ?>">
            <div class="movie-info">
                <h3><?= $title ?></h3>
                <p class="movie-date">Release: <?= htmlspecialchars($movie['release_year']) ?></p>
                <?php if (!empty($movie['genre_name'])): ?>
                <p class="movie-genre">Genre: <?= htmlspecialchars($movie['genre_name']) ?></p>
                <?php endif; ?>
                <p class="movie-desc"><?= $short ?></p>
                <?php if ($rating): ?>
                <p class="movie-rating"><span class="star">&#9733;</span> <?= $rating ?></p>
                <?php endif; ?>
                <div class="card-bottom">
                    <span class="creator-badge">By <?= htmlspecialchars($movie['creator_username'] ?? 'Creator') ?></span>
                    <span class="view-more">View More &rarr;</span>
                </div>
            </div>
        </a>
        <?php
    }
    echo '</div>';
} elseif (empty($searchTerm) && empty($creator) && empty($year) && $min_rating == 0) {
    echo '<p class="no-results">No creator movies have been added yet.</p>';
} else {
    echo '<p class="no-results">No creator movies match your filters.</p>';
}

/* Pagination */
if ($total_pages > 1) {
    echo '<div class="pagination" id="db-pagination">';
    echo $page > 1
        ? '<a class="page-btn" data-page="' . ($page-1) . '" href="#">&larr; Prev</a>'
        : '<span class="page-btn disabled">&larr; Prev</span>';
    echo '<div class="page-numbers">';
    $s = max(1, $page-2); $e = min($total_pages, $page+2);
    for ($i = $s; $i <= $e; $i++) {
        $cls = $i === $page ? 'page-num active' : 'page-num';
        echo "<a class=\"$cls\" data-page=\"$i\" href=\"#\">$i</a>";
    }
    echo '</div>';
    echo $page < $total_pages
        ? '<a class="page-btn" data-page="' . ($page+1) . '" href="#">Next &rarr;</a>'
        : '<span class="page-btn disabled">Next &rarr;</span>';
    echo '</div>';
}

$stmt->close();
$con->close();
?>
