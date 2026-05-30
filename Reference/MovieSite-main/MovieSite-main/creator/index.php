<?php

session_start();

include("../database/DBconn.php");

$con = getConnection();

/*
--------------------------------------------------
Check if logged in user is a creator
--------------------------------------------------
*/

if (
    !isset($_SESSION['role'])
    || $_SESSION['role'] !== 'creator'
) {

    header("Location: ../auth/login.php");

    exit();
}

/*
--------------------------------------------------
Retrieve creator account information
--------------------------------------------------
*/

$username = $_SESSION['username'];

$query = "
SELECT user_id,email
FROM dbProj_users
WHERE username = ?
";

$stmt = $con->prepare($query);

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$creator_id = $user['user_id'];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Creator Dashboard</title>

<link rel="stylesheet" href="../shared.css">
<link rel="stylesheet" href="creator.css">

</head>

<body>

<?php $base_path = "../"; include "../includes/navbar.php"; ?>

<!-- ==========================================
     Main Content
=========================================== -->

<main>

<h1>

Creator Dashboard

</h1>

<p class="welcome">

Welcome back,
<strong>

<?php echo $_SESSION['username']; ?>

</strong>

</p>

<!-- ==========================================
     Success Messages
=========================================== -->

<?php if (isset($_GET['success'])): ?>
<div class="success-message">
    <?php
    $msgs = [
        'movie_added'   => 'Movie added successfully!',
        'movie_updated' => 'Movie updated successfully!',
        'movie_deleted' => 'Movie deleted successfully!',
    ];
    echo $msgs[$_GET['success']] ?? 'Done!';
    ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="error-message">
    <?php
    $errors = [
        'missing_fields' => 'Please fill in all required fields.',
        'upload_failed'  => 'File upload failed. Please try again.',
        'invalid_type'   => 'Invalid file type. Only JPG, PNG, GIF and WebP images are allowed.',
        'file_too_large' => 'File is too large. Maximum size is 5 MB.',
        'save_failed'    => 'Could not save the uploaded file. Check server permissions.',
        'db_error'       => 'Database error. Please try again.',
        'user_not_found' => 'Session error. Please log in again.',
    ];
    echo $errors[$_GET['error']] ?? 'An unexpected error occurred.';
    ?>
</div>
<?php endif; ?>

<!-- ==========================================
     Add Movie Form
=========================================== -->

<section class="add-movie-box">

<h2>

Add New Movie

</h2>

<form
action="add_movie.php"
method="POST"
enctype="multipart/form-data"
id="addMovieForm"
onsubmit="return validateAddMovie()"
>

<input
type="text"
name="title"
placeholder="Movie Title"
required
>

<textarea
name="description"
placeholder="Movie Description"
required
></textarea>

<input
type="text"
name="director"
placeholder="Director Name"
required
>

<input
type="number"
name="release_year"
placeholder="Release Year"
required
>

<!-- ==========================================
     Genre Selection
=========================================== -->

<select
name="genre_id"
required
>

<option value="">

Select Genre

</option>

<?php

$genre_query = "
SELECT *
FROM dbProj_genres
ORDER BY name ASC
";

$genre_result = mysqli_query(
    $con,
    $genre_query
);

while($genre = mysqli_fetch_assoc($genre_result)) {

?>

<option value="<?php echo $genre['genre_id']; ?>">

<?php echo htmlspecialchars($genre['name']); ?>

</option>

<?php } ?>

</select>

<!-- ==========================================
     Movie Status
=========================================== -->

<select
name="status"
required
>

<option value="published">

Published

</option>

<option value="draft">

Draft

</option>

</select>

<input
type="url"
name="trailer_url"
placeholder="YouTube Trailer URL (e.g. https://www.youtube.com/watch?v=...)"
>

<small style="color:#888;">Optional — paste a YouTube link and it will be embedded on the movie page.</small>

<label style="font-weight:600; margin-top:8px; display:block;">
    Movie Poster
</label>

<input
type="file"
name="poster"
accept="image/jpeg,image/png,image/gif,image/webp"
>

<small style="color:#888;">JPG, PNG, GIF or WebP — max 5 MB. Leave empty to use a placeholder.</small>

<button type="submit">

Add Movie

</button>

</form>

</section>

<!-- ==========================================
     Creator Movie List
=========================================== -->

<section class="movies-section">

<h2>

My Movies

</h2>

<div class="movie-grid">

<?php

$query_movies = "
SELECT *
FROM dbProj_movies
WHERE created_by = ?
ORDER BY created_at DESC
";

$stmt_movies = $con->prepare($query_movies);

$stmt_movies->bind_param(
    "i",
    $creator_id
);

$stmt_movies->execute();

$result_movies = $stmt_movies->get_result();

while($movie = $result_movies->fetch_assoc()) {

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
        ? "../" . $media['file_url']
        : "../movies_images/no_image.jpg";

?>

<div class="movie-card">

<img
src="<?php echo $poster; ?>"
class="movie-image"
>

<div class="movie-info">

<h3>

<?php echo htmlspecialchars($movie['title']); ?>

</h3>

<p>

Director:
<?php echo htmlspecialchars($movie['director']); ?>

</p>

<p>

Year:
<?php echo $movie['release_year']; ?>

</p>

<p>

Status:
<?php echo ucfirst($movie['status']); ?>

</p>

<div class="movie-buttons">

<a
href="../movie/?custom_id=<?php echo $movie_id; ?>"
class="view-btn"
>

View

</a>

<a
href="edit_movie.php?id=<?php echo $movie_id; ?>"
class="edit-btn"
>

Edit

</a>

<a
href="delete_movie.php?id=<?php echo $movie_id; ?>"
class="delete-btn"
onclick="return confirm('Delete this movie?')"
>

Delete

</a>

</div>

</div>

</div>

<?php } ?>

</div>

</section>

</main>

<script>
function validateAddMovie() {
    var errors = [];

    var title       = document.querySelector('#addMovieForm [name="title"]').value.trim();
    var description = document.querySelector('#addMovieForm [name="description"]').value.trim();
    var director    = document.querySelector('#addMovieForm [name="director"]').value.trim();
    var year        = parseInt(document.querySelector('#addMovieForm [name="release_year"]').value, 10);
    var genre       = document.querySelector('#addMovieForm [name="genre_id"]').value;
    var poster      = document.querySelector('#addMovieForm [name="poster"]');

    if (title.length < 1)   errors.push('Title is required.');
    if (title.length > 200) errors.push('Title must be under 200 characters.');
    if (description.length < 10) errors.push('Description must be at least 10 characters.');
    if (director.length < 1) errors.push('Director name is required.');
    if (isNaN(year) || year < 1900 || year > 2030) errors.push('Release year must be between 1900 and 2030.');
    if (!genre || genre === '') errors.push('Please select a genre.');

    if (poster && poster.files.length > 0) {
        var file = poster.files[0];
        var allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) errors.push('Poster must be a JPG, PNG, GIF or WebP image.');
        if (file.size > 5 * 1024 * 1024) errors.push('Poster file must be under 5 MB.');
    }

    if (errors.length > 0) {
        alert('Please fix the following:\n\n' + errors.join('\n'));
        return false;
    }
    return true;
}
</script>

</body>

</html>