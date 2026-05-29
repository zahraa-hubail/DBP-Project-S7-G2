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

    die("Unauthorized");
}

/*
--------------------------------------------------
Retrieve updated movie form data
--------------------------------------------------
*/

$movie_id = intval($_POST['movie_id']);

$title = trim($_POST['title']);

$description = trim($_POST['description']);

$director = trim($_POST['director']);

$release_year = intval($_POST['release_year']);

$status = trim($_POST['status']);

/*
--------------------------------------------------
Update movie information in database
--------------------------------------------------
*/

$query = "
UPDATE dbProj_movies
SET
title = ?,
description = ?,
director = ?,
release_year = ?,
status = ?
WHERE movie_id = ?
";

$stmt = $con->prepare($query);

$stmt->bind_param(
    "sssisi",
    $title,
    $description,
    $director,
    $release_year,
    $status,
    $movie_id
);

$stmt->execute();

/*
--------------------------------------------------
Redirect after successful update
--------------------------------------------------
*/

header(
    "Location: index.php?success=movie_updated"
);

exit();

?>