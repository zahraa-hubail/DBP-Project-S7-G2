<?php
date_default_timezone_set('Europe/Bucharest');
/*
--------------------------------------------------
Enable error reporting for debugging
--------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include("../database/DBconn.php");

$con = getConnection();

/*
--------------------------------------------------
Create human readable review timestamps
--------------------------------------------------
*/

function time_elapsed_string($datetime)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);

    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . " year(s) ago";
    if ($diff->m > 0) return $diff->m . " month(s) ago";
    if ($diff->d > 0) return $diff->d . " day(s) ago";
    if ($diff->h > 0) return $diff->h . " hour(s) ago";
    if ($diff->i > 0) return $diff->i . " minute(s) ago";

    return "just now";
}

/*
--------------------------------------------------
Retrieve movie details from TMDB API
--------------------------------------------------
*/

if (isset($_GET['id'])) {

    $movie_id = intval($_GET['id']);

    $api_key = "a80e29ac528ddd8cf4409afced5495e1";

    $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$api_key&language=en-US";

    // API Protection rule: adding @ suppresses raw runtime network dumps if a 404 occurs
    $response = @file_get_contents($url);

    $result = json_decode($response, true);

    if (!$result || isset($result['success'])) {
        die("Movie not found.");
    }

    $is_custom_movie = false;

    /*
    --------------------------------------------------
    Automatically save TMDB movie into local database
    --------------------------------------------------
    */

    $check_query = "
    SELECT movie_id
    FROM dbProj_movies
    WHERE movie_id = ?
    ";

    $stmt_check = $con->prepare($check_query);

    $stmt_check->bind_param("i", $movie_id);

    $stmt_check->execute();

    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows == 0) {

        $insert_query = "
        INSERT INTO dbProj_movies
        (
            movie_id,
            created_by,
            title,
            description,
            director,
            release_year,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt_insert = $con->prepare($insert_query);

        $created_by = 1;

        $title = $result['title'];

        $description = $result['overview'];

        $director = "TMDB";

        $release_year = substr($result['release_date'], 0, 4);

        $status = "published";

        $stmt_insert->bind_param(
            "iisssis",
            $movie_id,
            $created_by,
            $title,
            $description,
            $director,
            $release_year,
            $status
        );

        $stmt_insert->execute();
    }
}

/*
--------------------------------------------------
Retrieve custom creator movie from database
--------------------------------------------------
*/

elseif (isset($_GET['custom_id'])) {

    $movie_id = intval($_GET['custom_id']);

    $query = "
    SELECT *
    FROM dbProj_movies
    WHERE movie_id = ?
    ";

    $stmt = $con->prepare($query);

    $stmt->bind_param("i", $movie_id);

    $stmt->execute();

    $dbMovie = $stmt->get_result()->fetch_assoc();

    if (!$dbMovie) {
        die("Movie not found.");
    }

    $result = [
        'title' => $dbMovie['title'],
        'overview' => $dbMovie['description'],
        'release_date' => $dbMovie['release_year'],
        'poster_path' => null,
        'vote_average' => "N/A"
    ];

    $is_custom_movie = true;
}

else {

    die("Movie not found.");
}

/*
--------------------------------------------------
Fallback values for missing movie information
--------------------------------------------------
*/

if (empty($result['overview'])) {
    $result['overview'] = "No description available.";
}

if (empty($result['release_date'])) {
    $result['release_date'] = "Unknown";
}

/*
--------------------------------------------------
Calculate average movie rating
--------------------------------------------------
*/

$query_rating = "
SELECT AVG(stars) AS average_rating
FROM dbProj_ratings
WHERE movie_id = ?
";

$stmt_rating = $con->prepare($query_rating);

$stmt_rating->bind_param("i", $movie_id);

$stmt_rating->execute();

$result_rating = $stmt_rating->get_result();

$row_rating = $result_rating->fetch_assoc();

$average_rating = $row_rating['average_rating'];

if ($average_rating !== null) {
    $average_rating = round($average_rating, 1);
} else {
    $average_rating = "No ratings";
}

/*
--------------------------------------------------
Count total movie reviews
--------------------------------------------------
*/

$query_reviews_count = "
SELECT COUNT(*) AS total_reviews
FROM dbProj_comments
WHERE movie_id = ?
";

$stmt_reviews_count = $con->prepare($query_reviews_count);

$stmt_reviews_count->bind_param("i", $movie_id);

$stmt_reviews_count->execute();

$result_reviews_count = $stmt_reviews_count->get_result();

$row_reviews_count = $result_reviews_count->fetch_assoc();

$total_reviews = $row_reviews_count['total_reviews'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($result['title']); ?></title>
    <link rel="stylesheet" href="../shared.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php $base_path = "../"; include "../includes/navbar.php"; ?>

<main>

<?php if (isset($_GET['success']) && $_GET['success'] == 'review_added') { ?>
<div class="success-message alert-container">
    Review added successfully!
</div>
<?php } ?>

<?php if (isset($_GET['status']) && $_GET['status'] == 'deleted') { ?>
<div class="success-message deletion-alert alert-container">
    Review deleted successfully!
</div>
<?php } ?>

    <div class="container">

<?php
/*
--------------------------------------------------
Display TMDB movie poster
--------------------------------------------------
*/
if (!$is_custom_movie && !empty($result['poster_path'])) {
?>
<img src="https://image.tmdb.org/t/p/w500<?php echo $result['poster_path']; ?>" class="movieimg">
<?php
}
/*
--------------------------------------------------
Display creator uploaded movie poster
--------------------------------------------------
*/
elseif ($is_custom_movie) {
    $media_query = "SELECT file_url FROM dbProj_media WHERE movie_id = ? AND media_type = 'image' LIMIT 1";
    $media_stmt = $con->prepare($media_query);
    $media_stmt->bind_param("i", $movie_id);
    $media_stmt->execute();
    $media_result = $media_stmt->get_result();
    $media = $media_result->fetch_assoc();
    $poster = $media ? "../" . $media['file_url'] : "../movies_images/no_image.jpg";
?>
<img src="<?php echo $poster; ?>" class="movieimg">
<?php
}
/*
--------------------------------------------------
Fallback image
--------------------------------------------------
*/
else {
?>
<img src="../movies_images/no_image.jpg" class="movieimg">
<?php } ?>

<div class="detalii">
    <h1><?php echo htmlspecialchars($result['title']); ?></h1>
    <div class="rating">
        <h2><?php echo $average_rating; ?> ⭐</h2>
        <h4><?php echo $total_reviews; ?> reviews</h4>
    </div>
    <br><br>
    <h2>Description</h2>
    <br>
    <h4><?php echo htmlspecialchars($result['overview']); ?></h4>
    <br><br>
    <h2>Release Date: <span style="font-weight:400"><?php echo htmlspecialchars($result['release_date']); ?></span></h2>

    <?php if (!$is_custom_movie) { ?>
    <br><br>
    <h2>Official TMDB Score: <span style="font-weight:400"><?php echo $result['vote_average']; ?></span></h2>
    <?php } ?>

    <?php
    /* Show YouTube trailer if one is stored for this movie */
    $trailer_q = $con->prepare("SELECT file_url FROM dbProj_media WHERE movie_id = ? AND media_type = 'video' LIMIT 1");
    $trailer_q->bind_param("i", $movie_id);
    $trailer_q->execute();
    $trailer_row = $trailer_q->get_result()->fetch_assoc();
    if ($trailer_row) {
        /* Extract YouTube video ID from the URL */
        $yt_url = $trailer_row['file_url'];
        $yt_id  = '';
        if (preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $yt_url, $m)) {
            $yt_id = $m[1];
        }
        if ($yt_id): ?>
    <br><br>
    <h2>Trailer</h2>
    <br>
    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:12px; max-width:1280px;">
        <iframe
            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($yt_id); ?>"
            style="position:absolute; top:0; left:0; width:100%; height:100%; border:none;"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>
        <?php endif;
    }
    ?>
</div>

</div>

<form id="reviewForm" action="../scripts_php/add_review.php" method="post" onsubmit="return checkUserSession()">
    <input type="hidden" name="movie_id"   value="<?php echo $movie_id; ?>">
    <input type="hidden" name="is_custom"  value="<?php echo $is_custom_movie ? '1' : '0'; ?>">
    <div class="review-input">
        <h3 class="name">
            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : "Guest"; ?>
        </h3>
        <textarea name="review_text" placeholder="Write your review" required></textarea>
    </div>

    <div class="rating_form">
        <label>Rating:</label>
        <div class="stars">
            <input type="radio" name="rating" value="5" id="star5" required>
            <label for="star5">★</label>
            <input type="radio" name="rating" value="4" id="star4">
            <label for="star4">★</label>
            <input type="radio" name="rating" value="3" id="star3">
            <label for="star3">★</label>
            <input type="radio" name="rating" value="2" id="star2">
            <label for="star2">★</label>
            <input type="radio" name="rating" value="1" id="star1">
            <label for="star1">★</label>
        </div>
    </div>

    <div class="submit-button">
        <input type="submit" value="Submit Review">
    </div>
</form>

<script>
function checkUserSession() {
    var isLoggedIn = <?php echo isset($_SESSION['id']) ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        alert("⚠️ Access Denied: You must log in to your account before posting a review.");
        window.location.href = "../account/index.php";
        return false;
    }
    return true;
}
</script>

<?php
/*
=========================================
GET REVIEWS
=========================================
*/
$query_reviews = "
SELECT
    c.comment_id,
    c.user_id,
    c.body,
    c.created_at,
    r.stars,
    u.username
FROM dbProj_comments c
LEFT JOIN dbProj_ratings r ON c.user_id = r.user_id AND c.movie_id = r.movie_id
LEFT JOIN dbProj_users u ON c.user_id = u.user_id
WHERE c.movie_id = ?
ORDER BY c.created_at DESC";

$stmt_reviews = $con->prepare($query_reviews);
$stmt_reviews->bind_param("i", $movie_id);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();

/*
=========================================
SHOW REVIEWS
=========================================
*/
if ($result_reviews->num_rows > 0) {
    echo "<div class='reviews'>";
    echo "<h2>Reviews</h2>";

    while ($row = $result_reviews->fetch_assoc()) {
        echo "<div class='review'>";
        echo "<div class='linia-doi'>";
        echo "<div>";
        echo "<h3 class='name'>" . htmlspecialchars($row['username']) . "</h3>";
        echo "<div>";
        echo "<span class='rating_mic'>" . str_repeat('★', intval($row['stars'])) . "</span>";
        echo "<span class='date'>" . time_elapsed_string($row['created_at']) . "</span>";
        echo "</div>";
        echo "</div>";

        /*
        =========================================
        DELETE BUTTON (Owner OR Admin Authorization Check)
        =========================================
        */
        if (isset($_SESSION['id']) && ($_SESSION['id'] == $row['user_id'] || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
            echo "<form action='../scripts_php/delete_review.php' method='POST' style='display:inline;'>";
            echo "<input type='hidden' name='review_id'  value='" . $row['comment_id'] . "'>";
            echo "<input type='hidden' name='movie_id'   value='" . $movie_id . "'>";
            echo "<input type='hidden' name='is_custom'  value='" . ($is_custom_movie ? '1' : '0') . "'>";
            echo "<button type='submit' class='delete-review-btn' onclick='return confirm(\"Are you sure you want to delete this review? This action cannot be undone.\")'>Delete</button>";
            echo "</form>";
        }

        echo "</div>";
        echo "<p class='content'>" . htmlspecialchars($row['body']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div class='reviews'>";
    echo "<h2>Reviews</h2>";
    echo "<p>No reviews yet.</p>";
    echo "</div>";
}
?>

</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Targets alert elements across the page template cleanly
    var $alertContainer = $('.deletion-alert, .success-message, .alert-container');
    
    if ($alertContainer.length > 0) {
        // Wait 4000 milliseconds (4 seconds), then smoothly slide up and fade out
        setTimeout(function() {
            $alertContainer.fadeTo(500, 0).slideUp(500, function(){
                $(this).remove(); 
            });
        }, 2000);
    }
});
</script>

</body>
</html>