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
    <button class="logout-btn"><a href="login.php" style="color: white; text-decoration: none;"><i class="bi bi-box-arrow-left"></i>&nbsp;LogOut</a></button>
</nav>

<!-- Main -->
<div class="content">
    <h3 class="text-primary">Product</h3>

    <!-- Controls -->
    <div class="d-flex justify-content-between mb-3">
        <input type="text" class="form-control w-25" placeholder="🔍 Quick search">
        <div>
            <button class="btn btn-primary">+ Create Stock</button>
            <button class="btn btn-outline-secondary">Sort ▼</button>
        </div>
    </div>

    <!--Table -->
    <div class="table-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="checkbox"></td>
                    <td>#0001</td>
                    <td>Shovel</td>
                    <td>Digging Tools</td>
                    <td>100</td>
                    <td>
                        <button class="btn btn-success"><i class="bi bi-pencil-square"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger"><i class="bi bi-trash3"></i>&nbsp;Delete</button>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox"></td>
                    <td>#0002</td>
                    <td>Clamp</td>
                    <td>Gripping Tools</td>
                    <td>100</td>
                    <td>
                        <button class="btn btn-success"><i class="bi bi-pencil-square"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger"><i class="bi bi-trash3"></i>&nbsp;Delete</button>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox"></td>
                    <td>#0003</td>
                    <td>Hammer</td>
                    <td>Striking Tools</td>
                    <td>100</td>
                    <td>
                        <button class="btn btn-success"><i class="bi bi-pencil-square"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger"><i class="bi bi-trash3"></i>&nbsp;Delete</button>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox"></td>
                    <td>#0004</td>
                    <td>Knife</td>
                    <td>Cutting Tools</td>
                    <td>100</td>
                    <td>
                        <button class="btn btn-success"><i class="bi bi-pencil-square"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger"><i class="bi bi-trash3"></i>&nbsp;Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
