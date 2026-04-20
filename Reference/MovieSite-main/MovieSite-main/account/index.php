<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Account - Movie Shop Website</title>
  <link rel="stylesheet" href="account.css" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>

<body>
<header>
    <div class="logo">
      <a href="../"><img src="../logo.png" alt="Movies" /></a>
    </div>
    <nav>
      <ul>
        <li class="dropdown">
            <a href="../search/">Search</a>
            <div class="dropdown-content">
                <a href="../search/category/">Search Category</a>
            </div>
        </li>
        <li><a href="./">Account</a></li>
        <li class="dropdown">
          <a href="../about/">About</a>
          <div class="dropdown-content">
            <a href="../about/">About Us</a>
            <a href="../about/movies.html">About Movies</a>
          </div>
        </li>
      </ul>
    </nav>
  </header>
  <main>
    <?php
    // PREVIOUS CODE
//        session_start();
//        $con = mysqli_connect("localhost","root","","moviesite");
//        if(!$con){
//            die("Connection failed: " . mysqli_connect_error());
//        }
    // current code
        session_start();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        include("../database/DBconn.php");
        $con = getConnection();
        
        if (!isset($_SESSION['username'])) {
            echo "<div class='form'>
                    <h3>You need to login first.</h3>
                    <p class='link'>Click here to <a href='../auth/login.php'>Login</a></p>
                </div>";
        }
        else{  
            // get email and ID from database and show it
            $user = mysqli_real_escape_string($con, $_SESSION['username']);
        
            $query = "SELECT user_id, email FROM `dbProj_users` WHERE username='$user'";
            $result = mysqli_query($con, $query) or die(mysqli_error($con));
    
            if ($row = mysqli_fetch_assoc($result)) 
            {
                $email = $row['email'];
                $userid = $row['user_id'];}
?>
          <section class='profile'>
            <div class='detailsandlogout'>
              <img class="profileimg" src='../movies_images/profile.png' alt='profile' />
              <div class='profile-details'>
                <h3>Name:</h3><?php echo "<h4> {$_SESSION['username']}</h4>"; ?> 
                <br>
                <h3>Email:</h3> <?php echo " <h4> $email </h4> ";?> 
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

            if(isset($userid)){
               $query = "SELECT movie_id FROM `dbProj_movies` WHERE created_by = '$userid'";
               $result = mysqli_query($con, $query) or die(mysqli_error($con));
              while( $row = mysqli_fetch_assoc($result)):
                $movieid = $row['movie_id'];
                $url = "https://api.themoviedb.org/3/movie/$movieid?api_key=$api_key&language=en-US";
                $response = file_get_contents($url);
                $response = json_decode($response, true);
                
                if($response){
                ?>
                <a href="../movie/?id=<?php echo $response['id']; ?>" class="movie">
                        <img src="https://image.tmdb.org/t/p/w500<?php echo $response['poster_path']; ?>" class="image"/>
                        <div class="detbtn">
                            <div class="details">
                                <div class="nameofmovie">
                                    <h1><?php echo $response['title']; ?></h3>
                                </div>
                            </div>
                            <img src="../movies_images/remove.png" class="addbtn" data-id="<?php echo $response['id']; ?>"/>
                        </div>
                </a>
                <?php
                }
              endwhile;
            }
          ?>
          </div>
        </section>
        <?php
        }
        ?>
    <?php
    // previous code
         // mysqli_close($con);
        ?>
  </main>
  <footer>
    <p>&copy; 2023 MovieSite. All rights reserved.</p>
  </footer>
  <script>
    $(document).ready(function(){
      $(".addbtn").on("click",function(){
        event.preventDefault();
        let movieid = $(this).data("id");
        let userid= <?php echo $userid??0; ?>;
        $.ajax({
          url: "../scripts_php/remove.php",
          type: "POST",
          data: {
            movie_id: movieid,
            user_id: userid
          },
          success: function(data){
            location.reload();
          },
          error: function(){
            alert(response);
          }
        });
      });
    });
  </script>
</body>

</html>