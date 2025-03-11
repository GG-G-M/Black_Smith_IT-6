<?php
// Include the session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; // Adjust the path to your database connection file

$full_name = $_SESSION['full_name']; // Get the user's full name

// Fetch data for the bar graph
$product_count_sql = "SELECT COUNT(*) AS total FROM products";
$product_count_result = $conn->query($product_count_sql);
$product_count = $product_count_result->fetch_assoc()['total'];

$material_count_sql = "SELECT COUNT(*) AS total FROM materials";
$material_count_result = $conn->query($material_count_sql);
$material_count = $material_count_result->fetch_assoc()['total'];

$inventory_count_sql = "SELECT SUM(quantity) AS total FROM inventory";
$inventory_count_result = $conn->query($inventory_count_sql);
$inventory_count = $inventory_count_result->fetch_assoc()['total'];
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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Main Content -->
<div class="content">
    <h3 class="text-primary">Dashboard - Hello <?php echo htmlspecialchars($full_name); ?></h3>

    <!-- Quick Stats Section -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Products</h5>
                    <p class="card-text">
                        <?php echo $product_count; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Materials</h5>
                    <p class="card-text">
                        <?php echo $material_count; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Inventory</h5>
                    <p class="card-text">
                        <?php echo $inventory_count; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Graph Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Inventory Overview</h5>
                    <canvas id="inventoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    // Bar Graph Data
    const ctx = document.getElementById('inventoryChart').getContext('2d');
    const inventoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Products', 'Materials', 'Inventory'],
            datasets: [{
                label: 'Count',
                data: [
                    <?php echo $product_count; ?>,
                    <?php echo $material_count; ?>,
                    <?php echo $inventory_count; ?>
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.2)', // Blue
                    'rgba(75, 192, 192, 0.2)',  // Green
                    'rgba(255, 206, 86, 0.2)'   // Yellow
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>