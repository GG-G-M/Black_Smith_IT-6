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
    die("Error fetching inventory: " . $conn->error); // Add error handling
}

// Fetch products for the dropdown
$product_sql = "SELECT id, name FROM products";
$product_result = $conn->query($product_sql);

if (!$product_result) {
    die("Error fetching products: " . $conn->error); // Add error handling
}

// Handle Add Inventory
if (isset($_POST['add_inventory'])) {
    if (!empty($_POST['product_id']) && !empty($_POST['quantity'])) {
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $_POST['product_id'], $_POST['quantity'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
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

// Handle Increase/Decrease Quantity
if (isset($_POST['action'])) {
    $inventory_id = $_POST['inventory_id'];
    $action = $_POST['action'];

    // Fetch current quantity
    $sql = "SELECT quantity FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_quantity = $row['quantity'];

    // Update quantity based on action
    if ($action === "increase") {
        $new_quantity = $current_quantity + 1;
    } elseif ($action === "decrease") {
        $new_quantity = $current_quantity - 1;
    }

    // Ensure quantity doesn't go below 0
    if ($new_quantity < 0) {
        $new_quantity = 0;
    }

    // Update the database
    $update_sql = "UPDATE inventory SET quantity = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_quantity, $inventory_id);
    $update_stmt->execute();

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
                            <!-- Add Button -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="inventory_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="increase">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-plus"></i>&nbsp;Add
                                </button>
                            </form>

                            <!-- Remove Button -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="inventory_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="decrease">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="bi bi-dash"></i>&nbsp;Remove
                                </button>
                            </form>
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
                    <select name="product_id" id="productSelect" required class="form-control mb-2">
                        <option value="">Select Product</option>
                        <?php
                        if ($product_result->num_rows > 0) {
                            $product_result->data_seek(0);
                            while ($product = $product_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($product['id']) . '">' . htmlspecialchars($product['name']) . '</option>';
                            }
                        } else {
                            echo '<option value="">No products available</option>';
                        }
                        ?>
                    </select>

                    <!-- Quantity Input -->
                    <input type="number" name="quantity" placeholder="Quantity" required class="form-control mb-2">

                    <!-- Materials Consumed Section -->
                    <div id="materialsSection" class="mb-3">
                        <h6>Materials Consumed</h6>
                        <div id="materialsList"></div>
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