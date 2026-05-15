<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

$start = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end = $_POST['end_date'] ?? date('Y-m-d');


$query = "CALL dbProj_GetPopularMovies('$start', '$end')";
$result = mysqli_query($con, $query) or die(mysqli_error($con));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - The Binge Box</title>
    <link rel="stylesheet" href="../account/account.css"> <link rel="stylesheet" href="admin.css"> </head>
<body>
    <header>
        <div class="logo"><a href="../"><img src="../logo.png" alt="Movies" /></a></div>
        <nav>
            <ul>
                <li><a href="../search/">Search</a></li>
                <li><a href="./dashboard.php">Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="profile">
            <h1>System Reporting Hub</h1>
            <p>Generating statistics from <strong><?php echo $start; ?></strong> to <strong><?php echo $end; ?></strong></p>
        </section>

        <section class="admin-section">
            <h2 style="color: #333 !important;">Filter Report by Date</h2>
            <form method="POST" action="reports.php" class="report-form">
                <input type="date" name="start_date" value="<?php echo $start; ?>" required>
                <input type="date" name="end_date" value="<?php echo $end; ?>" required>
                <button type="submit" class="admin-btn">Update Report</button>
            </form>
        </section>

        <section class="admin-section">
            <h2 style="color: #333 !important;">Most Popular Content (By Ratings)</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Movie ID</th>
                        <th>Title</th>
                        <th>Total Reviews</th>
                        <th>Avg Stars</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($result) > 0):
                        while($row = mysqli_fetch_assoc($result)): 
                    ?>
                    <tr>
                        <td><?php echo $row['movie_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo $row['total_reviews']; ?></td>
                        <td><?php echo number_format($row['average_stars'] ?? 0, 1); ?> ★</td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No data found for this date range.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 The Binge Box. Admin Portal.</p>
    </footer>
</body>
</html>