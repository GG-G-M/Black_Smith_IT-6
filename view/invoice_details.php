<?php
// Include the session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; // Adjust the path to your database connection file

$full_name = $_SESSION['full_name']; // Get the user's full name

// Fetch invoice details and items if view_invoice_id is set
$invoice = null;
$invoice_items = [];
if (isset($_GET['view_invoice_id'])) {
    $invoice_id = $_GET['view_invoice_id'];

    // Fetch invoice details
    $invoice_sql = "
        SELECT i.id, i.invoice_date, i.delivery_date, i.total_amount, 
               c.customer_name, c.customer_contact, c.customer_address, 
               u.last_name AS sold_by
        FROM invoice i
        JOIN customer c ON i.customer_id = c.id
        JOIN users u ON i.user_id = u.id
        WHERE i.id = ?
    ";
    $stmt = $conn->prepare($invoice_sql);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $invoice_result = $stmt->get_result();
    $invoice = $invoice_result->fetch_assoc();

    if ($invoice) {
        // Fetch invoice items
        $invoice_items_sql = "
            SELECT p.name AS product_name, ii.quantity, ii.unit_price, ii.amount 
            FROM invoice_items ii
            JOIN products p ON ii.product_id = p.id
            WHERE ii.invoice_id = ?
        ";
        $stmt = $conn->prepare($invoice_items_sql);
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $invoice_items_result = $stmt->get_result();
        $invoice_items = $invoice_items_result->fetch_all(MYSQLI_ASSOC);

        // Fetch return records for this invoice
        $returns_sql = "
            SELECT r.id, r.return_date, r.reason, r.status, 
                   p.name AS product_name, c.customer_name
            FROM returns r
            JOIN products p ON r.product_id = p.id
            JOIN customer c ON r.customer_id = c.id
            WHERE r.order_id = ?
        ";
        $stmt = $conn->prepare($returns_sql);
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $returns_result = $stmt->get_result();
        $returns = $returns_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle Return Initiation Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_return'])) {
    $order_id = $invoice_id; // Use the current invoice ID as the order ID
    $customer_id = $invoice['customer_id']; // Fetch customer ID from the invoice
    $product_id = $_POST['product_id'];
    $return_date = $_POST['return_date'];
    $reason = $_POST['reason'];
    $status = 'Pending'; // Default status
    $created_by = $_SESSION['user_id']; // Logged-in user ID

    // Insert the return record into the database
    $insert_sql = "
        INSERT INTO returns (order_id, customer_id, product_id, return_date, reason, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iissssi", $order_id, $customer_id, $product_id, $return_date, $reason, $status, $created_by);
    $stmt->execute();

    // Redirect to refresh the page and show the new return record
    header("Location: invoice_details.php?view_invoice_id=$invoice_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Details</title>

    <!-- Aesthetics -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Main Content -->
<div class="content">
    <h3 class="text-primary">Invoice Details - Hello <?php echo htmlspecialchars($full_name); ?></h3>

    <!-- Invoice Details -->
    <div id="invoiceDetailsContent">
        <?php if ($invoice) { ?>
            <p><strong>Invoice ID:</strong> <span id="viewInvoiceId"><?php echo htmlspecialchars($invoice['id']); ?></span></p>
            <p><strong>Invoice Date:</strong> <span id="viewInvoiceDate"><?php echo htmlspecialchars($invoice['invoice_date']); ?></span></p>
            <p><strong>Delivery Date:</strong> <span id="viewDeliveryDate"><?php echo htmlspecialchars($invoice['delivery_date']); ?></span></p>
            <p><strong>Customer:</strong> <span id="viewCustomer"><?php echo htmlspecialchars($invoice['customer_name']); ?></span></p>
            <p><strong>Contact:</strong> <span id="viewCustomerContact"><?php echo htmlspecialchars($invoice['customer_contact']); ?></span></p>
            <p><strong>Address:</strong> <span id="viewCustomerAddress"><?php echo htmlspecialchars($invoice['customer_address']); ?></span></p>
            <p><strong>Sold By:</strong> <span id="viewCreatedBy"><?php echo htmlspecialchars($invoice['sold_by']); ?></span></p>

            <!-- Invoice Items Table -->
            <h5>Products Ordered</h5>
            <table id="viewInvoiceItemsTable" class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="viewInvoiceItems">
                    <?php
                    $totalAmount = 0;
                    foreach ($invoice_items as $item) {
                        $total = $item['quantity'] * $item['unit_price'];
                        $totalAmount += $total;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['unit_price']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($total, 2)); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Total Amount, Amount Paid, and Balance Left -->
            <div class="mt-4">
                <p><strong>Total Amount:</strong> <?php echo htmlspecialchars(number_format($totalAmount, 2)); ?></p>
                <p><strong>Amount Paid:</strong> <?php echo htmlspecialchars(number_format($invoice['total_amount'], 2)); ?></p>
                <p><strong>Balance Left:</strong> <?php echo htmlspecialchars(number_format($totalAmount - $invoice['total_amount'], 2)); ?></p>
            </div>
        <?php } else { ?>
            <p>No invoice details found.</p>
        <?php } ?>
    </div>
    <!-- Return Button -->
    <button type="button" class="btn btn-secondary">
        <a href="sales.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-arrow-return-left"></i>Return</a>
    </button>
    <!-- Print Button -->
    <button type="button" class="btn btn-primary" id="printInvoiceButton">
        <i class="bi bi-printer"></i> Print
    </button>

    <!-- Return Table -->
    <h4 class="mt-5">Returns</h4>
    <!-- Initiate Return Button -->
    <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#initiateReturnModal">
        <i class="bi bi-arrow-90deg-up"></i> Return Items
    </button>
    <table id="returnsTable" class="table table-hover">
        <thead>
            <tr>
                <th>Return ID</th>
                <th>Return Date</th>
                <th>Product</th>
                <th>Customer</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($returns)) { ?>
                <?php foreach ($returns as $return) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($return['id']); ?></td>
                        <td><?php echo htmlspecialchars($return['return_date']); ?></td>
                        <td><?php echo htmlspecialchars($return['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($return['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($return['reason']); ?></td>
                        <td>
                            <span class="badge 
                                <?php echo $return['status'] === 'Approved' ? 'bg-success' : 
                                      ($return['status'] === 'Rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                                <?php echo htmlspecialchars($return['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6" class="text-center">No returns found for this invoice.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    
</div>

<!-- Initiate Return Modal -->
<div class="modal fade" id="initiateReturnModal" tabindex="-1" aria-labelledby="initiateReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="initiateReturnModalLabel">Initiate Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <?php foreach ($invoice_items as $item) { ?>
                                <option value="<?php echo htmlspecialchars($item['product_id']); ?>">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" class="form-control" id="return_date" name="return_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="initiate_return" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Handle Print Button Click
    $(document).on('click', '#printInvoiceButton', function() {
        // Clone the invoice content
        let printContent = $('#invoiceDetailsContent').clone();

        // Open a new window for printing
        let printWindow = window.open('', '', 'width=800,height=600');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Invoice Details</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                </head>
                <body>
                    ${printContent.html()}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    });
});
</script>
</body>
</html>