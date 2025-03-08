<?php
include '../Handler/session.php';
$full_name = $_SESSION['full_name']; // Get the user's full name

// DB Connection
include '../Handler/db.php';

// Fetch materials with supplier names
$materials_sql = "SELECT materials.id, materials.material_type, suppliers.supplier_name, materials.quantity, 
                         creator.last_name AS created_by, updater.last_name AS updated_by,
                         materials.created_at, materials.updated_at
                  FROM materials
                  JOIN suppliers ON materials.supplier_id = suppliers.id
                  LEFT JOIN users AS creator ON materials.created_by = creator.id
                  LEFT JOIN users AS updater ON materials.updated_by = updater.id";
$material_result = $conn->query($materials_sql);

if (!$material_result) {
    die("Error fetching materials: " . $conn->error); // Add error handling
}

// Fetch suppliers with user details
$supplier_sql = "SELECT suppliers.id, suppliers.supplier_name, suppliers.supplier_info, 
                        creator.last_name AS created_by, updater.last_name AS updated_by,
                        suppliers.created_at, suppliers.updated_at
                 FROM suppliers
                 LEFT JOIN users AS creator ON suppliers.created_by = creator.id
                 LEFT JOIN users AS updater ON suppliers.updated_by = updater.id";
$supplier_result = $conn->query($supplier_sql);

if (!$supplier_result) {
    die("Error fetching suppliers: " . $conn->error); // Add error handling
}

// Handle Add Supplier
if (isset($_POST['add_supplier'])) {
    if (!empty($_POST['supplier_name']) && !empty($_POST['supplier_info'])) {
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, supplier_info, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $_POST['supplier_name'], $_POST['supplier_info'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Edit Supplier
if (isset($_POST['edit_supplier'])) {
    $stmt = $conn->prepare("UPDATE suppliers SET supplier_name = ?, supplier_info = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("ssii", $_POST['supplier_name'], $_POST['supplier_info'], $_SESSION['user_id'], $_POST['supplier_id']);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Supplier
if (isset($_POST['delete_supplier'])) {
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $_POST['supplier_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Add Material
if (isset($_POST['add_material'])) {
    if (!empty($_POST['supplier_id']) && !empty($_POST['material_type']) && !empty($_POST['quantity'])) {
        $stmt = $conn->prepare("INSERT INTO materials (supplier_id, material_type, quantity, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $_POST['supplier_id'], $_POST['material_type'], $_POST['quantity'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Edit Material
if (isset($_POST['edit_material'])) {
    $stmt = $conn->prepare("UPDATE materials SET material_type = ?, supplier_id = ?, quantity = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("siiii", $_POST['material_type'], $_POST['supplier_id'], $_POST['quantity'], $_SESSION['user_id'], $_POST['id']);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Material
if (isset($_POST['delete_material'])) {
    $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->bind_param("i", $_POST['material_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Increase/Decrease Quantity
if (isset($_POST['action'])) { // Fixed: Added missing closing parenthesis `)`
    $material_id = $_POST['material_id'];
    $action = $_POST['action'];

    // Fetch current quantity
    $sql = "SELECT quantity FROM materials WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $material_id);
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
    $update_sql = "UPDATE materials SET quantity = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_quantity, $material_id);
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
    <title>Materials Management</title>
    <!-- BootStrap, Icon, DataTable Link -->
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
    <h3 class="text-primary">Materials - Hello <?php echo htmlspecialchars($full_name); ?></h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMaterialModal"><i class="bi bi-wrench-adjustable-circle-fill"></i>&nbsp;Add Materials</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#suppliersModal"><i class="bi bi-person-fill-down"></i>&nbsp;Suppliers</button>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <table id="materialsTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material</th>
                    <th>Supplier</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="materialsList">
            <?php while ($row = $material_result->fetch_assoc()) { ?>
    <tr>
        <td>#<?php echo $row['id']; ?></td>
        <td><?php echo $row['material_type']; ?></td>
        <td><?php echo $row['supplier_name']; ?></td>
        <td><?php echo $row['quantity']; ?></td>
        <td>
            <!-- Plus Button -->
            <form method="POST" style="display:inline;">
                <input type="hidden" name="material_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="action" value="increase">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-plus"></i>
                </button>
            </form>

            <!-- Minus Button -->
            <form method="POST" style="display:inline;">
                <input type="hidden" name="material_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="action" value="decrease">
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-dash"></i>
                </button>
            </form>
             <button class="btn btn-primary btn-sm info-material-btn" 
                    data-id="<?php echo $row['id']; ?>" 
                    data-material="<?php echo htmlspecialchars($row['material_type'], ENT_QUOTES, 'UTF-8'); ?>" 
                    data-supplier="<?php echo htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                    data-quantity="<?php echo $row['quantity']; ?>"
                    data-created-by="<?php echo htmlspecialchars($row['created_by'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-created-at="<?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-updated-by="<?php echo htmlspecialchars($row['updated_by'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-updated-at="<?php echo htmlspecialchars($row['updated_at'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-info-circle"></i>&nbsp;Info
            </button>
            <button class="btn btn-success btn-sm edit_material-btn" 
                    data-id="<?php echo $row['id']; ?>" 
                    data-material="<?php echo $row['material_type']; ?>" 
                    data-supplier="<?php echo $row['supplier_name']; ?>" 
                    data-quantity="<?php echo $row['quantity']; ?>">
                <i class="bi bi-pencil-square"></i>&nbsp;Edit
            </button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this material?');">
                <input type="hidden" name="delete_material" value="1">
                <input type="hidden" name="material_id" value="<?php echo $row['id']; ?>">
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

<!-- Info Material Modal -->
<div class="modal fade" id="infoMaterialModal" tabindex="-1" aria-labelledby="infoMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoMaterialModalLabel">Material Info</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>ID:</strong> <span id="infoMaterialId"></span></p>
        <p><strong>Material Type:</strong> <span id="infoMaterialType"></span></p>
        <p><strong>Supplier:</strong> <span id="infoMaterialSupplier"></span></p>
        <p><strong>Quantity:</strong> <span id="infoMaterialQuantity"></span></p>
        <p><strong>Created By:</strong> <span id="infoMaterialCreatedBy"></span></p>
        <p><strong>Created At:</strong> <span id="infoMaterialCreatedAt"></span></p>
        <p><strong>Updated By:</strong> <span id="infoMaterialUpdatedBy"></span></p>
        <p><strong>Updated At:</strong> <span id="infoMaterialUpdatedAt"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Suppliers Modal -->
<div class="modal fade" id="suppliersModal" tabindex="-1" aria-labelledby="suppliersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suppliersModalLabel">Suppliers Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Supplier Form -->
                <form method="POST">
                    <input type="text" name="supplier_name" placeholder="Supplier Name" required class="form-control mb-2">
                    <input type="text" name="supplier_info" placeholder="Supplier Info" required class="form-control mb-2">
                    <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                </form>

                <hr>

                <!-- Suppliers Table -->
                <table class="table mt-3" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($supplier = $supplier_result->fetch_assoc()) { ?>
                            <tr id="supplier-<?php echo $supplier['id']; ?>">
                                <td><?php echo $supplier['id']; ?></td>
                                <td><?php echo $supplier['supplier_name']; ?></td>
                                <td><?php echo $supplier['supplier_info']; ?></td>
                                <td>
                                    
                                    <button class="btn btn-warning btn-sm edit-supplier" 
                                            data-id="<?php echo $supplier['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-info="<?php echo htmlspecialchars($supplier['supplier_info'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                        <input type="hidden" name="delete_supplier" value="1">
                                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash3"></i>&nbsp;Delete
                                        </button>
                                    </form>
                                    <button class="btn btn-primary btn-sm info-supplier" 
                                            data-id="<?php echo $supplier['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-info="<?php echo htmlspecialchars($supplier['supplier_info'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-created-by="<?php echo htmlspecialchars($supplier['created_by'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-created-at="<?php echo htmlspecialchars($supplier['created_at'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-updated-by="<?php echo htmlspecialchars($supplier['updated_by'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-updated-at="<?php echo htmlspecialchars($supplier['updated_at'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Info
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>  
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSupplierForm">
                    <input type="hidden" id="editSupplierId" name="supplier_id">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" id="editSupplierName" name="supplier_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier Info</label>
                        <input type="text" class="form-control" id="editSupplierInfo" name="supplier_info" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Info Supplier Modal -->
<div class="modal fade" id="infoSupplierModal" tabindex="-1" aria-labelledby="infoSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoSupplierModalLabel">Supplier Info</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>ID:</strong> <span id="infoSupplierId"></span></p>
        <p><strong>Supplier Name:</strong> <span id="infoSupplierName"></span></p>
        <p><strong>Info:</strong> <span id="infoSupplierInfo"></span></p>
        <p><strong>Created By:</strong> <span id="infoCreatedBy"></span></p>
        <p><strong>Created At:</strong> <span id="infoCreatedAt"></span></p>
        <p><strong>Updated By:</strong> <span id="infoUpdatedBy"></span></p>
        <p><strong>Updated At:</strong> <span id="infoUpdatedAt"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMaterialModalLabel">Add Materials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Material Form -->
                <form method="POST">
                    <select name="supplier_id" required class="form-control mb-2">
                        <option value="">Select Supplier</option>
                        <?php 
                        if ($supplier_result->num_rows > 0) {
                            $supplier_result->data_seek(0); 
                            while ($supplier = $supplier_result->fetch_assoc()) { 
                                echo '<option value="'.htmlspecialchars($supplier['id']).'">'.htmlspecialchars($supplier['supplier_name']).'</option>';
                            }
                        } else {
                            echo '<option value="">No suppliers available</option>';
                        }
                        ?>
                    </select>
                    
                    <select name="material_type" required class="form-control mb-2">
                        <option value="">Select Material Type</option>
                        <option value="Wood">Wood</option>
                        <option value="Metal">Metal</option>
                        <option value="Plastic">Plastic</option>
                        <option value="Other">Other</option>
                    </select>
                    
                    <input type="number" name="quantity" placeholder="Quantity" required class="form-control mb-2">
                    
                    <button type="submit" name="add_material" class="btn btn-success">+ Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Material Modal -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <!-- Hidden field for Material ID -->
                    <input type="hidden" name="id" id="editMaterialId">
                    
                    <!-- Material Type Select -->
                    <select name="material_type" id="editMaterialType" required class="form-control mb-2">
                        <option value="">Select Material Type</option>
                        <option value="Wood">Wood</option>
                        <option value="Metal">Metal</option>
                        <option value="Plastic">Plastic</option>
                        <option value="Other">Other</option>
                    </select>
                    
                    <!-- Supplier Select -->
                    <select name="supplier_id" id="editSupplierId" required class="form-control mb-2">
                        <option value="">Select Supplier</option>
                        <?php 
                        $supplier_result->data_seek(0); 
                        while ($supplier = $supplier_result->fetch_assoc()) { ?>
                            <option value="<?php echo $supplier['id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    
                    <!-- Quantity Input -->
                    <input type="number" name="quantity" id="editQuantity" required class="form-control mb-2">
                    
                    <!-- Submit Button -->
                    <button type="submit" name="edit_material" class="btn btn-success">Update</button>
                </form>
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
        $('#materialsTable').DataTable();

        // Edit Supplier Modal
        $(document).on("click", ".edit-supplier", function() {
            let supplierId = $(this).data("id");
            let supplierName = $(this).data("name");
            let supplierInfo = $(this).data("info");

            $("#editSupplierId").val(supplierId);
            $("#editSupplierName").val(supplierName);
            $("#editSupplierInfo").val(supplierInfo);
            $("#editSupplierModal").modal("show");
        });

        // Handle Edit Supplier Form Submission
        $("#editSupplierForm").submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: "",
                type: "POST",
                data: $(this).serialize() + "&edit_supplier=1",
                success: function(response) {
                    location.reload(); 
                }
            });
        });

        // Info Supplier Modal
        $(document).on("click", ".info-supplier", function(){
            $("#infoSupplierId").text($(this).data("id"));
            $("#infoSupplierName").text($(this).data("name"));
            $("#infoSupplierInfo").text($(this).data("info"));
            $("#infoCreatedBy").text($(this).data("created-by") ? $(this).data("created-by") : "N/A");
            $("#infoCreatedAt").text($(this).data("created-at") ? $(this).data("created-at") : "N/A");
            $("#infoUpdatedBy").text($(this).data("updated-by") ? $(this).data("updated-by") : "N/A");
            $("#infoUpdatedAt").text($(this).data("updated-at") ? $(this).data("updated-at") : "N/A");
            $("#infoSupplierModal").modal("show");
        });
        // Edit Material Button
        $(document).on("click", ".edit_material-btn", function() {
            let materialId = $(this).data("id");
            let materialType = $(this).data("material");
            let supplierId = $(this).data("supplier");
            let quantity = $(this).data("quantity");

            $("#editMaterialId").val(materialId);
            $("#editMaterialType").val(materialType);
            $("#editSupplierId").val(supplierId);
            $("#editQuantity").val(quantity);
            $("#editMaterialModal").modal("show");
        });
    });

    $(document).on("click", ".info-material-btn", function() {
    // Populate the modal with data attributes
        $("#infoMaterialId").text($(this).data("id"));
        $("#infoMaterialType").text($(this).data("material"));
        $("#infoMaterialSupplier").text($(this).data("supplier"));
        $("#infoMaterialQuantity").text($(this).data("quantity"));
        $("#infoMaterialCreatedBy").text($(this).data("created-by") ? $(this).data("created-by") : "N/A");
        $("#infoMaterialCreatedAt").text($(this).data("created-at") ? $(this).data("created-at") : "N/A");
        $("#infoMaterialUpdatedBy").text($(this).data("updated-by") ? $(this).data("updated-by") : "N/A");
        $("#infoMaterialUpdatedAt").text($(this).data("updated-at") ? $(this).data("updated-at") : "N/A");

    // Show the modal
    $("#infoMaterialModal").modal("show");
});

$(document).ready(function() {
    // Handle Edit Button Click
    $(document).on("click", ".edit_material-btn", function() {
        let materialId = $(this).data("id");
        let materialType = $(this).data("material");
        let supplierId = $(this).data("supplier");
        let quantity = $(this).data("quantity");

        // Populate the Edit Modal
        $("#editMaterialId").val(materialId);
        $("#editMaterialType").val(materialType);
        $("#editSupplierId").val(supplierId);
        $("#editQuantity").val(quantity);

        // Show the Edit Modal
        $("#editMaterialModal").modal("show");
    });

    // Handle Plus and Minus Button Clicks
    $(document).on("click", ".update-quantity", function() {
        let materialId = $(this).data("id");
        let action = $(this).data("action");

        // Send AJAX request
        $.ajax({
            url: "update_quantity.php",
            type: "POST",
            data: {
                material_id: materialId,
                action: action
            },
            success: function(response) {
                // Update the quantity in the table
                let quantityCell = $("#material-" + materialId).find(".quantity");
                quantityCell.text(response);
            },
            error: function(xhr, status, error) {
                alert("Error: " + error);
            }
        });
    });
});
</script>

</body>
</html>

<?php $conn->close(); ?>