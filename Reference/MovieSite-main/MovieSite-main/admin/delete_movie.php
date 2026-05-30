<?php

session_start();
include("../database/DBconn.php");
$con = getConnection();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$return_to = (isset($_GET['from']) && $_GET['from'] === 'dashboard')
    ? "dashboard.php"
    : "all_movies.php";

if (!isset($_GET['id'])) {
    header("Location: $return_to");
    exit();
}

$movie_id = intval($_GET['id']);

// Delete child records first to avoid FK constraint errors
$con->prepare("DELETE FROM dbProj_comments      WHERE movie_id = ?")->execute([$movie_id]) ?? null;
$con->prepare("DELETE FROM dbProj_ratings        WHERE movie_id = ?")->execute([$movie_id]) ?? null;
$con->prepare("DELETE FROM dbProj_media          WHERE movie_id = ?")->execute([$movie_id]) ?? null;
$con->prepare("DELETE FROM dbProj_movie_genres   WHERE movie_id = ?")->execute([$movie_id]) ?? null;

// Delete the movie itself
$stmt = $con->prepare("DELETE FROM dbProj_movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: $return_to?msg=movie_deleted");
} else {
    header("Location: $return_to?error=delete_failed");
}
exit();
