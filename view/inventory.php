<?php
include '../Handler/session.php';
$full_name = $_SESSION['full_name']; // Get the user's full name

// DB Connection
include '../Handler/db.php';
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

<?php include 'navbar.php'; ?>


<!-- Main -->
<div class="content">
    <h3 class="text-primary">Inventory</h3>

    <!-- Controls -->
    <div class="d-flex justify-content-between mb-3">
        <input type="text" class="form-control w-25" placeholder="🔍 Quick search">
        <div>
            <button class="btn btn-primary">+ Add Inventory</button>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>
</html>
