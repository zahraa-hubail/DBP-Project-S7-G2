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

<link rel="stylesheet" href="creator.css">

</head>

<body>

<!-- ==========================================
     Navigation Header
=========================================== -->

<header>

<div class="logo">

<a href="../">

<img
src="../logo.png"
alt="Movies"
>

</a>

</div>

<nav>

<ul>

<li>

<a href="../search/">

Search

</a>

</li>

<li>

<a href="./">

Creator Dashboard

</a>

</li>

<li>

<a href="../auth/logout.php">

Logout

</a>

</li>

</ul>

</nav>

</header>

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

<?php if(isset($_GET['success'])) { ?>

<div class="success-message">

<?php

if($_GET['success'] == "movie_added") {

    echo "Movie added successfully!";
}

if($_GET['success'] == "movie_updated") {

    echo "Movie updated successfully!";
}

?>

</div>

<?php } ?>

<!-- ==========================================
     Add Movie Form
=========================================== -->

<section class="add-movie-box">

<h2>

Add Movie

</h2>

<form
action="add_movie.php"
method="POST"
enctype="multipart/form-data"
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

<select name="status">

<option value="published">

Publish

</option>

<option value="draft">

Draft

</option>

</select>

<input
type="file"
name="poster"
accept="image/*"
>

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

$stmt_movies->bind_param("i", $creator_id);

$stmt_movies->execute();

$result_movies = $stmt_movies->get_result();

while($movie = $result_movies->fetch_assoc()) {

    $movie_id = $movie['movie_id'];

    $media_query = "
    SELECT file_url
    FROM dbProj_media
    WHERE movie_id = ?
    LIMIT 1
    ";

    $media_stmt = $con->prepare($media_query);

    $media_stmt->bind_param("i", $movie_id);

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

</body>

</html>