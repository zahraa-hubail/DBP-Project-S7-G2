<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

// 1. Role Enforcement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Fetch User Data to Populate Form
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $query = "SELECT * FROM dbProj_users WHERE user_id = $user_id";
    $result = mysqli_query($con, $query);
    $user_data = mysqli_fetch_assoc($result);

    if (!$user_data) {
        header("Location: manage_users.php?error=not_found");
        exit();
    }
}

// 3. Handle Form Submission (Update Logic)
if (isset($_POST['update_user'])) {
    $uid = intval($_POST['user_id']);
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $role_id = intval($_POST['role_id']);
    $is_active = intval($_POST['is_active']);

    $update_query = "UPDATE dbProj_users SET 
                    username = '$username', 
                    email = '$email', 
                    role_id = $role_id, 
                    is_active = $is_active 
                    WHERE user_id = $uid";

    if (mysqli_query($con, $update_query)) {
        header("Location: manage_users.php?msg=updated");
    } else {
        $error_msg = "Database Error: Could not update user.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User | The Binge Box</title>
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
    <div class="profile">
        <h1>Edit User Account</h1>
        <p class="welcome-line">Modify permissions or account status for <strong><?php echo htmlspecialchars($user_data['username']); ?></strong>.</p>

        <?php if(isset($error_msg)): ?>
            <div class="alert error-alert"><strong>Error:</strong> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <section class="admin-section">
            <form action="edit_user.php" method="POST" class="report-form">
                <input type="hidden" name="user_id" value="<?php echo $user_data['user_id']; ?>">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>System Role</label>
                    <select name="role_id">
                        <option value="1" <?php if($user_data['role_id'] == 1) echo 'selected'; ?>>Administrator</option>
                        <option value="2" <?php if($user_data['role_id'] == 2) echo 'selected'; ?>>Creator</option>
                        <option value="3" <?php if($user_data['role_id'] == 3) echo 'selected'; ?>>Viewer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Account Status</label>
                    <select name="is_active">
                        <option value="1" <?php if($user_data['is_active'] == 1) echo 'selected'; ?>>Active</option>
                        <option value="0" <?php if($user_data['is_active'] == 0) echo 'selected'; ?>>Inactive / Banned</option>
                    </select>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="update_user" class="admin-btn">Save Changes</button>
                    <a href="manage_users.php" class="admin-btn" style="background: #6c757d;">Cancel</a>
                </div>
            </form>
        </section>
    </div>
</main>

<footer>
    <p>&copy; 2026 The Binge Box. Admin Portal.</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    }
});
</script>

</body>
</html>