<?php
// Include the session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; // Adjust the path to your database connection file

$full_name = $_SESSION['full_name']; // Get the user's full name
// Fetch orders with customer and order details
$orders_sql = "
    SELECT 
        o.id AS order_id,
        o.order_date,
        c.customer_name,
        o.status,
        SUM(od.quantity * od.unique_price) AS total_amount
    FROM orders o
    JOIN customer c ON o.customer_id = c.id
    JOIN order_details od ON o.id = od.order_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
";
$orders_result = $conn->query($orders_sql);

if (!$orders_result) {
    die("Error fetching orders: " . $conn->error);
}

// Fetch customers for the dropdown
$customer_sql = "SELECT id, customer_name FROM customer";
$customer_result = $conn->query($customer_sql);
if (!$customer_result) {
    die("Error fetching customers: " . $conn->error);
}

// Handle Add Customer
if (isset($_POST['add_customer'])) {
    $customer_name = $_POST['customer_name'];
    $customer_contact = $_POST['customer_contact'];
    $customer_address = $_POST['customer_address'];
    $created_by = $_SESSION['user_id']; // Assuming the logged-in user's ID is stored in the session

    // Validate inputs
    if (empty($customer_name)) {
        $_SESSION['error'] = "Customer name is required.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Insert into Customer Table
    $stmt = $conn->prepare("INSERT INTO customer (customer_name, customer_contact, customer_address, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $customer_name, $customer_contact, $customer_address, $created_by);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Customer added successfully!";
    } else {
        $_SESSION['error'] = "Error adding customer: " . $stmt->error;
    }

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Add Order
if (isset($_POST['add_order'])) {
    $customer_id = $_POST['customer_id'];
    $order_date = $_POST['order_date'];
    $status = isset($_POST['status']) ? 1 : 0; // 1 = Completed, 0 = Pending

    // Insert into Orders Table
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, status) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $customer_id, $order_date, $status);
    $stmt->execute();

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Edit Order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['id'];
    $customer_id = $_POST['customer_id'];
    $order_date = $_POST['order_date'];
    $status = isset($_POST['status']) ? 1 : 0; // 1 = Completed, 0 = Pending

    // Update the Order
    $stmt = $conn->prepare("UPDATE orders SET customer_id = ?, order_date = ?, status = ? WHERE id = ?");
    $stmt->bind_param("isii", $customer_id, $order_date, $status, $order_id);
    $stmt->execute();

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Order
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];

    // Delete the Order
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Management</title>
    <!-- Bootstrap, Icon, DataTable Link -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<!-- Sidebar -->
<?php include 'navbar.php'; ?>

<!-- Main Content-->
<div class="content">
    <h3 class="text-primary">Order Management - Hello <?php echo htmlspecialchars($full_name); ?></h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addOrderModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Add Order
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Add Customer
        </button>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <table id="ordersTable" class="table table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ordersList">
                <?php while ($row = $orders_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['order_id']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td><?php echo $row['customer_name']; ?></td>
                        <td><?php echo $row['status'] ? 'Completed' : 'Pending'; ?></td>
                        <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <!-- Info Button -->
                            <button class="btn btn-primary btn-sm info-order-btn"
                                data-id="<?php echo $row['order_id']; ?>"
                                data-customer-name="<?php echo htmlspecialchars($row['customer_name']); ?>"
                                data-order-date="<?php echo $row['order_date']; ?>"
                                data-status="<?php echo $row['status']; ?>"
                                data-total-amount="<?php echo $row['total_amount']; ?>">
                                <i class="bi bi-info-circle"></i>&nbsp;Info
                            </button>

                            <!-- Edit Button -->
                            <button class="btn btn-success btn-sm edit-order-btn" 
                                    data-id="<?php echo $row['order_id']; ?>" 
                                    data-customer-id="<?php echo $row['customer_id']; ?>" 
                                    data-order-date="<?php echo $row['order_date']; ?>" 
                                    data-status="<?php echo $row['status']; ?>">
                                <i class="bi bi-pencil-square"></i>&nbsp;Edit
                            </button>

                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                <input type="hidden" name="delete_order" value="1">
                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCustomerModalLabel">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Customer Form -->
                <form method="POST" action="">
                    <!-- Customer Name -->
                    <div class="mb-3">
                        <label for="customerName" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="customerName" name="customer_name" required>
                    </div>

                    <!-- Customer Contact -->
                    <div class="mb-3">
                        <label for="customerContact" class="form-label">Customer Contact</label>
                        <input type="text" class="form-control" id="customerContact" name="customer_contact">
                    </div>

                    <!-- Customer Address -->
                    <div class="mb-3">
                        <label for="customerAddress" class="form-label">Customer Address</label>
                        <textarea class="form-control" id="customerAddress" name="customer_address" rows="3"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="add_customer" class="btn btn-success">Add Customer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Order Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOrderModalLabel">Add Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Order Form -->
                <form method="POST">
                    <!-- Customer Select -->
                    <select name="customer_id" required class="form-control mb-2">
                        <option value="">Select Customer</option>
                        <?php
                        $customer_sql = "SELECT id, customer_name FROM customer";
                        $customer_result = $conn->query($customer_sql);
                        if ($customer_result->num_rows > 0) {
                            while ($customer = $customer_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($customer['id']) . '">' . htmlspecialchars($customer['customer_name']) . '</option>';
                            }
                        } else {
                            echo '<option value="">No customers available</option>';
                        }
                        ?>
                    </select>

                    <!-- Order Date Input -->
                    <input type="date" name="order_date" placeholder="Order Date" required class="form-control mb-2">

                    <!-- Status Checkbox -->
                    <div class="form-check mb-2">
                        <input type="checkbox" name="status" class="form-check-input" id="statusCheck">
                        <label class="form-check-label" for="statusCheck">Completed</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="add_order" class="btn btn-success">+ Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <!-- Hidden field for Order ID -->
                    <input type="hidden" name="id" id="editOrderId">
                    
                    <!-- Customer Select -->
                    <select name="customer_id" id="editCustomerId" required class="form-control mb-2">
                        <option value="">Select Customer</option>
                        <?php
                        $customer_sql = "SELECT id, customer_name FROM customer";
                        $customer_result = $conn->query($customer_sql);
                        if ($customer_result->num_rows > 0) {
                            while ($customer = $customer_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($customer['id']) . '">' . htmlspecialchars($customer['customer_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <!-- Order Date Input -->
                    <input type="date" name="order_date" id="editOrderDate" required class="form-control mb-2">

                    <!-- Status Checkbox -->
                    <div class="form-check mb-2">
                        <input type="checkbox" name="status" id="editStatus" class="form-check-input">
                        <label class="form-check-label" for="editStatus">Completed</label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" name="edit_order" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Info Order Modal -->
<div class="modal fade" id="infoOrderModal" tabindex="-1" aria-labelledby="infoOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoOrderModalLabel">Order Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Order ID:</strong> <span id="infoOrderId"></span></p>
                <p><strong>Customer Name:</strong> <span id="infoCustomerName"></span></p>
                <p><strong>Order Date:</strong> <span id="infoOrderDate"></span></p>
                <p><strong>Status:</strong> <span id="infoStatus"></span></p>
                <p><strong>Total Amount:</strong> <span id="infoTotalAmount"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#ordersTable').DataTable();

        // Handle Edit Button Click
        $(document).on("click", ".edit-order-btn", function() {
            let orderId = $(this).data("id");
            let customerId = $(this).data("customer-id");
            let orderDate = $(this).data("order-date");
            let status = $(this).data("status");

            // Populate the Edit Modal
            $("#editOrderId").val(orderId);
            $("#editCustomerId").val(customerId);
            $("#editOrderDate").val(orderDate);
            $("#editStatus").prop("checked", status == 1);

            // Show the Edit Modal
            $("#editOrderModal").modal("show");
        });

        // Handle Info Button Click
        $(document).on("click", ".info-order-btn", function() {
            let orderId = $(this).data("id");
            let customerName = $(this).data("customer-name");
            let orderDate = $(this).data("order-date");
            let status = $(this).data("status") ? "Completed" : "Pending";
            let totalAmount = $(this).data("total-amount");

            // Populate the Info Modal
            $("#infoOrderId").text(orderId);
            $("#infoCustomerName").text(customerName);
            $("#infoOrderDate").text(orderDate);
            $("#infoStatus").text(status);
            $("#infoTotalAmount").text("$" + totalAmount);

            // Show the Info Modal
            $("#infoOrderModal").modal("show");
        });
    });
</script>
</body>
</html>

<?php $conn->close(); ?>