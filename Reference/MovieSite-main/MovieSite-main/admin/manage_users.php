<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
$query = "SELECT u.user_id, u.username, u.email, u.is_active, r.role_name 
          FROM dbProj_users u 
          JOIN dbProj_roles r ON u.role_id = r.role_id";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | The Binge Box</title>
    <link rel="stylesheet" href="../account/account.css"> 
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="../logo.png" alt="Binge Box Logo">
    </div>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="reports.php">System Reports</a></li>
            <li><a href="../scripts_php/logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <div class="profile">
        <h1>Manage Registered Users</h1>
        <p class="welcome-line">View and manage all accounts in the system.</p>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user_row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $user_row['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user_row['username']); ?></td>
                        <td><?php echo htmlspecialchars($user_row['email']); ?></td>
                        <td><?php echo $user_row['role_name']; ?></td>
                        <td>
                            <span class="status-badge <?php echo ($user_row['is_active']) ? 'active' : 'inactive'; ?>">
                                <?php echo ($user_row['is_active']) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="edit_user.php?id=<?php echo $user_row['user_id']; ?>" class="btn-style edit-btn">Edit</a>
                            
                            <button class="btn-style delete-btn" onclick="confirmDelete(<?php echo $user_row['user_id']; ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2026 The Binge Box. All rights reserved.</p>
</footer>

<script>
/**
 * Triggers a browser confirmation alert before deletion.
 * Essential evidence for the Test Plan to show the system prevents accidental removals.
 */
function confirmDelete(userId) {
    if (confirm("Are you sure you want to permanently delete user ID: " + userId + "? This action cannot be undone.")) {
        window.location.href = "delete_user.php?id=" + userId;
    }
}
</script>

</body>
</html>