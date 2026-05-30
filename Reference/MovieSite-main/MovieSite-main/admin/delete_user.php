<?php
session_start();
include("../database/DBconn.php");
$con = getConnection();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_to_delete   = intval($_GET['id']);
$logged_in_admin  = intval($_SESSION['id']);

if ($user_to_delete === $logged_in_admin) {
    header("Location: manage_users.php?error=self_delete");
    exit();
}

$stmt = $con->prepare("DELETE FROM dbProj_users WHERE user_id = ?");
$stmt->bind_param("i", $user_to_delete);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: manage_users.php?msg=user_deleted");
} else {
    header("Location: manage_users.php?error=delete_failed");
}
exit();
