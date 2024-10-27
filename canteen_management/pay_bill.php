<?php
session_start();
require 'db.php';

if (isset($_GET['bill_id']) && $_SESSION['user_id']) {
    $bill_id = $_GET['bill_id'];

    // Update the bill to set it as paid
    $stmt = $conn->prepare("UPDATE bills SET status = 'paid', payment_date = NOW() WHERE bill_id = :bill_id");
    $stmt->bindParam(':bill_id', $bill_id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=Payment successful!");
    } else {
        header("Location: dashboard.php?msg=Payment failed!");
    }
} else {
    header("Location: dashboard.php");
}
?>