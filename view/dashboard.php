<?php
session_start();

// Go back to Login if User not Login, Obviously
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION['full_name']; // Get the user's full name
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management</title>

    <!-- Aesthetics -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>
<body>

<!-- Sidebar -->
<nav class="sidebar d-flex flex-column">
    <a href="dashboard.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-bookmark-dash-fill"></i><br>Dashboard</a>
    <a href="inventory.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-bag-check-fill"></i><br>Inventory</a>
    <a href="product.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-hammer"></i><br>Product</a>
    <a href="materials.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-nut-fill"></i><br>Materials</a>
    <a href="orders.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-basket-fill"></i><br>Orders</a>
    <a href="sales.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-receipt"></i><br>Sales</a>
    <button class="logout-btn"><a href="../Handler/logout_handler.php" style="color: white; text-decoration: none;"><i class="bi bi-box-arrow-left"></i>&nbsp;LogOut</a></button>
</nav>

<!-- Main -->
<div class="content">
    <h3 class="text-primary">Dashboard - <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h3>
</div>

<!-- Script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>
</html>
