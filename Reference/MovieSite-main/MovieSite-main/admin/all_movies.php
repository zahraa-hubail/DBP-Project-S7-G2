<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

// 1. Security Check: Role Enforcement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Fetch Movies with Creator Names
$query = "SELECT m.*, u.username FROM dbProj_movies m JOIN dbProj_users u ON m.created_by = u.user_id";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Manage Movies | The Binge Box</title>
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

        <header>
            <div class="logo">
                <img src="../logo.png" alt="Binge Box Logo">
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="../auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="profile">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert success-alert">
                        <strong>Success!</strong> 
                        <?php
                        if ($_GET['msg'] == 'user_deleted') {
                            echo "The user account has been permanently removed.";
                        }
                        if ($_GET['msg'] == 'movie_deleted') {
                            echo "The movie listing has been deleted from the database.";
                        }
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
                <h1>Manage Movie Database</h1>
                <p class="welcome-line">Edit or remove movie listings from the system.</p>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Genre</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
// 3. The Loop: This converts database rows into HTML table rows
if (mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)):
        ?>
                                    <tr>
                                        <td><?php echo $row['movie_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['genre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td class="action-buttons">
                                            <a href="edit_movie.php?id=<?php echo $row['movie_id']; ?>" class="btn-style edit-btn">Edit</a>

                                            <button class="btn-style delete-btn" onclick="confirmDelete(<?php echo $row['movie_id']; ?>)">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
        <?php
    endwhile;
else:
    ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No movies found in the database.</td>
                                </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2026 The Binge Box. All rights reserved.</p>
        </footer>

        <script>
        // JavaScript confirmation to prevent accidental deletion
            function confirmDelete(movieId) {
                if (confirm("Are you sure you want to delete this movie? This action cannot be undone.")) {
                    window.location.href = "delete_movie.php?id=" + movieId;
                }
            }
        </script>

    </body>
</html>