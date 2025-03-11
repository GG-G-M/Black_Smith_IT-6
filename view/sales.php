<?php
// Include session & database connection
include '../Handler/session.php';
include '../Handler/db.php';

// Generate unique receipt number
function generateReceiptNo() {
    return str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
}

// Handle new sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_sale'])) {
    // Validate and process sale
    $customer_name = $_POST['customer_name'] ?? 'Walk-in Customer';
    $mode_of_payment = $_POST['mode_of_payment'];
    $amount_tendered = (float)$_POST['amount_tendered'];
    
    // Calculate totals
    $total_items = 0;
    $total_amount = 0;
    
    foreach ($_POST['product_id'] as $index => $product_id) {
        $quantity = (int)$_POST['quantity'][$index];
        $price = (float)$_POST['price'][$index];
        
        $total_items += $quantity;
        $total_amount += ($quantity * $price);
    }
    
    $change_due = $amount_tendered - $total_amount;
    
    // Create transaction
    $receipt_no = generateReceiptNo();
    $stmt = $conn->prepare("INSERT INTO transactions 
        (receipt_no, total_items, total_amount, amount_tendered, change_due, 
         mode_of_payment, staff, customer, date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Completed')");
    $stmt->bind_param("sidddsss",
        $receipt_no,
        $total_items,
        $total_amount,
        $amount_tendered,
        $change_due,
        $mode_of_payment,
        $_SESSION['full_name'],
        $customer_name
    );
    $stmt->execute();
    
    // Get last inserted order ID
    $order_id = $conn->insert_id;

    // Insert order details
    foreach ($_POST['product_id'] as $index => $product_id) {
        $quantity = (int)$_POST['quantity'][$index];
        $price = (float)$_POST['price'][$index];
        
        $stmt = $conn->prepare("INSERT INTO order_details 
            (order_id, product_id, quantity, unique_price)
            VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
        $stmt->execute();
        
        // Update inventory
        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? 
               WHERE product_id = ?");
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();
    }

    $_SESSION['success'] = "Sale completed successfully! Receipt #$receipt_no";
    header("Location: sales.php");
    exit;
}

// Fetch products
$products_sql = "SELECT id, name, price FROM products";
$products_result = $conn->query($products_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <!-- Sales Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">New Sale</h4>
        </div>
        <div class="card-body">
            <form method="POST" id="salesForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" name="customer_name" class="form-control" 
                               placeholder="Customer Name (Optional)">
                    </div>
                    <div class="col-md-4">
                        <select name="mode_of_payment" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="POS">POS</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="amount_tendered" 
                               class="form-control" placeholder="Amount Tendered" 
                               step="0.01" required>
                    </div>
                </div>

                <!-- Product Items -->
                <div id="productItems">
                    <div class="row mb-3 product-row">
                        <div class="col-md-5">
                            <select name="product_id[]" class="form-control product-select" required>
                                <option value="">Select Product</option>
                                <?php while($product = $products_result->fetch_assoc()) { ?>
                                    <option value="<?= $product['id'] ?>" 
                                            data-price="<?= $product['price'] ?>">
                                        <?= htmlspecialchars($product['name']) ?> - 
                                        ₦<?= number_format($product['price'], 2) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="quantity[]" class="form-control quantity" 
                                   placeholder="Qty" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="price[]" class="form-control price" 
                                   placeholder="Price" readonly>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" id="addProduct" class="btn btn-secondary btn-sm mb-3">
                    <i class="bi bi-plus-circle"></i> Add Product
                </button>

                <div class="row">
                    <div class="col-md-4 offset-md-8">
                        <div class="input-group mb-3">
                            <span class="input-group-text">Total Items</span>
                            <input type="text" class="form-control" id="totalItems" readonly>
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Total Amount</span>
                            <input type="text" class="form-control" id="totalAmount" readonly>
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Change Due</span>
                            <input type="text" class="form-control" id="changeDue" readonly>
                        </div>
                    </div>
                </div>

                <button type="submit" name="create_sale" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle"></i> Complete Sale
                </button>
            </form>
        </div>
    </div>

    <!-- Transactions Table (Same as previous example) -->
    <!-- Include the transactions table code from previous answer here -->
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Add product row
    $('#addProduct').click(function() {
        const newRow = $('.product-row:first').clone();
        newRow.find('select').val('');
        newRow.find('input').val('');
        newRow.find('.price').val('');
        $('#productItems').append(newRow);
    });

    // Remove product row
    $(document).on('click', '.remove-item', function() {
        if($('.product-row').length > 1) {
            $(this).closest('.product-row').remove();
            calculateTotals();
        }
    });

    // Update price when product selected
    $(document).on('change', '.product-select', function() {
        const price = $(this).find(':selected').data('price');
        $(this).closest('.product-row').find('.price').val(price);
        calculateTotals();
    });

    // Auto-calculate totals
    function calculateTotals() {
        let totalItems = 0;
        let totalAmount = 0;
        
        $('.product-row').each(function() {
            const qty = parseInt($(this).find('.quantity').val()) || 0;
            const price = parseFloat($(this).find('.price').val()) || 0;
            
            totalItems += qty;
            totalAmount += qty * price;
        });
        
        const amountTendered = parseFloat($('input[name="amount_tendered"]').val()) || 0;
        const changeDue = amountTendered - totalAmount;
        
        $('#totalItems').val(totalItems);
        $('#totalAmount').val('₦' + totalAmount.toFixed(2));
        $('#changeDue').val('₦' + (changeDue >= 0 ? changeDue.toFixed(2) : '0.00'));
    }

    // Trigger calculations on input
    $(document).on('input', '.quantity, input[name="amount_tendered"]', calculateTotals);
});
</script>

</body>
</html>