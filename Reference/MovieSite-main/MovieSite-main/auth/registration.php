<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registration</title>
    <link rel="stylesheet" href="style_auth.css"/>
</head>
<body>
<header>
    <a href="../"><img class="logo" src="../logo.png" alt="Movies" /></a>
    <nav>
      <ul>
        <li class="dropdown">
            <a href="../search/">Search</a>
            <div class="dropdown-content">
                <a href="../search/category/">Search Category</a>
            </div>
        </li>
        <li><a href="../account/">Account</a></li>
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
 <?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

session_start();
$con = mysqli_connect("localhost","u202301089","asdASD123!","db202301089");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_SESSION['username'])) {
    header("Location: ../account/");
    exit();
}

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($con, stripslashes($_POST['username']));
    $email = mysqli_real_escape_string($con, stripslashes($_POST['email']));
    $password = mysqli_real_escape_string($con, stripslashes($_POST['password']));
    $token = bin2hex(random_bytes(50));

$query = "INSERT into `dbProj_users` (role_id, username, password_hash, email, is_active, token, created_at)
          VALUES (2, '$username', '" . md5($password) . "', '$email', 0, '$token', NOW())";
$result = mysqli_query($con, $query);

    if ($result) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'thebingeboxco@gmail.com'; 
            $mail->Password = 'eosj vasi hscl icka'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('thebingeboxco@gmail.com', 'TheBingeBox'); 
            $mail->addAddress($email, $username); 
            $mail->addReplyTo('thebingeboxco@gmail.com', 'TheBingeBox'); 

            $mail->isHTML(true);
            $mail->Subject = 'Email Verification';
            $mail->Body = "Click the following link to verify your email: <a href='http://20.74.143.233/~u202301660/MovieSite-main/MovieSite-main/auth/verified.php?token=$token'>Verify Email</a>";

            $mail->send();
            echo "<div class='form'>
                  <h3>You are registered successfully. Please check your email for verification.</h3><br/>
                  <p class='link'>Click here to <a href='login.php'>Login</a></p>
                  </div>";
        } catch (Exception $e) {
            echo "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "<div class='form'>
              <h3>Required fields are missing.</h3><br/>
              <p class='link'>Click here to <a href='registration.php'>registration</a> again.</p>
              </div>";
    }
} else {
?>
    <main>
    <form class="form" action="" method="post">
        <h1 class="login-title">Registration</h1>
        <input type="text" class="login-input" name="username" placeholder="Username" required />
        <input type="text" class="login-input" name="email" placeholder="Email Adress">
        <input type="password" class="login-input" name="password" placeholder="Password">
        <input type="submit" name="submit" value="Register" class="login-button">
        <p class="link"><a href="login.php">Click to Login</a></p>
    </form>
    </main>
<?php
    }
?>
    <footer>
        <p>&copy; 2023 MovieSite. All rights reserved.</p>
    </footer>
</body>
</html>