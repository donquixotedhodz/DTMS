<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function log_action($action, $details = '') {
    require 'config.php';
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $details = $conn->real_escape_string($details);
    
    $sql = "INSERT INTO audit_log (user_id, action, details, ip_address) 
            VALUES ('$user_id', '$action', '$details', '$ip_address')";
    
    $conn->query($sql);
    $conn->close();
}
?>