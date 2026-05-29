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

<form
action="update_movie.php"
method="POST"
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

<button type="submit">

Update Movie

</button>

</form>

</section>

</main>

</body>

</html>