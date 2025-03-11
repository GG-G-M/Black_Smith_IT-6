<?php
// Include session & database connection
include '../Handler/session.php';
include '../Handler/db.php';

$full_name = $_SESSION['full_name']; // Get logged-in user's name

// Fetch orders with customer names
$orders_sql = "SELECT o.id, c.customer_name, o.order_date, o.status
               FROM orders o
               JOIN customer c ON o.customer_id = c.id";
$order_result = $conn->query($orders_sql);

// Fetch customers for order form
$customers_sql = "SELECT id, customer_name FROM customer";
$customers_result = $conn->query($customers_sql);

// Handle Add Order
if (isset($_POST['add_order'])) {
    $customer_id = $_POST['customer_id'];
    $order_date = $_POST['order_date'];

    $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date) VALUES (?, ?)");
    $stmt->bind_param("is", $customer_id, $order_date);
    $stmt->execute();
    $stmt->close();

    header("Location: orders.php");
    exit;
}

// Handle Edit Order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $customer_id = $_POST['customer_id'];
    $order_date = $_POST['order_date'];

    $stmt = $conn->prepare("UPDATE orders SET customer_id = ?, order_date = ? WHERE id = ?");
    $stmt->bind_param("isi", $customer_id, $order_date, $order_id);
    $stmt->execute();
    $stmt->close();

    header("Location: orders.php");
    exit;
}

// Handle Delete Order
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];

    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    header("Location: orders.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders Management</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="content">
    <h3 class="text-primary">Orders Management - Hello <?php echo htmlspecialchars($full_name); ?></h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addOrderModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Add Order
        </button>
    </div>

    <div class="table-container">
        <table id="ordersTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $order_result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td><?php echo $row['status'] ? "Completed" : "Pending"; ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-order"
                                data-id="<?php echo $row['id']; ?>"
                                data-customer="<?php echo $row['customer_name']; ?>"
                                data-date="<?php echo $row['order_date']; ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#editOrderModal">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_order" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this order?');">
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

<!-- Add Order Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOrderModalLabel">Add Order</h5>
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
                    <input type="date" name="order_date" required class="form-control mb-2">
                    <button type="submit" name="add_order" class="btn btn-primary">Add Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="editOrderId" name="order_id">
                    <select name="customer_id" id="editCustomerId" required class="form-control mb-2">
                        <option value="">Select Customer</option>
                        <?php $customers_result->data_seek(0); while ($customer = $customers_result->fetch_assoc()) { ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['customer_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <input type="date" id="editOrderDate" name="order_date" required class="form-control mb-2">
                    <button type="submit" name="edit_order" class="btn btn-primary">Save Changes</button>
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
        $('#ordersTable').DataTable();

        $('.edit-order').click(function() {
            let orderId = $(this).data('id');
            let customerName = $(this).data('customer');
            let orderDate = $(this).data('date');

            $('#editOrderId').val(orderId);
            $('#editCustomerId option:contains("' + customerName + '")').attr("selected", "selected");
            $('#editOrderDate').val(orderDate);
        });
    });
</script>

</body>
</html>
