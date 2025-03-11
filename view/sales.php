<?php
// Include session & database connection
include '../Handler/session.php';
include '../Handler/db.php';

$full_name = $_SESSION['full_name']; // Get logged-in user's name

// Fetch invoices with customer names & orders
$sales_sql = "SELECT i.id, c.customer_name, i.invoice_date, i.total, i.tin, i.delivery_date
              FROM invoice i
              JOIN customer c ON i.customer_id = c.id";
$sales_result = $conn->query($sales_sql);

// Fetch customers for invoice form
$customers_sql = "SELECT id, customer_name FROM customer";
$customers_result = $conn->query($customers_sql);

// Fetch orders for invoice form
$orders_sql = "SELECT id FROM orders";
$orders_result = $conn->query($orders_sql);

// Handle Add Invoice
if (isset($_POST['add_invoice'])) {
    $customer_id = $_POST['customer_id'];
    $order_id = $_POST['order_id'];
    $invoice_date = $_POST['invoice_date'];
    $delivery_date = $_POST['delivery_date'];
    $tin = $_POST['tin'];
    $total = $_POST['total'];

    $stmt = $conn->prepare("INSERT INTO invoice (user_id, invoice_date, customer_id, order_details_id, delivery_date, tin, total) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiisd", $_SESSION['user_id'], $invoice_date, $customer_id, $order_id, $delivery_date, $tin, $total);
    $stmt->execute();
    $stmt->close();

    header("Location: sales.php");
    exit;
}

// Handle Edit Invoice
if (isset($_POST['edit_invoice'])) {
    $invoice_id = $_POST['invoice_id'];
    $customer_id = $_POST['customer_id'];
    $order_id = $_POST['order_id'];
    $invoice_date = $_POST['invoice_date'];
    $delivery_date = $_POST['delivery_date'];
    $tin = $_POST['tin'];
    $total = $_POST['total'];

    $stmt = $conn->prepare("UPDATE invoice 
                            SET customer_id = ?, order_details_id = ?, invoice_date = ?, delivery_date = ?, tin = ?, total = ?
                            WHERE id = ?");
    $stmt->bind_param("iisssdi", $customer_id, $order_id, $invoice_date, $delivery_date, $tin, $total, $invoice_id);
    $stmt->execute();
    $stmt->close();

    header("Location: sales.php");
    exit;
}

// Handle Delete Invoice
if (isset($_POST['delete_invoice'])) {
    $invoice_id = $_POST['invoice_id'];

    $stmt = $conn->prepare("DELETE FROM invoice WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $stmt->close();

    header("Location: sales.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Management</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="content">
    <h3 class="text-primary">Sales Management - Hello <?php echo htmlspecialchars($full_name); ?></h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addInvoiceModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Add Sale
        </button>
    </div>

    <div class="table-container">
        <table id="salesTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Invoice Date</th>
                    <th>Delivery Date</th>
                    <th>TIN</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $sales_result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo $row['invoice_date']; ?></td>
                        <td><?php echo $row['delivery_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['tin']); ?></td>
                        <td><?php echo number_format($row['total'], 2); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-invoice"
                                data-id="<?php echo $row['id']; ?>"
                                data-customer="<?php echo $row['customer_name']; ?>"
                                data-date="<?php echo $row['invoice_date']; ?>"
                                data-delivery="<?php echo $row['delivery_date']; ?>"
                                data-tin="<?php echo $row['tin']; ?>"
                                data-total="<?php echo $row['total']; ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#editInvoiceModal">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="invoice_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_invoice" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this invoice?');">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Invoice Modal -->
<div class="modal fade" id="addInvoiceModal" tabindex="-1" aria-labelledby="addInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInvoiceModalLabel">Add Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <select name="customer_id" required class="form-control mb-2">
                        <option value="">Select Customer</option>
                        <?php while ($customer = $customers_result->fetch_assoc()) { ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['customer_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <input type="date" name="invoice_date" required class="form-control mb-2">
                    <input type="date" name="delivery_date" required class="form-control mb-2">
                    <input type="text" name="tin" placeholder="TIN (Optional)" class="form-control mb-2">
                    <input type="number" step="0.01" name="total" placeholder="Total Amount" required class="form-control mb-2">
                    <button type="submit" name="add_invoice" class="btn btn-primary">Add Sale</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#salesTable').DataTable();

        $('.edit-invoice').click(function() {
            let invoiceId = $(this).data('id');
            let customerName = $(this).data('customer');
            let invoiceDate = $(this).data('date');
            let deliveryDate = $(this).data('delivery');
            let tin = $(this).data('tin');
            let total = $(this).data('total');

            $('#editInvoiceId').val(invoiceId);
            $('#editInvoiceDate').val(invoiceDate);
            $('#editDeliveryDate').val(deliveryDate);
            $('#editTIN').val(tin);
            $('#editTotal').val(total);
        });
    });
</script>

</body>
</html>
