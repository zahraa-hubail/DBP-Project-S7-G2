<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$con = mysqli_connect('localhost', 'u202301089', 'asdASD123!', 'db202301089');

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($con, $_GET['token']);
    $query = "UPDATE `dbProj_users` SET is_active = '1' WHERE token = '$token'";
    $result = mysqli_query($con, $query);

    if (mysqli_affected_rows($con) > 0) {
        echo "<div class='form'>
              <h3>Email verification successful. You can now <a href='login.php'>login</a>.</h3>
              </div>";
    } else {
        echo "<div class='form'>
              <h3>Email verification failed. Please contact support.</h3>
              </div>";
    }
} else {
    echo "<div class='form'>
          <h3>Invalid verification link.</h3>
          </div>";
}
?>