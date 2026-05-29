<!DOCTYPE html>

<html lang="en">

<head>

    <!-- ==========================================
         Page Metadata
    =========================================== -->

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Search Movies</title>

    <!-- ==========================================
         Search Page Styling
    =========================================== -->

    <link rel="stylesheet" href="search.css">

    <!-- ==========================================
         jQuery Library
    =========================================== -->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- ==========================================
         AJAX Live Search Script
    =========================================== -->

    <script>

        $(document).ready(function () {

            /*
            ------------------------------------------
            Load search results dynamically
            ------------------------------------------
            */

            function loadResults() {

                var searchTerm = $("#search").val();

                var year = $("#year").val();

                var sort = $("#sort").val();

                /*
                ------------------------------------------
                Send AJAX request to search.php
                ------------------------------------------
                */

                $.ajax({

                    type: "GET",

                    url: "search.php",

                    data: {

                        search: searchTerm,

                        year: year,

                        sort: sort
                    },

                    /*
                    ------------------------------------------
                    Display returned search results
                    ------------------------------------------
                    */

                    success: function (response) {

                        $("#search-results").html(response);
                    }
                });
            }

            /*
            ------------------------------------------
            Trigger live search events
            ------------------------------------------
            */

            $("#search").on("input", loadResults);

            $("#year").on("change", loadResults);

            $("#sort").on("change", loadResults);

        });

    </script>

</head>

<body>

<!-- ==========================================
     Navigation Header
=========================================== -->

<header>

    <div class="logo">

        <a href="../">

            <img src="../logo.png" alt="Movies">

        </a>

    </div>

    <nav>

        <ul>

            <!-- ==========================================
                 Search Navigation Dropdown
            =========================================== -->

            <li class="dropdown">

                <a href="./">Search</a>

                <div class="dropdown-content">

                    <a href="./category/">

                        Search Category

                    </a>

                </div>

            </li>

            <!-- ==========================================
                 Account Navigation Link
            =========================================== -->

            <li>

                <a href="../account/">

                    Account

                </a>

            </li>

            <!-- ==========================================
                 About Navigation Dropdown
            =========================================== -->

            <li class="dropdown">

                <a href="../about/">

                    About

                </a>

                <div class="dropdown-content">

                    <a href="../about/">

                        About Us

                    </a>

                    <a href="../about/movies.html">

                        About Movies

                    </a>

                </div>

            </li>

        </ul>

    </nav>

</header>

<!-- ==========================================
     Main Search Content
=========================================== -->

<main>

    <h1>

        Search Movies

    </h1>

    <!-- ==========================================
         Search Filters
    =========================================== -->

    <div class="search-controls">

        <!-- ==========================================
             Search Input
        =========================================== -->

        <input
            type="text"
            id="search"
            placeholder="Search by title, description or director..."
        >

        <!-- ==========================================
             Release Year Filter
        =========================================== -->

        <select id="year">

            <option value="">

                All Years

            </option>

            <?php

            /*
            ------------------------------------------
            Generate release year options
            ------------------------------------------
            */

            for ($i = 2026; $i >= 1980; $i--) {

                echo "<option value='$i'>$i</option>";
            }

            ?>

        </select>

        <!-- ==========================================
             Sorting Options
        =========================================== -->

        <select id="sort">

            <option value="">

                Default

            </option>

            <option value="rating_desc">

                Highest Rated

            </option>

            <option value="rating_asc">

                Lowest Rated

            </option>

            <option value="latest">

                Latest Releases

            </option>

            <option value="oldest">

                Oldest Releases

            </option>

        </select>

    </div>

    <!-- ==========================================
         Dynamic Search Results
    =========================================== -->

    <div id="search-results"></div>

</main>

<!-- ==========================================
     Footer
=========================================== -->

<footer>

    <p>

        &copy; 2026 MovieSite

    </p>

</footer>

</body>

</html>