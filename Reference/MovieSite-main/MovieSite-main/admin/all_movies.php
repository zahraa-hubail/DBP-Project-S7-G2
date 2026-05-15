<!DOCTYPE html>

<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        session_start();
        include("../database/DBconn.php");
        $con = getConnection();

        // Security Check: Role Enforcement
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: ../auth/login.php");
            exit();
        }

        $query = "SELECT m.*, u.username FROM dbProj_movies m JOIN dbProj_users u ON m.created_by = u.user_id";
        $result = mysqli_query($con, $query);        
        ?>
    </body>
</html>
