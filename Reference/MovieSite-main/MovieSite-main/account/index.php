<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Account - The Binge Box</title>
        <link rel="stylesheet" href="../shared.css" />
        <link rel="stylesheet" href="account.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    </head>

    <body>
        <?php $base_path = "../"; include "../includes/navbar.php"; ?>
        <main>
            <?php
            session_start();
            error_reporting(E_ALL);
            ini_set('display_errors', 1);

            // Redirect Admins to the admin dashboard
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
                exit();
            }

            // NEW RULE: Redirect Creators to the creator directory index page
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'creator') {
                header("Location: ../creator/index.php");
                exit();
            }

            // Redirect guest users instantly to the login page if not logged in
            if (!isset($_SESSION['username'])) {
                header("Location: ../auth/login.php");
                exit();
            }

            include("../database/DBconn.php");
            $con = getConnection();

            // get email and ID from database and show it
            $user = mysqli_real_escape_string($con, $_SESSION['username']);

            $query = "SELECT user_id, email FROM `dbProj_users` WHERE username='$user'";
            $result = mysqli_query($con, $query) or die(mysqli_error($con));

            if ($row = mysqli_fetch_assoc($result)) {
                $email = $row['email'];
                $userid = $row['user_id'];
            }
            ?>
            <section class='profile'>
                <div class='detailsandlogout'>
                    <img class="profileimg" src='../movies_images/profile.png' alt='profile' />
                    <div class='profile-details'>
                        <h3>Name:</h3><?php echo "<h4> {$_SESSION['username']}</h4>"; ?> 
                        <br>
                        <h3>Email:</h3> <?php echo " <h4> $email </h4> "; ?> 
                    </div>
                    <div class='profile-buttons'>
                        <a href='../auth/logout.php'>
                            <img class="logoutbtn" src='../movies_images/logout.png' alt='logout' />
                        </a>
                    </div>
                </div>
            </section>
            <section class="favmovies">
                <h2>Watchlist:</h2>
                <div class="movie-list">
                    <?php
                    $api_key = "a80e29ac528ddd8cf4409afced5495e1";
                    $userid = isset($_SESSION['id']) ? $_SESSION['id'] : null;

                    if (isset($userid)) {
                        $query = "SELECT movie_id FROM `dbProj_movies` WHERE created_by = '$userid'";
                        $result = mysqli_query($con, $query) or die(mysqli_error($con));
                        while ($row = mysqli_fetch_assoc($result)):
                            $movieid = $row['movie_id'];
                            $url = "https://api.themoviedb.org/3/movie/$movieid?api_key=$api_key&language=en-US";

                            // Use @ to hide the warning and check if the request actually worked
                            $json_data = @file_get_contents($url);

                            if ($json_data !== false) {
                                $response = json_decode($json_data, true);

                                // Only show the movie if the API returned a valid result
                                if (isset($response['id'])) {
                                    ?>
                                    <a href="../movie/?id=<?php echo $response['id']; ?>" class="movie">
                                        <img src="https://image.tmdb.org/t/p/w500<?php echo $response['poster_path']; ?>" class="image"/>
                                        <div class="detbtn">
                                            <div class="details">
                                                <div class="nameofmovie">
                                                    <h1><?php echo htmlspecialchars($response['title']); ?></h1>
                                                </div>
                                            </div>
                                            <img src="../movies_images/remove.png" class="addbtn" data-id="<?php echo $response['id']; ?>"/>
                                        </div>
                                    </a>
                                    <?php
                                }
                            }
                        endwhile;
                    }
                    ?>
                </div>
            </section>
        </main>
        <footer>
            <p>&copy; 2026 The Binge Box. All rights reserved.</p>
        </footer>
        <script>
            $(document).ready(function () {
                $(".addbtn").on("click", function (event) { 
                    event.preventDefault();
                    let movieid = $(this).data("id");
                    let userid = <?php echo $userid ?? 0; ?>;
                    $.ajax({
                        url: "../scripts_php/remove.php",
                        type: "POST",
                        data: {
                            movie_id: movieid,
                            user_id: userid
                        },
                        success: function (data) {
                            location.reload();
                        },
                        error: function () {
                            alert("Error removing item.");
                        }
                    });
                });
            });
        </script>
    </body>

</html>