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
Check if movie ID exists
--------------------------------------------------
*/

if(!isset($_GET['id'])) {

    die("Movie not found");
}

$movie_id = intval($_GET['id']);

/*
--------------------------------------------------
Retrieve selected movie information
--------------------------------------------------
*/

$query = "
SELECT *
FROM dbProj_movies
WHERE movie_id = ?
";

$stmt = $con->prepare($query);

$stmt->bind_param("i", $movie_id);

$stmt->execute();

$result = $stmt->get_result();

$movie = $result->fetch_assoc();

/*
--------------------------------------------------
Validate movie existence
--------------------------------------------------
*/

if(!$movie) {

    die("Movie not found");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Edit Movie</title>

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

<a href="index.php">

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

<!-- ==========================================
     Edit Movie Form
=========================================== -->

<section class="add-movie-box">

<h1>

Edit Movie

</h1>

<?php
// Fetch current poster for preview
$media_q = $con->prepare("SELECT file_url FROM dbProj_media WHERE movie_id = ? LIMIT 1");
$media_q->bind_param("i", $movie_id);
$media_q->execute();
$current_media = $media_q->get_result()->fetch_assoc();
$current_poster = $current_media ? '../' . $current_media['file_url'] : '../movies_images/no_image.jpg';
?>

<div style="margin-bottom:20px;">
    <p style="font-weight:600; margin-bottom:8px;">Current Poster:</p>
    <img src="<?php echo htmlspecialchars($current_poster); ?>"
         alt="Current poster"
         style="height:200px; border-radius:10px; object-fit:cover; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
</div>

<form
action="update_movie.php"
method="POST"
enctype="multipart/form-data"
>

<input
type="hidden"
name="movie_id"
value="<?php echo $movie['movie_id']; ?>"
>

<input
type="text"
name="title"
value="<?php echo htmlspecialchars($movie['title']); ?>"
required
>

<textarea
name="description"
required
><?php echo htmlspecialchars($movie['description']); ?></textarea>

<input
type="text"
name="director"
value="<?php echo htmlspecialchars($movie['director']); ?>"
required
>

<input
type="number"
name="release_year"
value="<?php echo $movie['release_year']; ?>"
required
>

<select name="status">

<option
value="published"
<?php if($movie['status'] == 'published') echo 'selected'; ?>
>

Published

</option>

<option
value="draft"
<?php if($movie['status'] == 'draft') echo 'selected'; ?>
>

Draft

</option>

</select>

<?php
$cur_trailer_q = $con->prepare("SELECT file_url FROM dbProj_media WHERE movie_id = ? AND media_type = 'video' LIMIT 1");
$cur_trailer_q->bind_param("i", $movie_id);
$cur_trailer_q->execute();
$cur_trailer = $cur_trailer_q->get_result()->fetch_assoc();
?>
<input type="url" name="trailer_url"
       value="<?php echo htmlspecialchars($cur_trailer['file_url'] ?? ''); ?>"
       placeholder="YouTube Trailer URL (optional)">
<small style="color:#888;">Leave blank to remove trailer. Paste a YouTube link to add/change it.</small>

<label style="font-weight:600; margin-top:8px; display:block;">
    Change Poster (optional)
</label>

<input
type="file"
name="poster"
accept="image/jpeg,image/png,image/gif,image/webp"
>

<small style="color:#888;">Leave empty to keep the current poster. Max 5 MB.</small>

<button type="submit">

Update Movie

</button>

</form>

</section>

</main>

</body>

</html>