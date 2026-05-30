<?php
session_start();
$base_path = "../";

$con = mysqli_connect("localhost", "u202301089", "asdASD123!", "db202301089");
if (!$con) die("Connection failed: " . mysqli_connect_error());

if (isset($_SESSION['username'])) {
    header("Location: ../account/");
    exit();
}

if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    /* Server-side validation */
    if ($username === '' || $password === '') {
        $login_error = "Username and password are required.";
    } elseif (strlen($username) > 100 || strlen($password) > 100) {
        $login_error = "Input too long.";
    } else {
        /* Prepared statement — safe against SQL injection */
        $hash  = md5($password);
        $stmt  = $con->prepare("SELECT * FROM dbProj_users WHERE username = ? AND password_hash = ? AND is_active = 1");
        $stmt->bind_param("ss", $username, $hash);
        $stmt->execute();
        $result    = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        if ($user_data) {
            $_SESSION['username'] = $username;
            $_SESSION['id']       = $user_data['user_id'];

            if ($user_data['role_id'] == 1) {
                $_SESSION['role'] = 'admin';
                header("Location: ../admin/dashboard.php");
            } elseif ($user_data['role_id'] == 2) {
                $_SESSION['role'] = 'creator';
                header("Location: ../creator/index.php");
            } else {
                $_SESSION['role'] = 'viewer';
                header("Location: ../account/");
            }
            exit();
        }
        $login_error = "Login failed. Please check your username, password, or verify your email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — The Binge Box</title>
    <link rel="stylesheet" href="../shared.css" />
    <link rel="stylesheet" href="style_auth.css" />
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<main>
    <?php if (isset($login_error)): ?>
    <div class="form">
        <p style="color:#c0392b; font-weight:600;"><?= htmlspecialchars($login_error) ?></p>
        <p class="link">Click here to <a href="login.php">try again</a></p>
    </div>
    <?php else: ?>
    <form class="form" method="post" name="login" onsubmit="return validateLogin()">
        <h1 class="login-title">Login</h1>
        <input type="text"     class="login-input" id="loginUsername" name="username" placeholder="Username" autofocus />
        <input type="password" class="login-input" id="loginPassword" name="password" placeholder="Password" />
        <input type="submit"   class="login-button" name="submit" value="Login" />
        <p class="link"><a href="registration.php">New Registration</a></p>
    </form>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2026 The Binge Box. All rights reserved.</p>
</footer>

<script>
function validateLogin() {
    var u = document.getElementById('loginUsername').value.trim();
    var p = document.getElementById('loginPassword').value;
    var errors = [];

    if (u === '')       errors.push('Username is required.');
    if (u.length > 100) errors.push('Username is too long.');
    if (p === '')       errors.push('Password is required.');

    if (errors.length > 0) {
        alert('Please fix the following:\n\n' + errors.join('\n'));
        return false;
    }
    return true;
}
</script>
</body>
</html>
