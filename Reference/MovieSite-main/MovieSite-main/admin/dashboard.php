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
        <link rel="stylesheet" href="../shared.css">
        <link rel="stylesheet" href="../account/account.css">
        <link rel="stylesheet" href="admin.css">
    </head>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // 1. Find the alert element
            const alert = document.querySelector('.alert');

            if (alert) {
                // 2. Hide the alert after 4 seconds (4000ms)
                setTimeout(() => {
                    alert.style.transition = "opacity 0.5s ease";
                    alert.style.opacity = "0";

                    // Remove from DOM after fade out
                    setTimeout(() => alert.remove(), 500);
                }, 4000);

                // 3. Clean the URL (Removes ?msg=... or ?error=... from the address bar)
                // This prevents the message from reappearing on refresh
                if (window.history.replaceState) {
                    const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path: url}, '', url);
                }
            }
        });
    </script>
    <body class="admin-page">
        <?php $base_path = "../"; include "../includes/navbar.php"; ?>

        <main>
            <section class="profile">
                <h1>Administrator Control Panel</h1>
                <p>Welcome, <strong><?php echo $_SESSION['username']; ?></strong> (<?php echo $admin_data['email']; ?>)</p>
            </section>

            <hr>
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert success-alert">
                    <?php
                    if ($_GET['msg'] == 'user_deleted')
                        echo "The user account has been permanently removed.";
                    if ($_GET['msg'] == 'movie_deleted')
                        echo "The movie listing has been deleted from the database.";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error-alert">
                    <strong>Error:</strong> 
                    <?php
                    if ($_GET['error'] == 'self_delete') {
                        echo "Security violation: You cannot delete your own admin account.";
                    }
                    if ($_GET['error'] == 'delete_failed') {
                        echo "Database error: Could not remove record. Check foreign key constraints.";
                    }
                    if ($_GET['error'] == 'unauthorized') {
                        echo "Access denied: You do not have permission to perform this action.";
                    }
                    ?>
                </div>
<?php endif; ?>
            <section class="admin-section">
                <h2>System Reports</h2>
                <br>
                <div class="report-buttons">
                    <a href="reports.php" class="admin-btn">Generate Reports</a>
                    <a href="manage_users.php" class="admin-btn">View All Users</a>
                </div>
            </section>

            <hr>

            <section class="admin-section">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Manage Site Content</h2>
                    <a href="all_movies.php" class="admin-btn" style="background-color: #2c3e50;">View & Edit All Movies</a>
                </div>
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
                        $content_query = "SELECT m.movie_id, m.title, u.username 
                                          FROM `dbProj_movies` m 
                                          JOIN `dbProj_users` u ON m.created_by = u.user_id 
                                          ORDER BY m.created_at DESC";
                        $content_res = mysqli_query($con, $content_query);
                        while ($movie = mysqli_fetch_assoc($content_res)):
                            ?>
                            <tr>
                                <td><?php echo $movie['movie_id']; ?></td>
                                <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                <td><?php echo htmlspecialchars($movie['username']); ?></td>
                                <td class="action-buttons">
                                    <a href="edit_movie.php?id=<?php echo $movie['movie_id']; ?>" class="btn-style edit-btn">Edit</a>
                                    <button class="btn-style delete-btn" onclick="confirmDelete(<?php echo $movie['movie_id']; ?>, 'dashboard')">Delete</button>
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
            function confirmDelete(id, source) {
                if (confirm("Are you sure you want to remove this content? This action cannot be undone.")) {
                    window.location.href = "delete_movie.php?id=" + id + "&from=" + source;
                }
            }
        </script>
    </body>
</html>