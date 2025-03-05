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
    <h3 class="text-primary">Product</h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMaterialModal"><i class="bi bi-wrench-adjustable-circle-fill"></i>&nbsp;Create Product</button>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <table id="materialsTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="materialsList">
                <?php while ($row = $material_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['material_name']; ?></td>
                        <td><?php echo $row['supplier_name']; ?></td>
                        <td><?php echo $row['items']; ?></td>
                        <td>
                            <button class="btn btn-success btn-sm edit_material-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-material="<?php echo $row['material_name']; ?>" 
                                data-supplier="<?php echo $row['supplier_name']; ?>" 
                                data-items="<?php echo $row['items']; ?>">
                                    <i class="bi bi-pencil-square"></i>&nbsp;Edit
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                <input type="hidden" name="delete_material" value="1">
                                <input type="hidden" name="material_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash3"></i>&nbsp;Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
