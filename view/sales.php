<?php
// Include the session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; // Adjust the path to your database connection file

$full_name = $_SESSION['full_name']; // Get the user's full name

// Fetch invoices from the database
$invoice_sql = "
    SELECT i.id, i.invoice_date, i.delivery_date, i.total_amount, 
           c.customer_name, c.customer_contact, c.customer_address, 
           u.last_name AS sold_by
    FROM invoice i
    JOIN customer c ON i.customer_id = c.id
    JOIN users u ON i.user_id = u.id
";
$invoice_result = $conn->query($invoice_sql);

if (!$invoice_result) {
    die("Error fetching invoices: " . $conn->error); // Debugging: Print the error
}

// Fetch sales report data
$total_invoices_sql = "SELECT COUNT(id) AS total_invoices FROM invoice";
$total_revenue_sql = "SELECT SUM(total_amount) AS total_revenue FROM invoice";
$total_products_sold_sql = "SELECT SUM(quantity) AS total_products_sold FROM invoice_items";

$total_invoices_result = $conn->query($total_invoices_sql);
$total_revenue_result = $conn->query($total_revenue_sql);
$total_products_sold_result = $conn->query($total_products_sold_sql);

$total_invoices = $total_invoices_result->fetch_assoc()['total_invoices'];
$total_revenue = $total_revenue_result->fetch_assoc()['total_revenue'];
$total_products_sold = $total_products_sold_result->fetch_assoc()['total_products_sold'];

// Calculate average revenue per invoice
$avg_revenue_per_invoice = $total_invoices > 0 ? $total_revenue / $total_invoices : 0;

// Fetch top selling products
$top_products_sql = "
    SELECT p.name AS product_name, SUM(ii.quantity) AS total_quantity_sold
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    GROUP BY p.name
    ORDER BY total_quantity_sold DESC
    LIMIT 5
";
$top_products_result = $conn->query($top_products_sql);
$top_products = $top_products_result->fetch_all(MYSQLI_ASSOC);

// Fetch revenue by month
$revenue_by_month_sql = "
    SELECT DATE_FORMAT(invoice_date, '%Y-%m') AS month, SUM(total_amount) AS monthly_revenue
    FROM invoice
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month DESC
";
$revenue_by_month_result = $conn->query($revenue_by_month_sql);
$revenue_by_month = $revenue_by_month_result->fetch_all(MYSQLI_ASSOC);

// Map month numbers to month names
$month_names = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
];
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
    <h3 class="text-primary">Sales/Invoice - Hello <?php echo htmlspecialchars($full_name); ?></h3>

    <!-- Sales Report -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Invoices</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($total_invoices); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <p class="card-text display-4">$<?php echo htmlspecialchars(number_format($total_revenue, 2)); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Products Sold</h5>
                    <p class="card-text display-4"><?php echo htmlspecialchars($total_products_sold); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Avg. Revenue/Invoice</h5>
                    <p class="card-text display-4">$<?php echo htmlspecialchars(number_format($avg_revenue_per_invoice, 2)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row mb-4">
        <!-- Top Selling Products -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity Sold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $product) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['total_quantity_sold']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Revenue by Month -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Revenue by Month</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue_by_month as $month) { 
                                $month_number = substr($month['month'], 5, 2); // Extract month number from YYYY-MM
                                $month_name = $month_names[$month_number]; // Get month name from the map
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($month_name); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($month['monthly_revenue'], 2)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <h4>Invoices</h4>
    <table id="invoicesTable" class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Invoice Date</th>
                <th>Delivery Date</th>
                <th>Customer</th>
                <th>Sold By</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $invoice_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['invoice_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['delivery_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['sold_by']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($row['total_amount'], 2)); ?></td>
                    <td>
                        <!-- View Button -->
                        <a href="invoice_details.php?view_invoice_id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#invoicesTable').DataTable();
});
</script>
</body>
</html>