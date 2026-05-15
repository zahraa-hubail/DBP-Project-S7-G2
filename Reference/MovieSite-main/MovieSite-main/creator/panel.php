<!DOCTYPE html>

<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        session_start();
        // Ensure user is logged in AND is a creator
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'creator') {
            header("Location: ../auth/login.php?error=unauthorized");
            exit();
        }
        ?>
    </body>
</html>
