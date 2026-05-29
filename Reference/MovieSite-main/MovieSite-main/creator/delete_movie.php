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

    die("Not authorized");
}

/*
--------------------------------------------------
Retrieve selected movie ID
--------------------------------------------------
*/

if(!isset($_GET['id'])) {

    die("Movie ID missing");
}

$movie_id = intval($_GET['id']);

/*
--------------------------------------------------
Delete movie media records
--------------------------------------------------
*/

$media_query = "
DELETE FROM dbProj_media
WHERE movie_id = ?
";

$media_stmt = $con->prepare($media_query);

$media_stmt->bind_param(
    "i",
    $movie_id
);

$media_stmt->execute();

/*
--------------------------------------------------
Delete movie comments
--------------------------------------------------
*/

$comment_query = "
DELETE FROM dbProj_comments
WHERE movie_id = ?
";

$comment_stmt = $con->prepare($comment_query);

$comment_stmt->bind_param(
    "i",
    $movie_id
);

$comment_stmt->execute();

/*
--------------------------------------------------
Delete movie ratings
--------------------------------------------------
*/

$rating_query = "
DELETE FROM dbProj_ratings
WHERE movie_id = ?
";

$rating_stmt = $con->prepare($rating_query);

$rating_stmt->bind_param(
    "i",
    $movie_id
);

$rating_stmt->execute();

/*
--------------------------------------------------
Delete movie from database
--------------------------------------------------
*/

$movie_query = "
DELETE FROM dbProj_movies
WHERE movie_id = ?
";

$movie_stmt = $con->prepare($movie_query);

$movie_stmt->bind_param(
    "i",
    $movie_id
);

$movie_stmt->execute();

/*
--------------------------------------------------
Redirect after successful deletion
--------------------------------------------------
*/

header(
    "Location: index.php?deleted=1"
);

exit();

?>