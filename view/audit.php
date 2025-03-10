<?php
// Include the session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; // Adjust the path to your database connection file

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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Main Content -->
<div class="content">
    <h3 class="text-primary">Dashboard - Hello <?php echo htmlspecialchars($full_name); ?></h3>


    <!-- Recent Inventory Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Recent Inventory</h5>
            <table id="recentInventoryTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_inventory_sql = "SELECT p.name, i.quantity, i.updated_at
                                             FROM inventory i
                                             JOIN products p ON i.product_id = p.id
                                             ORDER BY i.updated_at DESC
                                             LIMIT 5";
                    $recent_inventory_result = $conn->query($recent_inventory_sql);
                    if ($recent_inventory_result->num_rows > 0) {
                        while ($row = $recent_inventory_result->fetch_assoc()) {
                            echo '
                            <tr>
                                <td>' . htmlspecialchars($row['name']) . '</td>
                                <td>' . htmlspecialchars($row['quantity']) . '</td>
                                <td>' . htmlspecialchars($row['updated_at']) . '</td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3">No recent inventory found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Production Log Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Recent Production Log</h5>
            <table id="recentProductionLogTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Product</th>
                        <th>Material Consumed</th>
                        <th>Product Produced</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_production_sql = "SELECT sp.transaction_date, m.material_type, p.name AS product_name, sp.material_consumed, sp.product_produced
                                              FROM stock_product sp
                                              JOIN materials m ON sp.material_id = m.id
                                              JOIN products p ON sp.product_produced = p.id
                                              ORDER BY sp.transaction_date DESC
                                              LIMIT 5";
                    $recent_production_result = $conn->query($recent_production_sql);
                    if ($recent_production_result->num_rows > 0) {
                        while ($row = $recent_production_result->fetch_assoc()) {
                            echo '
                            <tr>
                                <td>' . htmlspecialchars($row['transaction_date']) . '</td>
                                <td>' . htmlspecialchars($row['material_type']) . '</td>
                                <td>' . htmlspecialchars($row['product_name']) . '</td>
                                <td>' . htmlspecialchars($row['material_consumed']) . '</td>
                                <td>' . htmlspecialchars($row['product_produced']) . '</td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5">No recent production logs found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Material Log Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Stock Material Log</h5>
            <table id="stockMaterialLogTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Material</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stock_material_sql = "SELECT sm.transaction_date, s.supplier_name, m.material_type, sm.quantity
                                           FROM stock_material sm
                                           JOIN suppliers s ON sm.supplier_id = s.id
                                           JOIN materials m ON sm.material_id = m.id
                                           ORDER BY sm.transaction_date DESC
                                           LIMIT 5";
                    $stock_material_result = $conn->query($stock_material_sql);
                    if ($stock_material_result->num_rows > 0) {
                        while ($row = $stock_material_result->fetch_assoc()) {
                            echo '
                            <tr>
                                <td>' . htmlspecialchars($row['transaction_date']) . '</td>
                                <td>' . htmlspecialchars($row['supplier_name']) . '</td>
                                <td>' . htmlspecialchars($row['material_type']) . '</td>
                                <td>' . htmlspecialchars($row['quantity']) . '</td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4">No stock material logs found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#recentInventoryTable').DataTable();
    $('#recentProductionLogTable').DataTable();
    $('#stockMaterialLogTable').DataTable();
});
</script>
</body>
</html>