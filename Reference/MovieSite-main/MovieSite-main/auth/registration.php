<?php
session_start();
$base_path = "../";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration — The Binge Box</title>
  <link rel="stylesheet" href="../shared.css" />
  <link rel="stylesheet" href="style_auth.css" />
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php

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
    <form class="form" action="" method="post" onsubmit="return validateRegistration()">
        <h1 class="login-title">Registration</h1>
        <input type="text"     class="login-input" id="regUsername" name="username" placeholder="Username (3–50 chars)" required />
        <input type="email"    class="login-input" id="regEmail"    name="email"    placeholder="Email Address" required />
        <input type="password" class="login-input" id="regPassword" name="password" placeholder="Password (min 6 chars)" required />
        <input type="password" class="login-input" id="regConfirm"  name="confirm"  placeholder="Confirm Password" required />
        <input type="submit" name="submit" value="Register" class="login-button">
        <p class="link"><a href="login.php">Already have an account? Login</a></p>
    </form>
    </main>
<?php
    }
?>
    <footer>
        <p>&copy; 2026 The Binge Box. All rights reserved.</p>
    </footer>

<script>
function validateRegistration() {
    var u  = document.getElementById('regUsername').value.trim();
    var e  = document.getElementById('regEmail').value.trim();
    var p  = document.getElementById('regPassword').value;
    var c  = document.getElementById('regConfirm').value;
    var errors = [];

    if (u.length < 3)   errors.push('Username must be at least 3 characters.');
    if (u.length > 50)  errors.push('Username must be under 50 characters.');
    if (!/^[a-zA-Z0-9_]+$/.test(u)) errors.push('Username can only contain letters, numbers and underscores.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)) errors.push('Please enter a valid email address.');
    if (p.length < 6)   errors.push('Password must be at least 6 characters.');
    if (p !== c)        errors.push('Passwords do not match.');

    if (errors.length > 0) {
        alert('Please fix the following:\n\n' + errors.join('\n'));
        return false;
    }
    return true;
}
</script>
</body>
</html>