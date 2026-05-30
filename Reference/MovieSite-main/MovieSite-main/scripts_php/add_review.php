<?php

session_start();

include("../database/DBconn.php");

$con = getConnection();

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
Check if user is logged in
--------------------------------------------------
*/

if (!isset($_SESSION['id'])) {

    die("You must be logged in.");
}

/*
--------------------------------------------------
Retrieve review form data
--------------------------------------------------
*/

$user_id = intval($_SESSION['id']);

$movie_id = intval($_POST['movie_id']);

$review_text = trim($_POST['review_text']);

$rating = intval($_POST['rating']);

/*
--------------------------------------------------
Validate review information
--------------------------------------------------
*/

if (empty($review_text)) {

    die("Review cannot be empty.");
}

if ($rating < 1 || $rating > 5) {

    die("Invalid rating.");
}

/*
--------------------------------------------------
Check if user already reviewed this movie
--------------------------------------------------
*/

$check_query = "
SELECT *
FROM dbProj_ratings
WHERE user_id = ?
AND movie_id = ?
";

$stmt_check = $con->prepare($check_query);

$stmt_check->bind_param(
    "ii",
    $user_id,
    $movie_id
);

$stmt_check->execute();

$check_result = $stmt_check->get_result();

/*
--------------------------------------------------
Update existing review and rating
--------------------------------------------------
*/

if ($check_result->num_rows > 0) {

    /*
    ----------------------------------------------
    Update review comment
    ----------------------------------------------
    */

    $update_comment = "
    UPDATE dbProj_comments
    SET body = ?
    WHERE user_id = ?
    AND movie_id = ?
    ";

    $stmt_update_comment = $con->prepare($update_comment);

    $stmt_update_comment->bind_param(
        "sii",
        $review_text,
        $user_id,
        $movie_id
    );

    $stmt_update_comment->execute();

    /*
    ----------------------------------------------
    Update movie rating
    ----------------------------------------------
    */

    $update_rating = "
    UPDATE dbProj_ratings
    SET stars = ?
    WHERE user_id = ?
    AND movie_id = ?
    ";

    $stmt_update_rating = $con->prepare($update_rating);

    $stmt_update_rating->bind_param(
        "iii",
        $rating,
        $user_id,
        $movie_id
    );

    $stmt_update_rating->execute();

} else {

    /*
----------------------------------------------
Insert new review comment
----------------------------------------------
*/

$query_comment = "
INSERT INTO dbProj_comments
(
    movie_id,
    user_id,
    body,
    is_removed,
    created_at
)
VALUES
(
    ?, ?, ?, 0,
    DATE_ADD(NOW(), INTERVAL 3 HOUR)
)
";

    $stmt_comment = $con->prepare($query_comment);

    $stmt_comment->bind_param(
        "iis",
        $movie_id,
        $user_id,
        $review_text
    );

    $stmt_comment->execute();

    /*
    ----------------------------------------------
    Insert new movie rating
    ----------------------------------------------
    */

    $query_rating = "
    INSERT INTO dbProj_ratings
    (
        user_id,
        movie_id,
        stars
    )
    VALUES (?, ?, ?)
    ";

    $stmt_rating = $con->prepare($query_rating);

    $stmt_rating->bind_param(
        "iii",
        $user_id,
        $movie_id,
        $rating
    );

    $stmt_rating->execute();
}

/*
--------------------------------------------------
Redirect back to movie page
--------------------------------------------------
*/

$is_custom = intval($_POST['is_custom'] ?? 0);
$param     = $is_custom ? "custom_id" : "id";

header("Location: ../movie/?{$param}={$movie_id}&success=review_added");
exit();

?>