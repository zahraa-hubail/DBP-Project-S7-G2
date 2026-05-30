<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'creator') {
    header("Location: ../auth/login.php");
    exit();
}

$user_stmt = $con->prepare("SELECT user_id FROM dbProj_users WHERE username = ?");
$user_stmt->bind_param("s", $_SESSION['username']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
if (!$user) { header("Location: index.php?error=user_not_found"); exit(); }
$created_by = $user['user_id'];

$title        = trim($_POST['title']        ?? '');
$description  = trim($_POST['description']  ?? '');
$director     = trim($_POST['director']     ?? '');
$release_year = intval($_POST['release_year'] ?? 0);
$status       = trim($_POST['status']       ?? 'draft');
$genre_id     = intval($_POST['genre_id']   ?? 0);

if ($title === '' || $description === '' || $director === '' || $release_year === 0 || $genre_id === 0) {
    header("Location: index.php?error=missing_fields");
    exit();
}

/* --------------------------------------------------
   Resolve absolute upload directory
   realpath() normalises Windows backslashes
   -------------------------------------------------- */
$project_root = realpath(__DIR__ . '/..');
$upload_dir   = $project_root . DIRECTORY_SEPARATOR . 'movies_images' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
$file_url     = "movies_images/no_image.jpg"; // safe fallback

$file = $_FILES['poster'] ?? null;

if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: index.php?error=upload_failed");
        exit();
    }

    /* MIME validation — with fallback when fileinfo ext is absent */
    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (extension_loaded('fileinfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
    } else {
        $ext_map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
        $mime    = $ext_map[strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))] ?? '';
    }

    if (!in_array($mime, $allowed_mime)) {
        header("Location: index.php?error=invalid_type");
        exit();
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        header("Location: index.php?error=file_too_large");
        exit();
    }

    /* Create uploads directory if missing */
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('poster_', true) . '.' . $ext;
    $dest     = $upload_dir . $filename;

    /* Three-stage fallback for Windows XAMPP compatibility */
    $saved = move_uploaded_file($file['tmp_name'], $dest);

    if (!$saved && is_uploaded_file($file['tmp_name'])) {
        $saved = copy($file['tmp_name'], $dest);
        if ($saved) @unlink($file['tmp_name']);
    }

    if (!$saved) {
        $raw_data = file_get_contents($file['tmp_name']);
        if ($raw_data !== false && file_put_contents($dest, $raw_data) !== false) {
            $saved = true;
        }
    }

    if (!$saved) {
        header("Location: index.php?error=save_failed");
        exit();
    }

    $file_url = "movies_images/uploads/" . $filename;
}

/* Unique movie ID */
do {
    $movie_id = rand(100000, 999999);
    $chk = $con->prepare("SELECT movie_id FROM dbProj_movies WHERE movie_id = ?");
    $chk->bind_param("i", $movie_id);
    $chk->execute();
    $chk->store_result();
} while ($chk->num_rows > 0);

/* Insert movie */
$stmt = $con->prepare("INSERT INTO dbProj_movies (movie_id,created_by,title,description,director,release_year,status) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param("iisssis", $movie_id, $created_by, $title, $description, $director, $release_year, $status);
if (!$stmt->execute()) { header("Location: index.php?error=db_error"); exit(); }

/* Insert genre */
$g = $con->prepare("INSERT INTO dbProj_movie_genres (genre_id, movie_id) VALUES (?, ?)");
$g->bind_param("ii", $genre_id, $movie_id);
$g->execute();

/* Insert image media */
$m = $con->prepare("INSERT INTO dbProj_media (movie_id, media_type, file_url) VALUES (?, 'image', ?)");
$m->bind_param("is", $movie_id, $file_url);
$m->execute();

/* Insert YouTube trailer if provided */
$trailer_url = trim($_POST['trailer_url'] ?? '');
if ($trailer_url !== '' && filter_var($trailer_url, FILTER_VALIDATE_URL)) {
    $tm = $con->prepare("INSERT INTO dbProj_media (movie_id, media_type, file_url) VALUES (?, 'video', ?)");
    $tm->bind_param("is", $movie_id, $trailer_url);
    $tm->execute();
}

header("Location: index.php?success=movie_added");
exit();
