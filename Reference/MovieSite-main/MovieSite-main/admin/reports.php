<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

// 1. Role Enforcement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Data Handling - Report 1
$start = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end = $_POST['end_date'] ?? date('Y-m-d');
$query_popular = "CALL dbProj_GetPopularMovies('$start', '$end')";
$res_popular = mysqli_query($con, $query_popular);
mysqli_next_result($con); 

// Data Handling - Report 2
$creator_id = $_POST['creator_id'] ?? '';
$res_creator = null;
if (!empty($creator_id)) {
    $creator_id_clean = mysqli_real_escape_string($con, $creator_id);
    $query_creator = "SELECT m.*, u.username FROM dbProj_movies m JOIN dbProj_users u ON m.created_by = u.user_id WHERE m.created_by = '$creator_id_clean'";
    $res_creator = mysqli_query($con, $query_creator);
}

$creators = mysqli_query($con, "SELECT user_id, username FROM dbProj_users WHERE role_id = 2");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports | The Binge Box</title>
    <link rel="stylesheet" href="../account/account.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">

<header class="no-print">
    <div class="logo"><img src="../logo.png" alt="Binge Box"></div>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <div class="profile no-print">
        <h1>System Reporting Hub</h1>
        <p>Analyze platform performance and content creation.</p>
    </div>

    <div class="report-grid">
        
        <div class="report-card" id="popular-report">
            <h2>Most Popular Content</h2>
            <form method="POST" class="no-print">
                <div class="form-group">
                    <label>Filter Date Range:</label>
                    <input type="date" name="start_date" value="<?php echo $start; ?>">
                    <input type="date" name="end_date" value="<?php echo $end; ?>">
                </div>
                <button type="submit" class="admin-btn">Update</button>
                <button type="button" class="admin-btn" style="background:#28a745;" onclick="printDiv('popular-report')">Save PDF</button>
            </form>

            <table class="report-table">
                <thead>
                    <tr><th>Title</th><th>Reviews</th><th>Rating</th></tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($res_popular)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo $row['total_reviews']; ?></td>
                        <td><?php echo number_format($row['average_stars'], 1); ?> ★</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="report-card" id="creator-report">
            <h2>Content by Creator</h2>
            <form method="POST" class="no-print">
                <div class="form-group">
                    <label>Select Creator:</label>
                    <select name="creator_id">
                        <option value="">-- Choose User --</option>
                        <?php while($c = mysqli_fetch_assoc($creators)): ?>
                            <option value="<?php echo $c['user_id']; ?>" <?php echo ($creator_id == $c['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="admin-btn">Generate</button>
                <button type="button" class="admin-btn" style="background:#28a745;" onclick="printDiv('creator-report')">Save PDF</button>
            </form>

            <table class="report-table">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Genre</th></tr>
                </thead>
                <tbody>
                    <?php if($res_creator && mysqli_num_rows($res_creator) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_creator)): ?>
                        <tr>
                            <td><?php echo $row['movie_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['genre']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">No data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<script>
function printDiv(divId) {
    const originalContent = document.body.innerHTML;
    const printContent = document.getElementById(divId).innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 40px; font-family: sans-serif;">
            <h1 style="color: #152232; border-bottom: 2px solid #00adff; padding-bottom: 10px;">The Binge Box - Official System Report</h1>
            <p style="color: #666;">Generated on: ${new Date().toLocaleDateString()}</p>
            ${printContent}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload(); 
}
</script>

</body>
</html>