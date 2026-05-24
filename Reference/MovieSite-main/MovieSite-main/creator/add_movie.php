<?php

session_start();

/*
--------------------------------------------------
Enable error reporting for debugging
--------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
Retrieve creator account information
--------------------------------------------------
*/

$username = $_SESSION['username'];

$user_query = "
SELECT user_id
FROM dbProj_users
WHERE username = ?
";

$user_stmt = $con->prepare($user_query);

$user_stmt->bind_param(
    "s",
    $username
);

$user_stmt->execute();

$user_result = $user_stmt->get_result();

$user = $user_result->fetch_assoc();

$created_by = $user['user_id'];

/*
--------------------------------------------------
Retrieve movie form data
--------------------------------------------------
*/

$title = trim($_POST['title']);

$description = trim($_POST['description']);

$director = trim($_POST['director']);

$release_year = intval($_POST['release_year']);

$status = trim($_POST['status']);

$genre_id = intval($_POST['genre_id']);

/*
--------------------------------------------------
Generate unique movie ID
--------------------------------------------------
*/

$movie_id = rand(100000, 999999);

/*
--------------------------------------------------
Insert movie into database
--------------------------------------------------
*/

$query = "
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
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?
)
";

$stmt = $con->prepare($query);

$stmt->bind_param(
    "iisssis",
    $movie_id,
    $created_by,
    $title,
    $description,
    $director,
    $release_year,
    $status
);

if(!$stmt->execute()) {

    die($stmt->error);
}

/*
--------------------------------------------------
Insert movie genre into junction table
--------------------------------------------------
*/

$genre_query = "
INSERT INTO dbProj_movie_genres
(
    genre_id,
    movie_id
)
VALUES
(
    ?,
    ?
)
";

$genre_stmt = $con->prepare($genre_query);

$genre_stmt->bind_param(
    "ii",
    $genre_id,
    $movie_id
);

$genre_stmt->execute();

/*
--------------------------------------------------
Assign hardcoded movie poster
--------------------------------------------------
*/

$file_url = "movies_images/Narnia.jpg";

/*
--------------------------------------------------
Save movie poster into media table
--------------------------------------------------
*/

$media_query = "
INSERT INTO dbProj_media
(
    movie_id,
    media_type,
    file_url
)
VALUES
(
    ?,
    'image',
    ?
)
";

$media_stmt = $con->prepare($media_query);

$media_stmt->bind_param(
    "is",
    $movie_id,
    $file_url
);

$media_stmt->execute();

/*
--------------------------------------------------
Redirect after successful movie creation
--------------------------------------------------
*/

header(
    "Location: index.php?success=movie_added"
);

exit();

?>