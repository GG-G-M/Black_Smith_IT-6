<?php
session_start();
require '../Handler/db.php';

$full_name = $_SESSION['full_name']; // Get the user's full name

// Fetch inventory with product and user details
$inventory_sql = "SELECT inventory.id, products.name AS product_name, inventory.quantity, 
                         creator.last_name AS created_by, updater.last_name AS updated_by,
                         inventory.created_at, inventory.updated_at
                  FROM inventory
                  JOIN products ON inventory.product_id = products.id
                  LEFT JOIN users AS creator ON inventory.created_by = creator.id
                  LEFT JOIN users AS updater ON inventory.updated_by = updater.id";
$inventory_result = $conn->query($inventory_sql);

if (!$inventory_result) {
    die("Error fetching inventory: " . $conn->error);
}

// Fetch products for the dropdown
$product_sql = "SELECT id, name FROM products";
$product_result = $conn->query($product_sql);
if (!$product_result) {
    die("Error fetching products: " . $conn->error);
}

// Handle Add Inventory
if (isset($_POST['add_inventory'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $material_ids = $_POST['material_id'];
    $material_consumed = $_POST['material_consumed'];

    // Validate inputs
    if (empty($product_id) || empty($quantity) || empty($material_ids) || empty($material_consumed)) {
        $_SESSION['error'] = "Please fill all fields.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Check if enough materials are available
    $enough_materials = true;
    foreach ($material_ids as $index => $material_id) {
        $consumed_quantity = $material_consumed[$index];

        // Check available quantity
        $check_sql = "SELECT quantity FROM materials WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $material_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $material = $check_result->fetch_assoc();

        if ($material['quantity'] < $consumed_quantity) {
            $enough_materials = false;
            $_SESSION['error'] = "Not enough " . $material['material_type'] . " available.";
            break;
        }
    }

    if ($enough_materials) {
        // Check if the product already exists in the inventory
        $check_inventory_sql = "SELECT id, quantity FROM inventory WHERE product_id = ?";
        $check_inventory_stmt = $conn->prepare($check_inventory_sql);
        $check_inventory_stmt->bind_param("i", $product_id);
        $check_inventory_stmt->execute();
        $check_inventory_result = $check_inventory_stmt->get_result();

        if ($check_inventory_result->num_rows > 0) {
            // Product exists, update the quantity
            $row = $check_inventory_result->fetch_assoc();
            $inventory_id = $row['id'];
            $new_quantity = $row['quantity'] + $quantity;

            $update_sql = "UPDATE inventory SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_quantity, $inventory_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Product does not exist, insert a new row
            $insert_sql = "INSERT INTO inventory (product_id, quantity) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $product_id, $quantity);
            $insert_stmt->execute();
            $insert_stmt->close();
        }

        // Log materials consumed in stock_product
        foreach ($material_ids as $index => $material_id) {
            $consumed_quantity = $material_consumed[$index];

            // Deduct consumed materials
            $update_sql = "UPDATE materials SET quantity = quantity - ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $consumed_quantity, $material_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Log the transaction in stock_product
            $log_sql = "INSERT INTO stock_product (material_id, material_consumed, product_produced) VALUES (?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("iii", $material_id, $consumed_quantity, $product_id);
            $log_stmt->execute();
            $log_stmt->close();
        }

        $_SESSION['success'] = "Inventory added successfully.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Edit Inventory
if (isset($_POST['edit_inventory'])) {
    $stmt = $conn->prepare("UPDATE inventory SET  quantity = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("iii",  $_POST['quantity'], $_SESSION['user_id'], $_POST['id']);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Inventory
if (isset($_POST['delete_inventory'])) {
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $_POST['inventory_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management</title>
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
    <h3 class="text-primary">Inventory - Hello <?php echo htmlspecialchars($full_name); ?></h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Add Inventory
        </button>
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#productionLogModal">
            <i class="bi bi-list-check"></i>&nbsp;Production Log
        </button>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <table id="inventoryTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="inventoryList">
                <?php while ($row = $inventory_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['product_name']; ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm info-inventory-btn"
                                data-id="<?php echo $row['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($row['product_name']); ?>"
                                data-quantity="<?php echo $row['quantity']; ?>"
                                data-created-by="<?php echo $row['created_by']; ?>"
                                data-created-at="<?php echo $row['created_at']; ?>"
                                data-updated-by="<?php echo $row['updated_by']; ?>"
                                data-updated-at="<?php echo $row['updated_at']; ?>">
                                <i class="bi bi-info-circle"></i>&nbsp;Info
                            </button>
                            <!-- Edit Button -->
                            <button class="btn btn-success btn-sm edit-inventory-btn" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-product-name="<?php echo htmlspecialchars($row['product_name']); ?>" 
                                    data-quantity="<?php echo $row['quantity']; ?>">
                                <i class="bi bi-pencil-square"></i>&nbsp;Edit
                            </button>
                            

                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this inventory?');">
                                <input type="hidden" name="delete_inventory" value="1">
                                <input type="hidden" name="inventory_id" value="<?php echo $row['id']; ?>">
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

<!-- Add Inventory Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInventoryModalLabel">Add Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Inventory Form -->
                <form method="POST">
                    <!-- Product Select -->
                    <select name="product_id" required class="form-control mb-2">
                        <option value="">Select Product</option>
                        <?php
                        $product_sql = "SELECT id, name FROM products";
                        $product_result = $conn->query($product_sql);
                        if ($product_result->num_rows > 0) {
                            while ($product = $product_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($product['id']) . '">' . htmlspecialchars($product['name']) . '</option>';
                            }
                        } else {
                            echo '<option value="">No products available</option>';
                        }
                        ?>
                    </select>

                    <!-- Quantity Input -->
                    <input type="number" name="quantity" placeholder="Quantity Produced" required class="form-control mb-2">

                    <!-- Materials Consumed Section -->
                    <div class="mb-3">
                        <h6>Materials Consumed</h6>
                        <div id="materialsConsumedSection">
                            <!-- Dynamic fields for materials consumed will be added here -->
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="addMaterialConsumed">
                            <i class="bi bi-plus"></i>&nbsp;Add Material
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="add_inventory" class="btn btn-success">+ Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Inventory Modal -->
<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <!-- Hidden field for Inventory ID -->
                    <input type="hidden" name="id" id="editInventoryId">
                    
                    <!-- Product Name (Read-only Input Field) -->
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" id="editProductName" class="form-control" readonly>
                    </div>
                    
                    <!-- Quantity Input -->
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="editQuantity" required class="form-control">
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" name="edit_inventory" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Info Inventory Modal -->
<div class="modal fade" id="infoInventoryModal" tabindex="-1" aria-labelledby="infoInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoInventoryModalLabel">Inventory Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>ID:</strong> <span id="infoInventoryId"></span></p>
                <p><strong>Product Name:</strong> <span id="infoInventoryProductName"></span></p>
                <p><strong>Quantity:</strong> <span id="infoInventoryQuantity"></span></p>
                <p><strong>Created By:</strong> <span id="infoInventoryCreatedBy"></span></p>
                <p><strong>Created At:</strong> <span id="infoInventoryCreatedAt"></span></p>
                <p><strong>Updated By:</strong> <span id="infoInventoryUpdatedBy"></span></p>
                <p><strong>Updated At:</strong> <span id="infoInventoryUpdatedAt"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Log Modal -->
<div class="modal fade" id="productionLogModal" tabindex="-1" aria-labelledby="productionLogModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionLogModalLabel">Production Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Transaction Log Table -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Material</th>
                            <th>Product</th>
                            <th>Consumed</th>
                            <th>Produced</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch production log from stock_product table
                        $log_sql = "SELECT sp.transaction_date, m.material_type, p.name AS product_name, sp.material_consumed, sp.product_produced
                                    FROM stock_product sp
                                    JOIN materials m ON sp.material_id = m.id
                                    JOIN products p ON sp.product_produced = p.id
                                    ORDER BY sp.transaction_date DESC";

                        $log_result = $conn->query($log_sql);
                        if ($log_result->num_rows > 0) {
                            while ($log = $log_result->fetch_assoc()) {
                                echo '
                                <tr>
                                    <td>' . htmlspecialchars($log['transaction_date']) . '</td>
                                    <td>' . htmlspecialchars($log['material_type']) . '</td>
                                    <td>' . htmlspecialchars($log['product_name']) . '</td>
                                    <td>' . htmlspecialchars($log['material_consumed']) . '</td>
                                    <td>' . htmlspecialchars($log['product_produced']) . '</td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5">No production transactions found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
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
        $('#inventoryTable').DataTable();

        // Handle Edit Button Click
        $(document).on("click", ".edit-inventory-btn", function() {
        let inventoryId = $(this).data("id");
        let productName = $(this).data("product-name"); // Add product name to data attributes
        let quantity = $(this).data("quantity");

        // Populate the Edit Modal
        $("#editInventoryId").val(inventoryId);
        $("#editProductName").val(productName); // Set product name in the read-only field
        $("#editQuantity").val(quantity);

        // Show the Edit Modal
        $("#editInventoryModal").modal("show");
        });
    });

    // Add Material Consumed Field
    $('#addMaterialConsumed').click(function() {
        $('#materialsConsumedSection').append(`
            <div class="row mb-2">
                <div class="col">
                    <select name="material_id[]" class="form-control" required>
                        <option value="">Select Material</option>
                        <?php
                        $material_sql = "SELECT id, material_type FROM materials";
                        $material_result = $conn->query($material_sql);
                        if ($material_result->num_rows > 0) {
                            while ($material = $material_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($material['id']) . '">' . htmlspecialchars($material['material_type']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col">
                    <input type="number" name="material_consumed[]" placeholder="Quantity Consumed" class="form-control" required>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger btn-sm removeMaterialConsumed">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `);
    });

    // Remove Material Consumed Field
    $(document).on('click', '.removeMaterialConsumed', function() {
        $(this).closest('.row').remove();
    });

     // Handle Info Button Click
    $(document).on("click", ".info-inventory-btn", function() {
        // Get data attributes from the button
        let inventoryId = $(this).data("id");
        let productName = $(this).data("product-name");
        let quantity = $(this).data("quantity");
        let createdBy = $(this).data("created-by");
        let createdAt = $(this).data("created-at");
        let updatedBy = $(this).data("updated-by");
        let updatedAt = $(this).data("updated-at");

        // Populate the modal with data
        $("#infoInventoryId").text(inventoryId);
        $("#infoInventoryProductName").text(productName);
        $("#infoInventoryQuantity").text(quantity);
        $("#infoInventoryCreatedBy").text(createdBy);
        $("#infoInventoryCreatedAt").text(createdAt);
        $("#infoInventoryUpdatedBy").text(updatedBy);
        $("#infoInventoryUpdatedAt").text(updatedAt);

        // Show the modal
        $("#infoInventoryModal").modal("show");
    });
</script>
</body>
</html>

<?php $conn->close(); ?>