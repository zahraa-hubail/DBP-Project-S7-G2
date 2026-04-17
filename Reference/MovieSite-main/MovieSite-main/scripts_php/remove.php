<?php
    session_start();

    include("../database/DBconn.php");
    $con = getConnection();
    $userid = $_POST['user_id'];
    $movieid = $_POST['movie_id'];

    // Prepare and execute the DELETE query
    $query = "DELETE FROM `moviesowned` WHERE user_id = ? AND movie_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $userid, $movieid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Movie removed successfully.";
    } else {
        echo "Failed to remove the movie.";
    }

    $stmt->close();
    $con->close();
?>
