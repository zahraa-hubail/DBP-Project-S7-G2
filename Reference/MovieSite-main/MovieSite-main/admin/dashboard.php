<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}


$user = mysqli_real_escape_string($con, $_SESSION['username']);
$query = "SELECT email FROM `dbProj_users` WHERE username='$user'";
$result = mysqli_query($con, $query);
$admin_data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - The Binge Box</title>
    <link rel="stylesheet" href="../account/account.css"> <link rel="stylesheet" href="admin.css"> </head>
<body>
    <header>
        <div class="logo"><a href="../"><img src="../logo.png" alt="Movies" /></a></div>
        <nav>
            <ul>
                <li><a href="../search/">Search</a></li>
                <li><a href="./dashboard.php">Admin Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="profile">
            <h1>Administrator Control Panel</h1>
            <p>Welcome, <strong><?php echo $_SESSION['username']; ?></strong> (<?php echo $admin_data['email']; ?>)</p>
        </section>

        <hr>

        <section class="admin-section">
            <h2>System Reports</h2>
            <br>
            <div class="report-buttons">
                <a href="reports.php?type=popular" class="admin-btn">Generate Most Popular Content Report</a>
                <a href="reports.php?type=users" class="admin-btn">View All Users</a>
            </div>
        </section>

        <hr>

        <section class="admin-section">
            <h2>Manage Site Content</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Movie ID</th>
                        <th>Title</th>
                        <th>Creator</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetching all content from dbProj_ movies to allow admin management
                    $content_query = "SELECT * FROM `dbProj_movies` ORDER BY created_at DESC";
                    $content_res = mysqli_query($con, $content_query);
                    while($movie = mysqli_fetch_assoc($content_res)):
                    ?>
                    <tr>
                        <td><?php echo $movie['movie_id']; ?></td>
                        <td>Movie Title Placeholder</td> <td>User #<?php echo $movie['created_by']; ?></td>
                        <td>
                            <button class="remove-btn" onclick="deleteContent(<?php echo $movie['movie_id']; ?>)">Remove</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 The Binge Box. Admin Portal.</p>
    </footer>

    <script>
        function deleteContent(id) {
            if(confirm("Are you sure you want to remove this content? A system message will be shown to the creator.")) {
                // AJAX call to a script like ../scripts_php/admin_remove.php
            }
        }
    </script>
</body>
</html>