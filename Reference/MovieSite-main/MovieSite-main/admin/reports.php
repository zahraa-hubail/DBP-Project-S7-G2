<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

// 1. Role Enforcement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Report 1 — validate date format to prevent injection before stored proc call
$start = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end   = $_POST['end_date']   ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = date('Y-m-d');

$res_popular = $con->query("CALL dbProj_GetPopularMovies('$start', '$end')");
$con->next_result();

// Report 2 — prepared statement, genre names via junction table
$creator_id  = intval($_POST['creator_id'] ?? 0);
$res_creator = null;
if ($creator_id > 0) {
    $stmt_c = $con->prepare("
        SELECT m.movie_id, m.title, m.status,
               GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ') AS genre_names
        FROM dbProj_movies m
        LEFT JOIN dbProj_movie_genres mg ON m.movie_id  = mg.movie_id
        LEFT JOIN dbProj_genres g        ON mg.genre_id = g.genre_id
        WHERE m.created_by = ?
        GROUP BY m.movie_id, m.title, m.status
        ORDER BY m.created_at DESC
    ");
    $stmt_c->bind_param("i", $creator_id);
    $stmt_c->execute();
    $res_creator = $stmt_c->get_result();
}

$creators = mysqli_query($con, "SELECT user_id, username FROM dbProj_users WHERE role_id = 2");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports | The Binge Box</title>
    <link rel="stylesheet" href="../shared.css">
    <link rel="stylesheet" href="../account/account.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">

<?php $base_path = "../"; include "../includes/navbar.php"; ?>

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
                    <tr><th>ID</th><th>Title</th><th>Genre(s)</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if($res_creator && $res_creator->num_rows > 0): ?>
                        <?php while($row = $res_creator->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['movie_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['genre_names'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($row['status'] ?? '')); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No data available.</td></tr>
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