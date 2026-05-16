<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

// 1. Security Check: Role Enforcement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Process Delete
if (isset($_GET['id'])) {
    $user_to_delete = intval($_GET['id']);
    
    // FETCH THE CURRENT ADMIN ID FROM SESSION
    // Your login code uses $_SESSION['id'], so we use that here.
    $logged_in_admin = $_SESSION['id'];

    // 3. THE SAFETY CHECK
    if ($user_to_delete === intval($logged_in_admin)) {
        // Redirect with the specific error code
        header("Location: manage_users.php?error=self_delete");
        exit();
    }

    // 4. Execution (Fixed Prefix Rule Applied)
    $query = "DELETE FROM dbProj_users WHERE user_id = $user_to_delete";
    
    if (mysqli_query($con, $query)) {
        header("Location: manage_users.php?msg=user_deleted");
    } else {
        header("Location: manage_users.php?error=delete_failed");
    }
} else {
    header("Location: manage_users.php");
}
?>