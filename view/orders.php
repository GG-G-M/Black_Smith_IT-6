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
    SELECT o.id, o.order_date, o.delivery_date, o.status, o.amount_paid, c.customer_name, c.customer_contact, c.customer_address 
    FROM orders o
    JOIN customer c ON o.customer_id = c.id
    WHERE o.status IN ('Pending', 'Cancelled')
";
$ongoing_orders_result = $conn->query($ongoing_orders_sql);

// Fetch completed orders with customer details
$completed_orders_sql = "
    SELECT o.id, o.order_date, o.delivery_date, o.status, o.amount_paid, c.customer_name, c.customer_contact, c.customer_address 
    FROM orders o
    JOIN customer c ON o.customer_id = c.id
    WHERE o.status = 'Completed'
";
$completed_orders_result = $conn->query($completed_orders_sql);

// Fetch products for order details
$products_sql = "SELECT id, name, price FROM products";
$products_result = $conn->query($products_sql);

// Handle Fetch Order Details Request
if (isset($_POST['fetch_order_details'])) {
    $order_id = $_POST['order_id'];

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

    if (!$order) {
        // If no order is found, return an error
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

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

    // Combine order and product data
    $response = [
        'id' => $order['id'],
        'customer_name' => $order['customer_name'],
        'customer_contact' => $order['customer_contact'],
        'customer_address' => $order['customer_address'],
        'order_date' => $order['order_date'],
        'status' => $order['status'],
        'amount_paid' => $order['amount_paid'],
        'products' => $products
    ];

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle Add Order
if (isset($_POST['add_order'])) {
    $customer_name = $_POST['customer_name'];
    $customer_contact = $_POST['customer_contact'];
    $customer_address = $_POST['customer_address'];
    $order_date = date('Y-m-d'); // Current date for order_date
    $delivery_date = $_POST['delivery_date']; // Keep the original name for delivery_date
    $status = $_POST['status'];
    $amount_paid = $_POST['amount_paid'];

    // Insert Customer
    $stmt = $conn->prepare("INSERT INTO customer (customer_name, customer_contact, customer_address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $customer_name, $customer_contact, $customer_address);
    $stmt->execute();
    $customer_id = $conn->insert_id; // Get the ID of the newly inserted customer

    // Insert the order
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, status, amount_paid, order_date, delivery_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $customer_id, $status, $amount_paid, $order_date, $delivery_date);    
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
    $delivery_date = $_POST['delivery_date'];
    $status = $_POST['status'];
    $amount_paid = $_POST['amount_paid'];

    // Update the customer details
    $stmt = $conn->prepare("UPDATE customer SET customer_name = ?, customer_contact = ?, customer_address = ? WHERE id = (SELECT customer_id FROM orders WHERE id = ?)");
    $stmt->bind_param("sssi", $customer_name, $customer_contact, $customer_address, $order_id);
    $stmt->execute();

    // Update the order with delivery_date instead of order_date
    $stmt = $conn->prepare("UPDATE orders SET delivery_date = ?, status = ?, amount_paid = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $delivery_date, $status, $amount_paid, $order_id);
    $stmt->execute();

    $_SESSION['success'] = "Order updated successfully.";
    header("Location: orders.php");
    exit;
}

// Handle Complete Order
if (isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];

    // Update order status to 'Completed'
    $stmt = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
    if (!$stmt) {
        die("Error in prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    // Get order information for invoice
    $query = "SELECT o.customer_id, o.amount_paid 
              FROM orders o 
              WHERE o.id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error in prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if ($order) {
        // Get current user ID from session
        $user_id = $_SESSION['user_id']; // Make sure this exists in your session
        
        // Today's date for invoice_date
        $today = date('Y-m-d');
        
        // Debug output - print the actual SQL statement
        $debug_sql = "INSERT INTO invoice (user_id, invoice_date, customer_id, delivery_date, total_amount) 
                      VALUES ($user_id, '$today', {$order['customer_id']}, '$today', {$order['amount_paid']})";
        
        // Insert into invoice - using the correct column name total_amount from your schema
        $stmt = $conn->prepare("INSERT INTO invoice (user_id, invoice_date, customer_id, delivery_date, total_amount) 
                              VALUES (?, ?, ?, ?, ?)");
        
        // Check if preparation succeeded
        if (!$stmt) {
            die("Error in prepare statement: " . $conn->error . "<br>Attempted SQL: " . $debug_sql);
        }
        
        $stmt->bind_param("isiss", $user_id, $today, $order['customer_id'], $today, $order['amount_paid']);
        $result = $stmt->execute();
        
        if (!$result) {
            $_SESSION['error'] = "Failed to create invoice: " . $stmt->error;
        } else {
            $_SESSION['success'] = "Order completed and invoice created successfully.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to generate invoice. Order not found.";
    }
    
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
                <th>Amount Paid</th>
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
                    <td><?php echo $row['amount_paid']; ?></td>
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
                            data-delivery-date="<?php echo $row['delivery_date']; ?>" 
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
                <th>Status</th>
                <th>Amount Paid</th>
                <th>Actions</th>
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
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['amount_paid']; ?></td>
                    <td>
                        <!-- Change Order Status to Pending -->
                        <?php
                        if (isset($_POST['set_pending_' . $row['id']])) {
                            $order_id = $row['id'];

                            // Update status to 'Pending' in database
                            $stmt = $conn->prepare("UPDATE orders SET status = 'Pending' WHERE id = ?");
                            $stmt->bind_param("i", $order_id);
                            $stmt->execute();
                            $stmt->close();

                            // Refresh page to reflect change
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit;
                        }
                        ?>

                        <form method="POST" style="display:inline;">
                            <button type="submit" name="set_pending_<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-exclamation-circle"></i> Set Pending
                            </button>
                        </form>

                    </td>
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
                    <input type="date" id="editDeliveryDate" name="delivery_date" required class="form-control mb-2">
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
                    <p><strong>Order ID:</strong> <span id="viewOrderId"></span></p>
                    <p><strong>Customer Name:</strong> <span id="viewCustomerName"></span></p>
                    <p><strong>Contact:</strong> <span id="viewCustomerContact"></span></p>
                    <p><strong>Address:</strong> <span id="viewCustomerAddress"></span></p>
                    <p><strong>Order Date:</strong> <span id="viewOrderDate"></span></p>
                    <p><strong>Status:</strong> <span id="viewOrderStatus"></span></p>
                    <p><strong>Amount Paid:</strong> <span id="viewAmountPaid"></span></p>

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
                            <!-- Product rows will be populated here -->
                        </tbody>
                    </table>
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

    // Add Product Field (Event Delegation)
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
        calculateTotal(); // Recalculate total after adding a product
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

    // Populate Edit Modal with Data
    $(document).on('click', '.edit-order', function() {
        $('#editOrderId').val($(this).data('id'));
        $('#editCustomerName').val($(this).data('customer'));
        $('#editCustomerContact').val($(this).data('contact'));
        $('#editCustomerAddress').val($(this).data('address'));
        $('#editDeliveryDate').val($(this).data('delivery-date'));
        $('#editStatus').val($(this).data('status'));
        $('#editAmountPaid').val($(this).data('amount'));
    });


    // Populate View Modal with Data
    $(document).on('click', '.view-order', function() {
        // Populate basic order details
        $('#viewOrderId').text($(this).data('id'));
        $('#viewCustomerName').text($(this).data('customer'));
        $('#viewCustomerContact').text($(this).data('contact'));
        $('#viewCustomerAddress').text($(this).data('address'));
        $('#viewOrderDate').text($(this).data('date'));
        $('#viewOrderStatus').text($(this).data('status'));
        $('#viewAmountPaid').text($(this).data('amount'));

        // Fetch product details via AJAX
        let orderId = $(this).data('id');
        $.ajax({
            url: 'orders.php', // Send request to the same file
            type: 'POST',
            data: { fetch_order_details: true, order_id: orderId },
            success: function(response) {
                console.log("Server Response:", response); // Debugging
                let order = JSON.parse(response);

                // Clear previous product rows
                $('#viewOrderProducts').empty();

                // Add product rows
                order.products.forEach(product => {
                    let total = product.quantity * product.unique_price;
                    $('#viewOrderProducts').append(`
                        <tr>
                            <td>${product.product_name}</td>
                            <td>${product.quantity}</td>
                            <td>${product.unique_price}</td>
                            <td>${total.toFixed(2)}</td>
                        </tr>
                    `);
                });
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error); // Debugging
            }
        });
    }); 

});
</script>

</body>
</html>