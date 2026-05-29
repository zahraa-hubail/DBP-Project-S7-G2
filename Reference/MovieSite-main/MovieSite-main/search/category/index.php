<?php

/*
========================================
DATABASE CONNECTION
========================================
*/

include("../../database/DBconn.php");

$con = getConnection();

/*
========================================
TMDB API CONFIGURATION
========================================
*/

$api_key = 'a80e29ac528ddd8cf4409afced5495e1';

$genre_endpoint = 'https://api.themoviedb.org/3/genre/movie/list';

$discover_endpoint = 'https://api.themoviedb.org/3/discover/movie';

/*
========================================
FETCH MOVIE GENRES
========================================
*/

$genres_json = file_get_contents(
    "$genre_endpoint?api_key=$api_key"
);

$genres_data = json_decode(
    $genres_json,
    true
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Browse Categories</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<!-- ==========================================
     Navigation Header
=========================================== -->

<header>

<div class="logo">

<a href="../../">

<img
src="../../logo.png"
alt="Movies"
/>

</a>

</div>

<nav>

<ul>

<li class="dropdown">

<a href="../">

Search

</a>

<div class="dropdown-content">

<a href="./">

Browse Categories

</a>

</div>

</li>

<li>

<a href="../../account/">

Account

</a>

</li>

<li class="dropdown">

<a href="../../about/">

About

</a>

<div class="dropdown-content">

<a href="../../about/">

About Us

</a>

<a href="../../about/movies.html">

About Movies

</a>

</div>

</li>

</ul>

</nav>

</header>

<!-- ==========================================
     Main Content
=========================================== -->

<main>

<h1 class="page-title">

Browse Movie Categories

</h1>

<!-- ==========================================
     Genre Selection Form
=========================================== -->

<form class="genre-form"
method="GET">

<select
name="genre"
required
>

<option value="">

Select Genre

</option>

<?php

foreach ($genres_data['genres'] as $genre) {

?>

<option value="<?php echo $genre['id']; ?>">

<?php echo htmlspecialchars($genre['name']); ?>

</option>

<?php } ?>

</select>

<button type="submit">

Browse

</button>

</form>

<?php

/*
========================================
FETCH MOVIES BY GENRE
========================================
*/

if (isset($_GET['genre'])) {

$selected_genre = $_GET['genre'];

/*
------------------------------------------
Build TMDB discover URL
------------------------------------------
*/

$discover_url =
"$discover_endpoint?api_key=$api_key&with_genres=$selected_genre&sort_by=popularity.desc";

/*
------------------------------------------
Fetch movies from TMDB
------------------------------------------
*/

$discover_json = file_get_contents(
$discover_url
);

$discover_data = json_decode(
$discover_json,
true
);

/*
------------------------------------------
Retrieve selected genre name
------------------------------------------
*/

$genre_name =
array_column(
$genres_data['genres'],
'name',
'id'
)[$selected_genre];

?>

<!-- ==========================================
     TMDB MOVIES
=========================================== -->

<h2 class="results-title">

Top
<?php echo htmlspecialchars($genre_name); ?>
Movies

</h2>

<div class="movie-list">

<?php

foreach ($discover_data['results'] as $movie) {

?>

<a
class="movie-card"
href="../../movie/?id=<?php echo $movie['id']; ?>"
>

<?php

if ($movie['poster_path']) {

?>

<img
class="movie-image"
src="https://image.tmdb.org/t/p/w500<?php echo $movie['poster_path']; ?>"
>

<?php

} else {

?>

<img
class="movie-image"
src="../../movies_images/no_image.jpg"
>

<?php } ?>

<div class="movie-info">

<h3>

<?php
echo htmlspecialchars(
$movie['title']
);
?>

</h3>

<p>

Release:
<?php echo $movie['release_date']; ?>

</p>

<p>

⭐
<?php
echo round(
$movie['vote_average'],
1
);
?>

</p>

</div>

</a>

<?php } ?>

</div>

<!-- ==========================================
     CREATOR UPLOADED MOVIES
=========================================== -->

<h2 class="results-title">

Creator Uploaded <?php echo $genre_name; ?> Movies

</h2>

<div class="movie-list">

<?php

/*
----------------------------------------------
Retrieve creator uploaded movies
----------------------------------------------
*/

$db_query = "
SELECT m.*
FROM dbProj_movies m

JOIN dbProj_movie_genres mg
ON m.movie_id = mg.movie_id

WHERE mg.genre_id = ?
";

$db_stmt = $con->prepare($db_query);

$db_stmt->bind_param(
"i",
$selected_genre
);

$db_stmt->execute();

$db_result = $db_stmt->get_result();

while($movie = $db_result->fetch_assoc()) {

$movie_id = $movie['movie_id'];

/*
----------------------------------------------
Retrieve movie poster
----------------------------------------------
*/

$media_query = "
SELECT file_url
FROM dbProj_media
WHERE movie_id = ?
LIMIT 1
";

$media_stmt = $con->prepare($media_query);

$media_stmt->bind_param(
"i",
$movie_id
);

$media_stmt->execute();

$media_result = $media_stmt->get_result();

$media = $media_result->fetch_assoc();

$poster = $media
? "../../" . $media['file_url']
: "../../movies_images/no_image.jpg";

?>

<a
class="movie-card"
href="../../movie/?custom_id=<?php echo $movie_id; ?>"
>

<img
class="movie-image"
src="<?php echo $poster; ?>"
>

<div class="movie-info">

<h3>

<?php echo htmlspecialchars($movie['title']); ?>

</h3>

<p>

Release:
<?php echo $movie['release_year']; ?>

</p>

<p class="creator-badge">

Creator Upload

</p>

</div>

</a>

<?php } ?>

</div>

<?php } ?>

</main>

<!-- ==========================================
     Footer
=========================================== -->

<footer>

<p>

&copy; 2026 MovieSite.
All rights reserved.

</p>

</footer>

</body>

</html>