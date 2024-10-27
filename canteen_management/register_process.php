<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Insert user data into the database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password); // No hashing for this project
    $stmt->bindParam(':role', $role);

    if ($stmt->execute()) {
        echo "Registration successful!";
        header("Location: index.php");
    } else {
        echo "Error registering user!";
    }
}
?>