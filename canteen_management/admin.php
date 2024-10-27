<?php
session_start();
require 'db.php';

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Add Food Item
if (isset($_POST['add_food'])) {
    $food_name = $_POST['food_name'];
    $price = $_POST['price'];
    $image = $_FILES['food_image']['name'];
    $target_dir = "images/";
    $target_file = $target_dir . basename($image);

    if (move_uploaded_file($_FILES['food_image']['tmp_name'], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO food_items (name, price, image) VALUES (?, ?, ?)");
        $stmt->execute([$food_name, $price, $image]);
        echo "<script>alert('Food item added successfully');</script>";
    } else {
        echo "<script>alert('Failed to upload image');</script>";
    }
}

// Delete User
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo "<script>alert('User deleted successfully');</script>";
}

// Change User Role
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->execute([$new_role, $user_id]);
    echo "<script>alert('User role updated successfully');</script>";
}

// Add New User
if (isset($_POST['add_user'])) {
    $username = $_POST['new_username'];
    $email = $_POST['new_email'];
    $role = $_POST['new_role'];
    $stmt = $conn->prepare("INSERT INTO users (username, email, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $role]);
    echo "<script>alert('User added successfully');</script>";
}

// Delete Food Item
if (isset($_GET['delete_food'])) {
    $food_id = $_GET['delete_food'];
    $stmt = $conn->prepare("DELETE FROM food_items WHERE food_id = ?");
    $stmt->execute([$food_id]);
    echo "<script>alert('Food item deleted successfully');</script>";
}

// Add Order
if (isset($_POST['add_order'])) {
    $user_id = $_POST['user_id'];
    $food_id = $_POST['food_id'];
    $quantity = $_POST['quantity'];

    // Get food price
    $stmt = $conn->prepare("SELECT price FROM food_items WHERE food_id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch();
    $total_cost = $food['price'] * $quantity;

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, food_id, quantity, total_cost, payment_status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->execute([$user_id, $food_id, $quantity, $total_cost]);
    echo "<script>alert('Order added successfully');</script>";
}

// Update Payment Status
if (isset($_POST['update_payment_status'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    echo "<script>alert('Order marked as paid successfully');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .tab-content h4 {
            font-weight: bold;
            color: #007bff;
            margin-top: 20px;
        }

        .table {
            background-color: #ffffff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .btn-primary {
            border-radius: 20px;
        }

        .food-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }

        .navbar-nav .nav-item .nav-link {
            font-weight: 500;
            color: #555;
        }

        .navbar-nav .nav-item .nav-link.active {
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2 class="mt-4 mb-3">Admin Dashboard</h2>

        <!-- Bootstrap Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active">
                        <a class="nav-link" href="#manageUsers" data-toggle="tab"><i class="fas fa-users"></i> Manage
                            Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#manageFood" data-toggle="tab"><i class="fas fa-utensils"></i> Manage
                            Food Items</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#createOrder" data-toggle="tab"><i class="fas fa-shopping-cart"></i>
                            Create Order</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#viewOrders" data-toggle="tab"><i class="fas fa-receipt"></i> View
                            Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="tab-content">
            <!-- Manage Users Section -->
            <div class="tab-pane fade show active" id="manageUsers">
                <h4>Manage Users</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $stmt = $conn->query("SELECT * FROM users");
                    while ($user = $stmt->fetch()) {
                        echo "<tr>
                            <td>{$user['username']}</td>
                            <td>{$user['email']}</td>
                            <td>{$user['role']}</td>
                            <td>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='user_id' value='{$user['user_id']}'>
                                    <select name='new_role' class='form-control' onchange='this.form.submit()'>
                                        <option value='student' " . ($user['role'] == 'student' ? 'selected' : '') . ">Student</option>
                                        <option value='staff' " . ($user['role'] == 'staff' ? 'selected' : '') . ">Staff</option>
                                        <option value='admin' " . ($user['role'] == 'admin' ? 'selected' : '') . ">Admin</option>
                                    </select>
                                    <button type='submit' name='change_role' class='btn btn-warning btn-sm'>Change Role</button>
                                </form>
                                <a href='admin.php?delete_user={$user['user_id']}' class='btn btn-danger btn-sm'>Delete</a>
                            </td>
                          </tr>";
                    }
                    ?>
                </table>

                <h4>Add New User</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="new_username">Username:</label>
                        <input type="text" class="form-control" id="new_username" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label for="new_email">Email:</label>
                        <input type="email" class="form-control" id="new_email" name="new_email" required>
                    </div>
                    <div class="form-group">
                        <label for="new_role">Role:</label>
                        <select class="form-control" id="new_role" name="new_role" required>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </form>
            </div>

            <!-- Manage Food Items Section -->
            <div class="tab-pane fade" id="manageFood">
                <h4>Manage Food Items</h4>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="food_name">Food Name:</label>
                        <input type="text" class="form-control" id="food_name" name="food_name" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price:</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="food_image">Image:</label>
                        <input type="file" class="form-control" id="food_image" name="food_image" accept="image/*"
                            required>
                    </div>
                    <button type="submit" name="add_food" class="btn btn-primary">Add Food Item</button>
                </form>

                <table class="table table-bordered mt-3">
                    <tr>
                        <th>Food Name</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $stmt = $conn->query("SELECT * FROM food_items");
                    while ($food = $stmt->fetch()) {
                        echo "<tr>
                            <td>{$food['name']}</td>
                            <td>Rs {$food['price']}</td>
                            <td><img src='images/{$food['image']}' class='food-image' alt='Food Image'></td>
                            <td><a href='admin.php?delete_food={$food['food_id']}' class='btn btn-danger btn-sm'>Delete</a></td>
                          </tr>";
                    }
                    ?>
                </table>
            </div>

            <!-- Create Order Section -->
            <div class="tab-pane fade" id="createOrder">
                <h4>Create Order</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="user_id">Select User:</label>
                        <select id="user_id" name="user_id" class="form-control" required>
                            <?php
                            $stmt = $conn->query("SELECT * FROM users WHERE role != 'admin'");
                            while ($user = $stmt->fetch()) {
                                echo "<option value='{$user['user_id']}'>{$user['username']} ({$user['role']})</option>";
                            }
                            ?>
                        </select>
                    </div>
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
                <h4>View Orders</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Food Item</th>
                        <th>Quantity</th>
                        <th>Total Cost</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    // Fetch orders along with payment status
                    $stmt = $conn->query("SELECT o.order_id, u.username, f.name AS food_name, o.quantity, o.total_cost, o.payment_status
                                      FROM orders o
                                      JOIN users u ON o.user_id = u.user_id
                                      JOIN food_items f ON o.food_id = f.food_id");
                    while ($order = $stmt->fetch()) {
                        echo "<tr>
                            <td>{$order['order_id']}</td>
                            <td>{$order['username']}</td>
                            <td>{$order['food_name']}</td>
                            <td>{$order['quantity']}</td>
                            <td>Rs {$order['total_cost']}</td>
                            <td>{$order['payment_status']}</td>
                            <td>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='order_id' value='{$order['order_id']}'>
                                    <input type='hidden' name='current_status' value='{$order['payment_status']}'>
                                    <button type='submit' name='update_payment_status' class='btn btn-success btn-sm' " . ($order['payment_status'] == 'Paid' ? 'disabled' : '') . ">
                                        Mark as Paid
                                    </button>
                                </form>
                            </td>
                          </tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>

</body>

</html>