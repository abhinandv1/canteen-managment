<?php
session_start();
require 'db.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if POST request is made
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if food_id and quantity are set
    if (isset($_POST['food_id']) && isset($_POST['quantity'])) {
        $food_id = $_POST['food_id'];
        $quantity = $_POST['quantity'];

        // Fetch food item price
        $food_stmt = $conn->prepare("SELECT price FROM food_items WHERE food_id = :food_id");
        $food_stmt->bindParam(':food_id', $food_id);
        $food_stmt->execute();
        $food_item = $food_stmt->fetch();

        // Check if the food item exists
        if ($food_item) {
            $total_cost = $food_item['price'] * $quantity;

            // Insert the order into the database
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, food_id, quantity, total_cost, payment_status) VALUES (:user_id, :food_id, :quantity, :total_cost, 'unpaid')");
            $order_stmt->bindParam(':user_id', $user_id);
            $order_stmt->bindParam(':food_id', $food_id);
            $order_stmt->bindParam(':quantity', $quantity);
            $order_stmt->bindParam(':total_cost', $total_cost);

            if ($order_stmt->execute()) {
                // Redirect to dashboard.php with a success message
                header("Location: dashboard.php?order=success");
                exit();
            } else {
                echo "Failed to place the order. Please try again.";
            }
        } else {
            echo "Food item not found.";
        }
    } else {
        echo "Invalid input. Please check the form.";
    }
} else {
    echo "Invalid request method.";
}
?>