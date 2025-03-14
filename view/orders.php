<!-- ADD SOME CANCELED TABLE BELOW COMPLETE -->

<?php
// Include session & database connection
include '../Handler/session.php';
include '../Handler/db.php';

$full_name = $_SESSION['full_name']; // Get logged-in user's name

$customer_sql = "SELECT * FROM customer";
$customer_result = $conn->query($customer_sql);

// Fetch ongoing orders with customer details
$ongoing_orders_sql = "
    SELECT o.id, o.order_date, o.delivery_date, o.status, o.amount_paid, 
           c.customer_name, c.customer_contact, c.customer_address, 
           COALESCE(SUM(od.quantity * od.unique_price), 0) AS amount_total
    FROM orders o
    JOIN customer c ON o.customer_id = c.id
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.status IN ('Pending')
    GROUP BY o.id
";
$ongoing_orders_result = $conn->query($ongoing_orders_sql);

// Fetch completed orders with customer details
$completed_orders_sql = "
    SELECT o.id, o.order_date, o.delivery_date, o.status, o.amount_paid, 
           c.customer_name, c.customer_contact, c.customer_address, 
           COALESCE(SUM(od.quantity * od.unique_price), 0) AS amount_total
    FROM orders o
    JOIN customer c ON o.customer_id = c.id
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.status IN ('Completed')
    GROUP BY o.id
";
$completed_orders_result = $conn->query($completed_orders_sql);

// Fetch completed orders with customer details
$cancelled_orders_sql = "
    SELECT o.id, o.order_date, o.delivery_date, o.status, o.amount_paid, 
           c.customer_name, c.customer_contact, c.customer_address, 
           COALESCE(SUM(od.quantity * od.unique_price), 0) AS amount_total
    FROM orders o
    JOIN customer c ON o.customer_id = c.id
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.status IN ('Cancelled')
    GROUP BY o.id
";
$cancelled_orders_result = $conn->query($cancelled_orders_sql);

// Fetch products for order details
$products_sql = "SELECT id, name, price FROM products";
$products_result = $conn->query($products_sql);

// Handle Add Order
if (isset($_POST['add_order'])) {
    $customer_name = $_POST['customer_name'];
    $customer_contact = $_POST['customer_contact'];
    $customer_address = $_POST['customer_address'];
    $order_date = $_POST['order_date'];
    $delivery_date = $_POST['delivery_date']; // Get the delivery date from form input
    $status = $_POST['status'];
    $amount_paid = $_POST['amount_paid'];

    // Insert Customer
    $stmt = $conn->prepare("INSERT INTO customer (customer_name, customer_contact, customer_address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $customer_name, $customer_contact, $customer_address);
    $stmt->execute();
    $customer_id = $conn->insert_id; // Get the ID of the newly inserted customer

    // Insert the order
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, status, amount_paid, delivery_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $customer_id, $status, $amount_paid, $delivery_date);    
    $stmt->execute();
    $order_id = $conn->insert_id; // Get the ID of the newly inserted order

    // Insert order details (products)
    if (isset($_POST['product_id'])) {
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];

        foreach ($product_ids as $index => $product_id) {
            $quantity = $quantities[$index];
            $price = $_POST['price'][$index];

            $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, unique_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $stmt->execute();
        }
    }

    $_SESSION['success'] = "Order added successfully.";
    header("Location: orders.php");
    exit;
}

// Handle Edit Order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $customer_name = $_POST['customer_name'];
    $customer_contact = $_POST['customer_contact'];
    $customer_address = $_POST['customer_address'];
    $order_date = $_POST['order_date'];
    $status = $_POST['status'];
    $amount_paid = $_POST['amount_paid'];

    // Update the customer details
    $stmt = $conn->prepare("UPDATE customer SET customer_name = ?, customer_contact = ?, customer_address = ? WHERE id = (SELECT customer_id FROM orders WHERE id = ?)");
    $stmt->bind_param("sssi", $customer_name, $customer_contact, $customer_address, $order_id);
    $stmt->execute();

    // Update the order
    $stmt = $conn->prepare("UPDATE orders SET order_date = ?, status = ?, amount_paid = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $order_date, $status, $amount_paid, $order_id);
    $stmt->execute();

    $_SESSION['success'] = "Order updated successfully.";
    header("Location: orders.php");
    exit;
}

// Handle Complete Order
if (isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order status to 'Completed'
        $stmt = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed (Update Orders): " . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();

        // Fetch order details for invoice
        $stmt = $conn->prepare("
            SELECT o.customer_id, o.amount_paid, o.delivery_date 
            FROM orders o 
            WHERE o.id = ?
        ");
        if (!$stmt) {
            throw new Exception("Prepare failed (Fetch Order): " . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();

        if (!$order) {
            throw new Exception("Order not found.");
        }

        // Insert into invoice table
        $stmt = $conn->prepare("
            INSERT INTO invoice (user_id, invoice_date, customer_id, delivery_date, total_amount) 
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new Exception("Prepare failed (Insert Invoice): " . $conn->error);
        }
        $user_id = $_SESSION['user_id']; // Get logged-in user ID
        $today = date('Y-m-d'); // Today's date
        $stmt->bind_param("isisd", $user_id, $today, $order['customer_id'], $order['delivery_date'], $order['amount_paid']);
        $stmt->execute();
        $invoice_id = $conn->insert_id; // Get the newly created invoice ID
        $stmt->close();

        // Fetch order details to transfer to invoice_items
        $stmt = $conn->prepare("
            SELECT product_id, quantity, unique_price 
            FROM order_details 
            WHERE order_id = ?
        ");
        if (!$stmt) {
            throw new Exception("Prepare failed (Fetch Order Details): " . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Insert order details into invoice_items
        while ($product = $result->fetch_assoc()) {
            $amount = $product['quantity'] * $product['unique_price'];
            $stmt2 = $conn->prepare("
                INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, amount) 
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt2) {
                throw new Exception("Prepare failed (Insert Invoice Items): " . $conn->error);
            }
            $stmt2->bind_param("iiidd", $invoice_id, $product['product_id'], $product['quantity'], $product['unique_price'], $amount);
            $stmt2->execute();
            $stmt2->close();
        }

        $stmt->close();
        $conn->commit(); // Commit transaction
        $_SESSION['success'] = "Order completed and invoice created successfully.";
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction if an error occurs
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: orders.php");
    exit;
}

// Fetch order details and products for View Order skibidity
if (isset($_GET['view_order_id'])) {
    $order_id = $_GET['view_order_id'];

    // Fetch order details
    $order_sql = "
        SELECT o.id, o.order_date, o.delivery_date, o.status, o.amount_paid, c.customer_name, c.customer_contact, c.customer_address 
        FROM orders o
        JOIN customer c ON o.customer_id = c.id
        WHERE o.id = ?
    ";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();

    // Fetch order products
    $products_sql = "
        SELECT p.name AS product_name, od.quantity, od.unique_price
        FROM order_details od
        JOIN products p ON od.product_id = p.id
        WHERE od.order_id = ?
    ";
    $stmt = $conn->prepare($products_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $products_result = $stmt->get_result();
    $products = $products_result->fetch_all(MYSQLI_ASSOC);

    // Calculate the total amount for all products
    $total_amount = 0;
    foreach ($products as $product) {
        $total_amount += $product['quantity'] * $product['unique_price'];
    }

    // Calculate the balance left
    $balance_left = $total_amount - $order['amount_paid'];
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
        <!-- Add Order Button -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addOrderModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Add Order
        </button>
    </div>

<!-- Ongoing Orders Table -->
<h4>Ongoing Orders</h4>
<div class="table-container">
    <table id="ongoingOrdersTable" class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Order Date</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th>Amount Total</th>
                <th>Amount Paid</th>
                <th>Balance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $ongoing_orders_result->fetch_assoc()) { ?>
                
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_contact']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_address']); ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td><?php echo $row['delivery_date']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['amount_total']; ?></td>
                    <td><?php echo $row['amount_paid']; ?></td>
                    <td><?php echo $row['amount_total'] - $row['amount_paid'];?></td>
                    <td>
                        <!-- View Button -->
                        <button class="btn btn-info btn-sm view-order"
                            data-id="<?php echo $row['id']; ?>"
                            data-customer="<?php echo $row['customer_name']; ?>"
                            data-contact="<?php echo $row['customer_contact']; ?>"
                            data-address="<?php echo $row['customer_address']; ?>"
                            data-date="<?php echo $row['order_date']; ?>"
                            data-status="<?php echo $row['status']; ?>"
                            data-amount="<?php echo $row['amount_paid']; ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#viewOrderModal">
                            <i class="bi bi-eye"></i> View
                        </button>

                        <!-- Edit Button -->
                        <button class="btn btn-warning btn-sm edit-order"
                            data-id="<?php echo $row['id']; ?>"
                            data-customer="<?php echo $row['customer_name']; ?>"
                            data-contact="<?php echo $row['customer_contact']; ?>"
                            data-address="<?php echo $row['customer_address']; ?>"
                            data-date="<?php echo $row['order_date']; ?>"
                            data-status="<?php echo $row['status']; ?>"
                            data-amount="<?php echo $row['amount_paid']; ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editOrderModal">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>

                        <!-- Delete Button -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_order" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this order?');">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>

                        <!-- Complete Button -->
                        <?php if ($row['status'] !== 'Completed') { ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="complete_order" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle"></i> Complete
                                </button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Completed Orders Table -->
<h4>Completed Orders</h4>
<div class="table-container">
    <table id="completedOrdersTable" class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Order Date</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th>Amount Total</th>
                <th>Amount Paid</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $completed_orders_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_contact']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_address']); ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td><?php echo $row['delivery_date']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['amount_total']; ?></td>
                    <td><?php echo $row['amount_paid']; ?></td>
                    <td><?php echo $row['amount_total'] - $row['amount_paid'];?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Cancelled Orders Table -->
<h4>Cancelled Orders</h4>
<div class="table-container">
    <table id="completedOrdersTable" class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Order Date</th>
                <th>Delivery Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $cancelled_orders_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_contact']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_address']); ?></td>
                    <td><?php echo $row['order_date']; ?></td>
                    <td><?php echo $row['delivery_date']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
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
                    <!-- Customer Name -->
                    <input type="text" name="customer_name" placeholder="Customer Name" required class="form-control mb-2">

                    <!-- Customer Contact -->
                    <input type="text" name="customer_contact" placeholder="Customer Contact" required class="form-control mb-2">

                    <!-- Customer Address -->
                    <input type="text" name="customer_address" placeholder="Customer Address" required class="form-control mb-2">

                    <!-- Delivery Date -->
                    <input type="date" name="delivery_date" required class="form-control mb-2">

                    <!-- Order Status -->
                    <select name="status" required class="form-control mb-2">
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <!-- Amount Paid -->
                    <input type="number" name="amount_paid" placeholder="Amount Paid" step="0.01" class="form-control mb-2">

                    <!-- Order Details (Products) -->
                    <div id="orderDetails">
                        <div class="row mb-2">
                            <div class="col">
                                <select name="product_id[]" class="form-control product-select" required>
                                    <option value="">Select Product</option>
                                    <?php while ($product = $products_result->fetch_assoc()) { ?>
                                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo $product['price']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col">
                                <input type="number" name="quantity[]" placeholder="Quantity" class="form-control quantity" required>
                            </div>
                            <div class="col">
                                <input type="number" name="price[]" placeholder="Price" class="form-control price" readonly>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-danger btn-sm removeProductField">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add Product Button -->
                    <button type="button" id="addProductField" class="btn btn-secondary btn-sm mb-3">
                        <i class="bi bi-plus"></i>&nbsp;Add Product
                    </button>

                    <!-- Total Amount -->
                    <div class="mb-3">
                        <label for="totalAmount">Total Amount:</label>
                        <input type="text" id="totalAmount" class="form-control" readonly>
                    </div>

                    <!-- Submit Button -->
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
                    <input type="text" id="editCustomerName" name="customer_name" placeholder="Customer Name" required class="form-control mb-2">
                    <input type="text" id="editCustomerContact" name="customer_contact" placeholder="Customer Contact" required class="form-control mb-2">
                    <input type="text" id="editCustomerAddress" name="customer_address" placeholder="Customer Address" required class="form-control mb-2">
                    <input type="date" id="editOrderDate" name="order_date" required class="form-control mb-2">
                    <select name="status" id="editStatus" required class="form-control mb-2">
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <input type="number" id="editAmountPaid" name="amount_paid" placeholder="Amount Paid" step="0.01" class="form-control mb-2">
                    <button type="submit" name="edit_order" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Order Details -->
                <div id="orderDetailsContent">
                    <p><strong>Order ID:</strong> <span id="viewOrderId"><?php echo $order['id']; ?></span></p>
                    <p><strong>Customer Name:</strong> <span id="viewCustomerName"><?php echo htmlspecialchars($order['customer_name']); ?></span></p>
                    <p><strong>Contact:</strong> <span id="viewCustomerContact"><?php echo htmlspecialchars($order['customer_contact']); ?></span></p>
                    <p><strong>Address:</strong> <span id="viewCustomerAddress"><?php echo htmlspecialchars($order['customer_address']); ?></span></p>
                    <p><strong>Order Date:</strong> <span id="viewOrderDate"><?php echo $order['order_date']; ?></span></p>
                    <p><strong>Status:</strong> <span id="viewOrderStatus"><?php echo $order['status']; ?></span></p>
                    <h5>Products Ordered</h5>
                    <table id="viewOrderProductsTable" class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="viewOrderProducts">
                            <?php foreach ($products as $product) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo $product['quantity']; ?></td>
                                    <td><?php echo $product['unique_price']; ?></td>
                                    <td><?php echo $product['quantity'] * $product['unique_price']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end" style="text-decoration: solid;">Total Amount:</th>
                                <th><?php echo number_format($total_amount, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    <!-- Amount Paid and Balance Left -->
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-md-10 text-end" style="color: green;">
                                <p><strong>Amount Paid:</strong> <?php echo number_format($order['amount_paid'], 2); ?></p>
                                <p><strong>Balance Left:</strong> <?php echo number_format($balance_left, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#ongoingOrdersTable, #completedOrdersTable').DataTable();

    // Function to calculate total amount
    function calculateTotal() {
        let total = 0;
        $('.row').each(function() {
            let quantity = $(this).find('.quantity').val();
            let price = $(this).find('.price').val();
            if (quantity && price) {
                total += quantity * price;
            }
        });
        $('#totalAmount').val(total.toFixed(2)); // Display total with 2 decimal places
    }

     // Add Product Field
    $(document).on('click', '#addProductField', function() {
        let newField = `
            <div class="row mb-2">
                <div class="col">
                    <select name="product_id[]" class="form-control product-select" required>
                        <option value="">Select Product</option>
                        <?php $products_result->data_seek(0); while ($product = $products_result->fetch_assoc()) { ?>
                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> - $<?php echo $product['price']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col">
                    <input type="number" name="quantity[]" placeholder="Quantity" class="form-control quantity" required>
                </div>
                <div class="col">
                    <input type="number" name="price[]" placeholder="Price" class="form-control price" readonly>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger btn-sm removeProductField">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#orderDetails').append(newField);
    });

    // Remove Product Field (Event Delegation)
    $(document).on('click', '.removeProductField', function() {
        $(this).closest('.row').remove();
        calculateTotal(); // Recalculate total after removing a product
    });

    // Update Price Field When Product is Selected (Event Delegation)
    $(document).on('change', '.product-select', function() {
        let price = $(this).find(':selected').data('price');
        $(this).closest('.row').find('.price').val(price);
        calculateTotal(); // Recalculate total after selecting a product
    });

    // Recalculate Total When Quantity Changes (Event Delegation)
    $(document).on('input', '.quantity', function() {
        calculateTotal(); // Recalculate total when quantity changes
    });

    // Function to Calculate Total Amount
    function calculateTotal() {
        let total = 0;
        $('.row').each(function() {
            let quantity = $(this).find('.quantity').val();
            let price = $(this).find('.price').val();
            if (quantity && price) {
                total += quantity * price;
            }
        });
        $('#totalAmount').val(total.toFixed(2)); // Display total with 2 decimal places
    }

    
    // Populate Edit Modal with Data
    $(document).on('click', '.edit-order', function() {
        $('#editOrderId').val($(this).data('id'));
        $('#editCustomerName').val($(this).data('customer'));
        $('#editCustomerContact').val($(this).data('contact'));
        $('#editCustomerAddress').val($(this).data('address'));
        $('#editOrderDate').val($(this).data('date'));
        $('#editStatus').val($(this).data('status'));
        $('#editAmountPaid').val($(this).data('amount'));
    });


    // Populate View Modal with Data
    $(document).on('click', '.view-order', function() {
    let orderId = $(this).data('id');
    window.location.href = 'orders.php?view_order_id=' + orderId;
    });
    // Remove view_order_id from the URL when the modal is closed
    $('#viewOrderModal').on('hidden.bs.modal', function () {
        // Get the current URL
        let url = new URL(window.location.href);

        // Remove the view_order_id parameter
        url.searchParams.delete('view_order_id');

        // Replace the current URL without the view_order_id parameter
        window.history.replaceState({}, document.title, url.toString());
    });

});

</script>

<!-- This thingy prevent the page from loading again -->
<?php if (isset($_GET['view_order_id'])) { ?>
    <script>
        $(document).ready(function() {
            $('#viewOrderModal').modal('show');
        });
    </script>
<?php } ?>

</body>
</html>