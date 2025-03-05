<?php
// DB Connection
$conn = new mysqli("localhost", "root", "", "inventory_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch materials with supplier names
$materials_sql = "SELECT materials.id, materials.material_name, suppliers.supplier_name, materials.items 
                  FROM materials 
                  JOIN suppliers ON materials.supplier_id = suppliers.supplier_id";
$material_result = $conn->query($materials_sql);

// Fetch suppliers for forms
$supplier_sql = "SELECT * FROM suppliers";
$supplier_result = $conn->query($supplier_sql);

// Handle Add Supplier using stored procedure
if (isset($_POST['add_supplier'])) {
    if (!empty($_POST['supplier_name']) && !empty($_POST['supplier_contact'])) {
        $stmt = $conn->prepare("CALL AddSupplier(?, ?)");
        $stmt->bind_param("ss", $_POST['supplier_name'], $_POST['supplier_contact']);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']); // Prevent refresh duplication
    exit;
}

// Handle Edit Supplier using stored procedure
if (isset($_POST['edit_supplier'])) {
    $stmt = $conn->prepare("CALL EditSupplier(?, ?, ?)");
    $stmt->bind_param("ssi", $_POST['supplier_name'], $_POST['supplier_contact'], $_POST['supplier_id']);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// // Handle Delete Supplier using stored procedure
// if (isset($_POST['delete_supplier'])) {
//     if (!empty($_POST['supplier_id'])) {
//         $stmt = $conn->prepare("CALL DeleteSupplier(?)");
//         $stmt->bind_param("i", $_POST['supplier_id']);
//         $stmt->execute();
//         $stmt->close();
//     }
//     header("Location: " . $_SERVER['PHP_SELF']);
//     exit;
// }

// Handle Delete Material using stored procedure
if (isset($_POST['delete_supplier'])) {
    $stmt = $conn->prepare("CALL DeleteSupplier(?)");
    $stmt->bind_param("i", $_POST['supplier_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Add Material using stored procedure
if (isset($_POST['add_material'])) {
    if (!empty($_POST['supplier_id']) && !empty($_POST['material_name']) && !empty($_POST['items'])) {
        $stmt = $conn->prepare("CALL AddMaterial(?, ?, ?)");
        $stmt->bind_param("isi", $_POST['supplier_id'], $_POST['material_name'], $_POST['items']);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']); // Prevent refresh duplication
    exit;
}

// Handle Edit Material using stored procedure
if (isset($_POST['edit_material'])) {
    $stmt = $conn->prepare("CALL EditMaterial(?, ?, ?, ?)");
    $stmt->bind_param("siii", $_POST['material_name'], $_POST['supplier_id'], $_POST['items'], $_POST['id']);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Material using stored procedure
if (isset($_POST['delete_material'])) {
    $stmt = $conn->prepare("CALL DeleteMaterial(?)");
    $stmt->bind_param("i", $_POST['material_id']);
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
    <title>Materials Management</title>
    <!-- BootStrap, Icon, DataTable Link -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
<!-- Sidebar -->
<nav class="sidebar d-flex flex-column">
    <a href="dashboard.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-bookmark-dash-fill"></i><br>Dashboard</a>
    <a href="inventory.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-bag-check-fill"></i><br>Inventory</a>
    <a href="product.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-hammer"></i><br>Product</a>
    <a href="materials.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-nut-fill"></i><br>Materials</a>
    <a href="orders.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-basket-fill"></i><br>Orders</a>
    <a href="sales.php" class="nav-link" style="color:whitesmoke;"><i class="bi bi-receipt"></i><br>Sales</a>
    <button class="logout-btn"><a href="../Handler/logout_handler.php" style="color: white; text-decoration: none;"><i class="bi bi-box-arrow-left"></i>&nbsp;LogOut</a></button>
</nav>

<!-- Main Content-->
<div class="content">
    <h3 class="text-primary">Materials</h3>
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
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="materialsList">
                <?php while ($row = $material_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['material_name']; ?></td>
                        <td><?php echo $row['supplier_name']; ?></td>
                        <td><?php echo $row['items']; ?></td>
                        <td>
                            <button class="btn btn-success btn-sm edit_material-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-material="<?php echo $row['material_name']; ?>" 
                                data-supplier="<?php echo $row['supplier_name']; ?>" 
                                data-items="<?php echo $row['items']; ?>">
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
                    <input type="text" name="supplier_contact" placeholder="Supplier Contact" required class="form-control mb-2">
                    <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                </form>

                <hr>

                <!-- Suppliers Table -->
                <table class="table mt-3" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($supplier = $supplier_result->fetch_assoc()) { ?>
                            <tr id="supplier-<?php echo $supplier['supplier_id']; ?>">
                                <td><?php echo $supplier['supplier_id']; ?></td>
                                <td><?php echo $supplier['supplier_name']; ?></td>
                                <td><?php echo $supplier['supplier_contact']; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-supplier" 
                                            data-id="<?php echo $supplier['supplier_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-contact="<?php echo htmlspecialchars($supplier['supplier_contact'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                        <input type="hidden" name="delete_supplier" value="1">
                                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash3"></i>&nbsp;Delete
                                        </button>
                                    </form>
                                    <button class="btn btn-primary btn-sm info-supplier" 
                                            data-id="<?php echo $supplier['supplier_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-contact="<?php echo htmlspecialchars($supplier['supplier_contact'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-created-by="<?php echo $supplier['created_by']; ?>"
                                            data-created-at="<?php echo $supplier['created_at']; ?>"
                                            data-updated-by="<?php echo $supplier['updated_by']; ?>"
                                            data-updated-at="<?php echo $supplier['updated_at']; ?>">
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
                        <label class="form-label">Supplier Contact</label>
                        <input type="text" class="form-control" id="editSupplierContact" name="supplier_contact" required>
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
        <p><strong>Contact:</strong> <span id="infoSupplierContact"></span></p>
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
                                    echo '<option value="'.htmlspecialchars($supplier['supplier_id']).'">'.htmlspecialchars($supplier['supplier_name']).'</option>';
                                }
                            } else {
                                echo '<option value="">No suppliers available</option>';
                            }
                            ?>
                        </select>
                        
                        <input type="text" name="material_name" placeholder="Material Name" required class="form-control mb-2">
                        <input type="number" name="items" placeholder="Quantity" required class="form-control mb-2">
                        
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
                    
                    <!-- Material Name Input -->
                    <input type="text" name="material_name" id="editMaterialName" required class="form-control mb-2">
                    
                    <!-- Supplier Select -->
                    <select name="supplier_id" id="editSupplierId" required class="form-control mb-2">
                        <option value="">Select Supplier</option>
                        <?php 
                        $supplier_result->data_seek(0); 
                        while ($supplier = $supplier_result->fetch_assoc()) { ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    
                    <!-- Items Input -->
                    <input type="number" name="items" id="editItems" required class="form-control mb-2">
                    
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

//Edit Supplier Modal
$(document).on("click", ".edit-supplier", function() {
    let supplierId = $(this).data("id");
    let supplierName = $(this).data("name");
    let supplierContact = $(this).data("contact");

    $("#editSupplierId").val(supplierId);
    $("#editSupplierName").val(supplierName);
    $("#editSupplierContact").val(supplierContact);
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

$(document).on("click", ".info-supplier", function(){
    $("#infoSupplierId").text($(this).data("id"));
    $("#infoSupplierName").text($(this).data("name"));
    $("#infoSupplierContact").text($(this).data("contact"));
    $("#infoCreatedBy").text($(this).data("created-by") ? $(this).data("created-by") : "N/A");
    $("#infoCreatedAt").text($(this).data("created-at") ? $(this).data("created-at") : "N/A");
    $("#infoUpdatedBy").text($(this).data("updated-by") ? $(this).data("updated-by") : "N/A");
    $("#infoUpdatedAt").text($(this).data("updated-at") ? $(this).data("updated-at") : "N/A");
    $("#infoSupplierModal").modal("show");
});


            
            // Initialize DataTable
            $('#materialsTable').DataTable();

            // Add Material Form Submission
            $("#addMaterialForm").submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        location.reload(); 
                    }
                });
            });

        // Edit Material Button
        $(document).on("click", ".edit_material-btn", function() {
            let materialId = $(this).data("id");
            let materialName = $(this).data("material");
            let supplierId = $(this).data("supplier");
            let items = $(this).data("items");

            $("#editMaterialId").val(materialId);
            $("#editMaterialName").val(materialName);
            $("#editSupplierId").val(supplierId);
            $("#editItems").val(items);
            $("#editMaterialModal").modal("show");
        });

    });
</script>

</body>
</html>

<?php $conn->close(); ?>