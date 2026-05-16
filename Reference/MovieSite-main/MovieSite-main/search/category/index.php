<?php

/*
========================================
TMDB API CONFIGURATION
========================================
*/

$api_key = 'a80e29ac528ddd8cf4409afced5495e1';

$genre_endpoint = 'https://api.themoviedb.org/3/genre/movie/list';

$discover_endpoint = 'https://api.themoviedb.org/3/discover/movie';

/*
========================================
FETCH MOVIE GENRES
========================================
*/

$genres_json = file_get_contents(
    "$genre_endpoint?api_key=$api_key"
);

$genres_data = json_decode(
    $genres_json,
    true
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <!-- ==========================================
         Page Metadata
    =========================================== -->

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Browse Categories</title>

    <!-- ==========================================
         Category Page Styling
    =========================================== -->

    <link rel="stylesheet" href="style.css">

</head>

<body>

<!-- ==========================================
     Navigation Header
=========================================== -->

<header>

    <div class="logo">

        <a href="../../">

            <img
                src="../../logo.png"
                alt="Movies"
            />

        </a>

    </div>

    <nav>

        <ul>

            <!-- ==========================================
                 Search Dropdown Menu
            =========================================== -->

            <li class="dropdown">

                <a href="../">

                    Search

                </a>

                <div class="dropdown-content">

                    <a href="./">

                        Browse Categories

                    </a>

                </div>

            </li>

            <!-- ==========================================
                 Account Navigation
            =========================================== -->

            <li>

                <a href="../../account/">

                    Account

                </a>

            </li>

            <!-- ==========================================
                 About Dropdown Menu
            =========================================== -->

            <li class="dropdown">

                <a href="../../about/">

                    About

                </a>

                <div class="dropdown-content">

                    <a href="../../about/">

                        About Us

                    </a>

                    <a href="../../about/movies.html">

                        About Movies

                    </a>

                </div>

            </li>

        </ul>

    </nav>

</header>

<!-- ==========================================
     Main Content
=========================================== -->

<main>

    <!-- ==========================================
         Page Title
    =========================================== -->

    <h1 class="page-title">

        Browse Movie Categories

    </h1>

    <!-- ==========================================
         Genre Selection Form
    =========================================== -->

    <form class="genre-form"
          method="GET">

        <select name="genre"
                required>

            <option value="">

                Select Genre

            </option>

            <?php

            /*
            ------------------------------------------
            Display available genres
            ------------------------------------------
            */

            foreach ($genres_data['genres'] as $genre) {

                echo '<option value="' . $genre['id'] . '">';

                echo $genre['name'];

                echo '</option>';
            }

            ?>

        </select>

        <button type="submit">

            Browse

        </button>

    </form>

    <?php

    /*
    ========================================
    FETCH MOVIES BY GENRE
    ========================================
    */

    if (isset($_GET['genre'])) {

        $selected_genre = $_GET['genre'];

        /*
        ------------------------------------------
        Build discover movies URL
        ------------------------------------------
        */

        $discover_url =
            "$discover_endpoint?api_key=$api_key&with_genres=$selected_genre&sort_by=popularity.desc";

        /*
        ------------------------------------------
        Fetch genre movies from TMDB
        ------------------------------------------
        */

        $discover_json = file_get_contents(
            $discover_url
        );

        $discover_data = json_decode(
            $discover_json,
            true
        );

        /*
        ------------------------------------------
        Retrieve selected genre name
        ------------------------------------------
        */

        $genre_name =
            array_column(
                $genres_data['genres'],
                'name',
                'id'
            )[$selected_genre];

        ?>

        <!-- ==========================================
             Results Title
        =========================================== -->

        <h2 class="results-title">

            Top
            <?php echo htmlspecialchars($genre_name); ?>
            Movies

        </h2>

        <!-- ==========================================
             Movie Cards Container
        =========================================== -->

        <div class="movie-list">

            <?php

            /*
            ------------------------------------------
            Display movies
            ------------------------------------------
            */

            foreach ($discover_data['results'] as $movie) {

                ?>

                <a class="movie-card"
                   href="../../movie/?id=<?php echo $movie['id']; ?>">

                    <!-- ==========================================
                         Movie Poster
                    =========================================== -->

                    <?php

                    if ($movie['poster_path']) {

                        ?>

                        <img
                            class="movie-image"
                            src="https://image.tmdb.org/t/p/w500<?php echo $movie['poster_path']; ?>"
                        >

                        <?php

                    } else {

                        ?>

                        <img
                            class="movie-image"
                            src="../../movies_images/no_image.jpg"
                        >

                        <?php
                    }

                    ?>

                    <!-- ==========================================
                         Movie Information
                    =========================================== -->

                    <div class="movie-info">

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $movie['title']
                            );
                            ?>

                        </h3>

                        <p>

                            Release:
                            <?php echo $movie['release_date']; ?>

                        </p>

                        <p>

                            ⭐
                            <?php
                            echo round(
                                $movie['vote_average'],
                                1
                            );
                            ?>

                        </p>

                    </div>

                </a>

                <?php
            }

            ?>

        </div>

        <?php
    }

    ?>

</main>

<!-- ==========================================
     Footer
=========================================== -->

<footer>

    <p>

        &copy; 2026 MovieSite.
        All rights reserved.

    </p>

</footer>

</body>

</html>