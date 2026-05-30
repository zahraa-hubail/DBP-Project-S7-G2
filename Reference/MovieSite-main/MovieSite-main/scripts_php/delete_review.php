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

// Detect if the logged-in user has administrative rights
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

/*
--------------------------------------------------
Find Associated Movie Rating Author (BEFORE Deletion)
--------------------------------------------------
*/
if ($is_admin) {
    // Grab the original author's ID before deleting the comment row
    $find_author = $con->prepare("SELECT user_id FROM dbProj_comments WHERE comment_id = ?");
    if ($find_author) {
        $find_author->bind_param("i", $review_id);
        $find_author->execute();
        $res = $find_author->get_result()->fetch_assoc();
        $target_user = $res ? intval($res['user_id']) : $user_id;
    } else {
        $target_user = $user_id;
    }
} else {
    $target_user = $user_id;
}

/*
--------------------------------------------------
Delete review comment
--------------------------------------------------
*/
if ($is_admin) {
    // Admins can delete any comment ID across the system
    $delete_comment = "DELETE FROM dbProj_comments WHERE comment_id = ?";
    $stmt_comment = $con->prepare($delete_comment);
    $stmt_comment->bind_param("i", $review_id);
} else {
    // Normal members can only delete comments matching their user ID
    $delete_comment = "DELETE FROM dbProj_comments WHERE comment_id = ? AND user_id = ?";
    $stmt_comment = $con->prepare($delete_comment);
    $stmt_comment->bind_param("ii", $review_id, $user_id);
}
$stmt_comment->execute();

/*
--------------------------------------------------
Delete associated movie rating
--------------------------------------------------
*/
$delete_rating = "DELETE FROM dbProj_ratings WHERE movie_id = ? AND user_id = ?";
$stmt_rating = $con->prepare($delete_rating);
$stmt_rating->bind_param("ii", $movie_id, $target_user);
$stmt_rating->execute();

/*
--------------------------------------------------
Redirect back to your exact path (movie/index.php)
--------------------------------------------------
*/
header("Location: ../movie/index.php?id=" . $movie_id . "&status=deleted");
exit();
?>