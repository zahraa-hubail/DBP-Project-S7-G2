<?php

session_start();

include("../database/DBconn.php");

$con = getConnection();

/*
--------------------------------------------------
Check if user is logged in
--------------------------------------------------
*/

if (!isset($_SESSION['id'])) {

    die("You must be logged in.");
}

/*
--------------------------------------------------
Allow only POST requests
--------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    die("Invalid request.");
}

/*
--------------------------------------------------
Retrieve review deletion data
--------------------------------------------------
*/

$user_id = intval($_SESSION['id']);

$movie_id = intval($_POST['movie_id']);

$review_id = intval($_POST['review_id']);

/*
--------------------------------------------------
Delete review comment
--------------------------------------------------
*/

$delete_comment = "
DELETE FROM dbProj_comments
WHERE comment_id = ?
AND user_id = ?
";

$stmt_comment = $con->prepare($delete_comment);

$stmt_comment->bind_param(
    "ii",
    $review_id,
    $user_id
);

$stmt_comment->execute();

/*
--------------------------------------------------
Delete associated movie rating
--------------------------------------------------
*/

$delete_rating = "
DELETE FROM dbProj_ratings
WHERE movie_id = ?
AND user_id = ?
";

$stmt_rating = $con->prepare($delete_rating);

$stmt_rating->bind_param(
    "ii",
    $movie_id,
    $user_id
);

$stmt_rating->execute();

/*
--------------------------------------------------
Redirect back after deletion
--------------------------------------------------
*/

header(
    "Location: ../movie/?id=$movie_id&success=review_deleted"
);

exit();

?>