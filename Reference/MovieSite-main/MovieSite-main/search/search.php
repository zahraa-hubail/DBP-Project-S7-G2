<?php

include("../database/DBconn.php");

$con = getConnection();

/*
========================================
GET SEARCH FILTERS
========================================
*/

$searchTerm = isset($_GET['search'])
    ? trim($_GET['search'])
    : "";

$year = isset($_GET['year'])
    ? trim($_GET['year'])
    : "";

$sort = isset($_GET['sort'])
    ? trim($_GET['sort'])
    : "";

/*
========================================
TMDB API SEARCH
========================================
*/

if (!empty($searchTerm)) {

    $api_key = "a80e29ac528ddd8cf4409afced5495e1";

    $url =
        "https://api.themoviedb.org/3/search/movie?api_key=" .
        $api_key .
        "&query=" .
        urlencode($searchTerm);

    /*
    ----------------------------------------
    Fetch movie data from TMDB API
    ----------------------------------------
    */

    $response = file_get_contents($url);

    $response = json_decode($response, true);

    /*
    ----------------------------------------
    Display TMDB movie results
    ----------------------------------------
    */

    if ($response && isset($response['results'])) {

        foreach ($response['results'] as $movie) {

            $releaseYear = "";

            /*
            ----------------------------------------
            Extract release year
            ----------------------------------------
            */

            if (!empty($movie['release_date'])) {

                $releaseYear = substr(
                    $movie['release_date'],
                    0,
                    4
                );
            }

            /*
            ----------------------------------------
            Apply year filter
            ----------------------------------------
            */

            if (
                !empty($year) &&
                $releaseYear != $year
            ) {
                continue;
            }

            ?>

            <a class="movie"
               href="../movie/?id=<?php echo $movie['id']; ?>">

                <!-- ==========================================
                     Movie Poster
                =========================================== -->

                <?php if ($movie['poster_path']) { ?>

                    <img
                        src="https://image.tmdb.org/t/p/w500<?php echo $movie['poster_path']; ?>"
                        class="image"
                    >

                <?php } else { ?>

                    <img
                        src="../movies_images/no_image.jpg"
                        class="image"
                    >

                <?php } ?>

                <!-- ==========================================
                     Movie Details
                =========================================== -->

                <div class="details">

                    <h2>

                        <?php
                        echo htmlspecialchars($movie['title']);
                        ?>

                    </h2>

                    <p>

                        Release:
                        <?php
                        echo htmlspecialchars($movie['release_date']);
                        ?>

                    </p>

                    <p>

                        TMDB Rating:
                        ⭐ <?php echo $movie['vote_average']; ?>

                    </p>

                </div>

            </a>

            <?php
        }
    }
}

/*
========================================
DATABASE MOVIE SEARCH
========================================
*/

$query = "
SELECT
    m.*,
    AVG(r.stars) AS avg_rating
FROM dbProj_movies m
LEFT JOIN dbProj_ratings r
ON m.movie_id = r.movie_id
WHERE
(
    title LIKE ?
    OR director LIKE ?
    OR description LIKE ?
)
";

/*
========================================
PREPARE SEARCH PARAMETERS
========================================
*/

$params = [];

$types = "";

$like = "%" . $searchTerm . "%";

$params[] = $like;
$types .= "s";

$params[] = $like;
$types .= "s";

$params[] = $like;
$types .= "s";

/*
========================================
YEAR FILTER
========================================
*/

if (!empty($year)) {

    $query .= "
    AND release_year = ?
    ";

    $params[] = $year;

    $types .= "i";
}

/*
========================================
GROUP MOVIES
========================================
*/

$query .= "
GROUP BY m.movie_id
";

/*
========================================
SORTING OPTIONS
========================================
*/

switch ($sort) {

    case "rating_desc":

        $query .= "
        ORDER BY avg_rating DESC
        ";

        break;

    case "rating_asc":

        $query .= "
        ORDER BY avg_rating ASC
        ";

        break;

    case "latest":

        $query .= "
        ORDER BY release_year DESC
        ";

        break;

    case "oldest":

        $query .= "
        ORDER BY release_year ASC
        ";

        break;

    default:

        $query .= "
        ORDER BY created_at DESC
        ";
}

/*
========================================
EXECUTE DATABASE QUERY
========================================
*/

$stmt = $con->prepare($query);

$stmt->bind_param($types, ...$params);

$stmt->execute();

$result = $stmt->get_result();

/*
========================================
DISPLAY DATABASE MOVIES
========================================
*/

while ($movie = $result->fetch_assoc()) {

    ?>

<a class="movie"
   href="../movie/?custom_id=<?php echo $movie['movie_id']; ?>">

        <!-- ==========================================
             Default Poster
        =========================================== -->

        <img
            src="../movies_images/no_image.jpg"
            class="image"
        >

        <!-- ==========================================
             Movie Details
        =========================================== -->

        <div class="details">

            <h2>

                <?php
                echo htmlspecialchars($movie['title']);
                ?>

            </h2>

            <p>

                Director:
                <?php
                echo htmlspecialchars($movie['director']);
                ?>

            </p>

            <p>

                Release Year:
                <?php
                echo htmlspecialchars($movie['release_year']);
                ?>

            </p>

            <p>

                Rating:
                ⭐

                <?php

                echo $movie['avg_rating']
                    ? round($movie['avg_rating'], 1)
                    : "No ratings";

                ?>

            </p>

            <!-- ==========================================
                 Creator Upload Badge
            =========================================== -->

            <p class="creator-badge">

                Creator Upload

            </p>

        </div>

    </a>

    <?php
}

/*
========================================
CLOSE DATABASE CONNECTION
========================================
*/

$stmt->close();

$con->close();

?>