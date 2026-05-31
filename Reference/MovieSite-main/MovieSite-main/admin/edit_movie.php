<?php

session_start();
include("../database/DBconn.php");
$con = getConnection();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: all_movies.php");
    exit();
}

$movie_id = intval($_GET['id']);

// Fetch movie details
$stmt = $con->prepare("SELECT * FROM dbProj_movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    header("Location: all_movies.php?error=not_found");
    exit();
}

// Fetch current poster (image only)
$media_q = $con->prepare("SELECT file_url FROM dbProj_media WHERE movie_id = ? AND media_type = 'image' LIMIT 1");
$media_q->bind_param("i", $movie_id);
$media_q->execute();
$current_media  = $media_q->get_result()->fetch_assoc();
$current_poster = $current_media ? '../' . $current_media['file_url'] : '../movies_images/no_image.jpg';

// Fetch current trailer
$trailer_q = $con->prepare("SELECT file_url FROM dbProj_media WHERE movie_id = ? AND media_type = 'video' LIMIT 1");
$trailer_q->bind_param("i", $movie_id);
$trailer_q->execute();
$cur_trailer = $trailer_q->get_result()->fetch_assoc();

// Fetch all genres
$all_genres = mysqli_query($con, "SELECT * FROM dbProj_genres ORDER BY name ASC");

// Fetch current genre
$genre_q = $con->prepare("SELECT genre_id FROM dbProj_movie_genres WHERE movie_id = ? LIMIT 1");
$genre_q->bind_param("i", $movie_id);
$genre_q->execute();
$current_genre    = $genre_q->get_result()->fetch_assoc();
$current_genre_id = $current_genre ? $current_genre['genre_id'] : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie | Admin</title>
    <link rel="stylesheet" href="../shared.css">
    <link rel="stylesheet" href="../creator/creator.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        main { max-width: 800px; }
    </style>
</head>
<body>

<?php $base_path = "../"; include "../includes/navbar.php"; ?>

<main>

    <h1>Edit Movie</h1>
    <p class="welcome">Editing as <strong>Admin</strong> — changes apply immediately.</p>

    <section class="add-movie-box">

        <!-- Current poster preview -->
        <div style="margin-bottom:20px;">
            <p style="font-weight:600; margin-bottom:8px;">Current Poster:</p>
            <img src="<?php echo htmlspecialchars($current_poster); ?>"
                 alt="Current poster"
                 style="height:200px; border-radius:10px; object-fit:cover; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
        </div>

        <form action="update_movie.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">

            <input type="text" name="title"
                   value="<?php echo htmlspecialchars($movie['title']); ?>"
                   placeholder="Movie Title" required>

            <textarea name="description" placeholder="Movie Description" required
            ><?php echo htmlspecialchars($movie['description']); ?></textarea>

            <input type="text" name="director"
                   value="<?php echo htmlspecialchars($movie['director']); ?>"
                   placeholder="Director Name" required>

            <input type="number" name="release_year"
                   value="<?php echo $movie['release_year']; ?>"
                   placeholder="Release Year" required>

            <select name="genre_id">
                <option value="0">— Keep current genre —</option>
                <?php while ($g = mysqli_fetch_assoc($all_genres)): ?>
                <option value="<?php echo $g['genre_id']; ?>"
                    <?php echo $g['genre_id'] == $current_genre_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($g['name']); ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="status">
                <option value="published" <?php echo $movie['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="draft"     <?php echo $movie['status'] === 'draft'     ? 'selected' : ''; ?>>Draft</option>
            </select>

            <input type="url" name="trailer_url"
                   value="<?php echo htmlspecialchars($cur_trailer['file_url'] ?? ''); ?>"
                   placeholder="YouTube Trailer URL (optional)">
            <small style="color:#888;">Leave blank to remove trailer. Paste a YouTube link to add/change it.</small>

            <label style="font-weight:600; margin-top:8px; display:block;">
                Change Poster (optional)
            </label>
            <input type="file" name="poster" accept="image/jpeg,image/png,image/gif,image/webp">
            <small style="color:#888;">Leave empty to keep the current poster. Max 5 MB.</small>

            <button type="submit">Save Changes</button>

        </form>
    </section>

</main>

</body>
</html>
