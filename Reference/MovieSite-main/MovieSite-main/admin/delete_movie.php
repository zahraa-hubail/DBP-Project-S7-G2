<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

// 1. Security: Only Admin can delete
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Process Delete
if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);

    $query = "DELETE FROM dbProj_movies WHERE movie_id = $movie_id";
    
    if (mysqli_query($con, $query)) {
        header("Location: manage_movies.php?msg=movie_deleted");
    } else {
        header("Location: manage_movies.php?error=delete_failed");
    }
} else {
    header("Location: manage_movies.php");
}

if (mysqli_query($con, $query)) {
    $return_to = ($_GET['from'] == 'dashboard') ? "dashboard.php" : "all_movies.php";
    header("Location: $return_to?msg=movie_deleted");
}

?>