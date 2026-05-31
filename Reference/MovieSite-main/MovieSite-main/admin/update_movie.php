<?php

session_start();
include("../database/DBconn.php");
$con = getConnection();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$movie_id     = intval($_POST['movie_id']     ?? 0);
$title        = trim($_POST['title']          ?? '');
$description  = trim($_POST['description']    ?? '');
$director     = trim($_POST['director']       ?? '');
$release_year = intval($_POST['release_year'] ?? 0);
$status       = trim($_POST['status']         ?? 'draft');
$genre_id     = intval($_POST['genre_id']     ?? 0);

if ($movie_id === 0 || $title === '') {
    header("Location: all_movies.php?error=missing_fields");
    exit();
}

// Update core movie fields
$stmt = $con->prepare("
    UPDATE dbProj_movies
    SET title = ?, description = ?, director = ?, release_year = ?, status = ?
    WHERE movie_id = ?
");
$stmt->bind_param("sssisi", $title, $description, $director, $release_year, $status, $movie_id);
$stmt->execute();

// Update genre only if a real genre was chosen
if ($genre_id > 0) {
    $del = $con->prepare("DELETE FROM dbProj_movie_genres WHERE movie_id = ?");
    $del->bind_param("i", $movie_id);
    $del->execute();

    $ins = $con->prepare("INSERT INTO dbProj_movie_genres (genre_id, movie_id) VALUES (?, ?)");
    $ins->bind_param("ii", $genre_id, $movie_id);
    $ins->execute();
}

// Handle optional poster replacement
$file = $_FILES['poster'] ?? null;

if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: edit_movie.php?id=$movie_id&error=upload_failed");
        exit();
    }

    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mime, $allowed)) {
        header("Location: edit_movie.php?id=$movie_id&error=invalid_type");
        exit();
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        header("Location: edit_movie.php?id=$movie_id&error=file_too_large");
        exit();
    }

    $project_root = realpath(__DIR__ . '/..');
    $upload_dir   = $project_root . DIRECTORY_SEPARATOR . 'movies_images' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('poster_', true) . '.' . $ext;
    $dest     = $upload_dir . $filename;

    $saved = move_uploaded_file($file['tmp_name'], $dest);
    if (!$saved && is_uploaded_file($file['tmp_name'])) {
        $saved = copy($file['tmp_name'], $dest);
        if ($saved) @unlink($file['tmp_name']);
    }
    if (!$saved) {
        $raw = file_get_contents($file['tmp_name']);
        if ($raw !== false && file_put_contents($dest, $raw) !== false) $saved = true;
    }
    if (!$saved) {
        header("Location: edit_movie.php?id=$movie_id&error=save_failed");
        exit();
    }

    $new_file_url = "movies_images/uploads/" . $filename;

    $del = $con->prepare("DELETE FROM dbProj_media WHERE movie_id = ? AND media_type = 'image'");
    $del->bind_param("i", $movie_id);
    $del->execute();

    $ins = $con->prepare("INSERT INTO dbProj_media (movie_id, media_type, file_url) VALUES (?, 'image', ?)");
    $ins->bind_param("is", $movie_id, $new_file_url);
    $ins->execute();
}

// Handle YouTube trailer URL
$trailer_url = trim($_POST['trailer_url'] ?? '');
$del_trailer = $con->prepare("DELETE FROM dbProj_media WHERE movie_id = ? AND media_type = 'video'");
$del_trailer->bind_param("i", $movie_id);
$del_trailer->execute();
if ($trailer_url !== '' && filter_var($trailer_url, FILTER_VALIDATE_URL)) {
    $ins_trailer = $con->prepare("INSERT INTO dbProj_media (movie_id, media_type, file_url) VALUES (?, 'video', ?)");
    $ins_trailer->bind_param("is", $movie_id, $trailer_url);
    $ins_trailer->execute();
}

header("Location: all_movies.php?msg=movie_updated");
exit();
