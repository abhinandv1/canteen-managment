<?php
session_start();
require 'db.php';

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page
    exit();
}

// Check if user is logged in and is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user name
$stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$username = $user['username'];

// Create Order
if (isset($_POST['add_order'])) {
    $food_id = $_POST['food_id'];
    $quantity = $_POST['quantity'];

    // Get food price
    $stmt = $conn->prepare("SELECT price FROM food_items WHERE food_id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch();
    $total_cost = $food['price'] * $quantity;

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, food_id, quantity, total_cost, payment_status) VALUES (?, ?, ?, ?, 'unpaid')");
    $stmt->execute([$user_id, $food_id, $quantity, $total_cost]);
    echo "<script>alert('Order placed successfully');</script>";
}

// Mark Order as Paid
if (isset($_POST['pay_now'])) {
    $order_id = $_POST['order_id'];

    // Update payment status to paid
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    echo "<script>alert('Payment status updated to paid');</script>";
}

// Fetch orders for the logged-in user
$stmt = $conn->prepare("SELECT o.order_id, f.name AS food_name, o.quantity, o.total_cost, o.payment_status
                        FROM orders o
                        JOIN food_items f ON o.food_id = f.food_id
                        WHERE o.user_id = ?");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Fetch all food items for the menu
$stmt = $conn->query("SELECT * FROM food_items");
$food_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f5f5f5;
        }

        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }

        .table {
            background-color: #ffffff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .food-item {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            margin: 10px;
            background-color: #fff;
        }

        .food-image {
            width: 100%;
            height: auto;
            max-height: 150px;
            /* Set a max height */
            object-fit: cover;
            /* Cover the container without distortion */
            border-radius: 10px;
        }

        .form-control,
        .btn-primary {
            border-radius: 20px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2 class="mt-4 mb-3"><?= htmlspecialchars($username) ?>'s Dashboard</h2>

        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Dashboard</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active">
                        <a class="nav-link" href="#createOrder" data-toggle="tab"><i class="fas fa-shopping-cart"></i>
                            Create Order</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#viewOrders" data-toggle="tab"><i class="fas fa-receipt"></i> View
                            Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#viewMenu" data-toggle="tab"><i class="fas fa-utensils"></i> View
                            Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="tab-content">
            <!-- Create Order Section -->
            <div class="tab-pane fade show active" id="createOrder">
                <h4>Create Order</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="food_id">Select Food Item:</label>
                        <select id="food_id" name="food_id" class="form-control" required>
                            <?php
                            $stmt = $conn->query("SELECT * FROM food_items");
                            while ($food = $stmt->fetch()) {
                                echo "<option value='{$food['food_id']}'>{$food['name']} - Rs {$food['price']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <button type="submit" name="add_order" class="btn btn-primary">Create Order</button>
                </form>
            </div>

            <!-- View Orders Section -->
            <div class="tab-pane fade" id="viewOrders">
                <h4>Your Orders</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>S.No</th>
                        <th>Food Item</th>
                        <th>Quantity</th>
                        <th>Total Cost</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $sno = 1; // Initialize serial number
                    foreach ($orders as $order) {
                        echo "<tr>
                            <td>{$sno}</td>
                            <td>{$order['food_name']}</td>
                            <td>{$order['quantity']}</td>
                            <td>Rs {$order['total_cost']}</td>
                            <td>{$order['payment_status']}</td>
                            <td>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='order_id' value='{$order['order_id']}'>
                                    <button type='submit' name='pay_now' class='btn btn-success' " . ($order['payment_status'] == 'paid' ? 'disabled' : '') . ">Pay Now</button>
                                </form>
                            </td>
                          </tr>";
                        $sno++; // Increment serial number
                    }
                    ?>
                </table>
            </div>

            <!-- View Menu Section -->
            <div class="tab-pane fade" id="viewMenu">
                <h4>Menu</h4>
                <div class="row">
                    <?php foreach ($food_items as $food_item): ?>
                        <div class="col-md-4">
                            <div class="food-item">
                                <img src="images/<?= htmlspecialchars($food_item['image']) ?>"
                                    alt="<?= htmlspecialchars($food_item['name']) ?>" class="food-image">
                                <h5><?= htmlspecialchars($food_item['name']) ?></h5>
                                <p>Price: Rs <?= htmlspecialchars($food_item['price']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>