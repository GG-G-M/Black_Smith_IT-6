<?php
// Include the session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; 

$full_name = $_SESSION['full_name']; 

// Handle Fetch Materials Request
if (isset($_POST['fetch_materials'])) {
    $product_id = $_POST['product_id'];

    $sql = "SELECT m.id, m.material_type 
            FROM materials m
            JOIN product_materials pm ON m.id = pm.material_id
            WHERE pm.product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }

    echo json_encode($materials);
    exit;
}

// Fetch active products with their materials
$active_product_sql = "SELECT p.id, p.name, p.description, p.category, p.price, 
                              GROUP_CONCAT(m.material_type SEPARATOR ', ') AS materials,
                              creator.last_name AS created_by, updater.last_name AS updated_by,
                              p.created_at, p.updated_at
                       FROM products p
                       LEFT JOIN product_materials pm ON p.id = pm.product_id
                       LEFT JOIN materials m ON pm.material_id = m.id
                       LEFT JOIN users AS creator ON p.created_by = creator.id
                       LEFT JOIN users AS updater ON p.updated_by = updater.id
                       WHERE p.is_active = 1
                       GROUP BY p.id";
$active_product_result = $conn->query($active_product_sql);

// Fetch inactive products
$inactive_product_sql = "SELECT p.id, p.name, p.description, p.category, p.price, 
                                GROUP_CONCAT(m.material_type SEPARATOR ', ') AS materials,
                                creator.last_name AS created_by, updater.last_name AS updated_by,
                                p.created_at, p.updated_at
                         FROM products p
                         LEFT JOIN product_materials pm ON p.id = pm.product_id
                         LEFT JOIN materials m ON pm.material_id = m.id
                         LEFT JOIN users AS creator ON p.created_by = creator.id
                         LEFT JOIN users AS updater ON p.updated_by = updater.id
                         WHERE p.is_active = 0
                         GROUP BY p.id";
$inactive_product_result = $conn->query($inactive_product_sql);

// Fetch materials for the dropdown
$material_sql = "SELECT id, material_type FROM materials";
$material_result = $conn->query($material_sql);

// Handle Add Product
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $material_ids = $_POST['material_id']; // This should be an array
    $created_by = $_SESSION['user_id']; 

    // Validate inputs
    if (empty($name) || empty($category) || empty($price) || empty($material_ids)) {
        $_SESSION['error'] = "Please fill all fields.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Insert the product
        $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdi", $name, $description, $category, $price, $created_by);
        $stmt->execute();
        $product_id = $conn->insert_id;

        // Insert materials into product_materials table
        foreach ($material_ids as $material_id) {
            $stmt = $conn->prepare("INSERT INTO product_materials (product_id, material_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $product_id, $material_id);
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        $_SESSION['success'] = "Product added successfully.";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Edit Product
if (isset($_POST['edit_product'])) {
    $product_id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $material_ids = $_POST['material_id'];
    $updated_by = $_SESSION['user_id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Update the product
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, category = ?, price = ?, updated_by = ? WHERE id = ?");
        $stmt->bind_param("sssdii", $name, $description, $category, $price, $updated_by, $product_id);
        $stmt->execute();

        // Delete existing materials for the product
        $stmt = $conn->prepare("DELETE FROM product_materials WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        // Insert new materials into product_materials table
        foreach ($material_ids as $material_id) {
            $stmt = $conn->prepare("INSERT INTO product_materials (product_id, material_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $product_id, $material_id);
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        $_SESSION['success'] = "Product updated successfully.";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Product
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];

    // Soft delete (set is_active to 0)
    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Actual Delete Product
if (isset($_POST['actual_delete_product'])) {
    $product_id = $_POST['product_id'];

    // Permanently delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Activate Product
if (isset($_POST['activate_product'])) {
    $product_id = $_POST['product_id'];

    // Set is_active to 1
    $stmt = $conn->prepare("UPDATE products SET is_active = 1 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
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
    <title>Product Management</title>

    <!-- Aesthetics -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Main -->
<div class="content">
    <h3 class="text-primary">Products</h3>
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle-fill"></i>&nbsp;Create Product
        </button>
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#categoryInfoModal">
        <i class="bi bi-info-circle"></i>&nbsp;Category's Info
    </button>
    </div>
    

    <!-- Data Table -->
    <!-- Active Products Table -->
    <h4 class="text-success">Active Products</h4>
    <div class="table-container">
        <table id="activeProductsTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Materials</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $active_product_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td><?php echo $row['materials']; ?></td>
                        <td>
                            <!-- Info Button -->
                            <button class="btn btn-primary btn-sm info_product-btn" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-name="<?php echo $row['name']; ?>" 
                                    data-description="<?php echo $row['description']; ?>" 
                                    data-category="<?php echo $row['category']; ?>" 
                                    data-price="<?php echo $row['price']; ?>"
                                    data-materials="<?php echo $row['materials']; ?>"
                                    data-created-by="<?php echo $row['created_by']; ?>"
                                    data-created-at="<?php echo $row['created_at']; ?>"
                                    data-updated-by="<?php echo $row['updated_by']; ?>"
                                    data-updated-at="<?php echo $row['updated_at']; ?>">
                                <i class="bi bi-info-circle"></i>&nbsp;Info
                            </button>
                            <!-- Edit Button -->
                            <button class="btn btn-success btn-sm edit_product-btn" 
                                    data-id="<?php echo $row['id']; ?>" 
                                    data-name="<?php echo $row['name']; ?>" 
                                    data-description="<?php echo $row['description']; ?>" 
                                    data-category="<?php echo $row['category']; ?>" 
                                    data-price="<?php echo $row['price']; ?>">
                                <i class="bi bi-pencil-square"></i>&nbsp;Edit
                            </button>
                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this product?');">
                                <input type="hidden" name="delete_product" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash3"></i>&nbsp;Deactivate
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Inactive Products Table -->
    <h4 class="text-danger">Inactive Products</h4>
    <div class="table-container">
        <table id="inactiveProductsTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Material</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $inactive_product_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td><?php echo $row['material_type']; ?></td>
                        <td>
                            <!-- Activate Button -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to activate this product?');">
                                <input type="hidden" name="activate_product" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle"></i>&nbsp;Activate
                                </button>
                            </form>
                            <!-- Actual Delete Button -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this product? This action cannot be undone.');">
                                <input type="hidden" name="actual_delete_product" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash3"></i>&nbsp;Delete Permanently
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category's Info Modal -->
<div class="modal fade" id="categoryInfoModal" tabindex="-1" aria-labelledby="categoryInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryInfoModalLabel">Category's Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tools -->
                <h5>Tools</h5>
                <ul>
                    <li>Hammers (Martilyo)</li>
                    <li>Chisels (Paet)</li>
                    <li>Tongs (Sipit)</li>
                    <li>Files (Kikil)</li>
                </ul>

                <!-- Farming Tools -->
                <h5>Farming Tools</h5>
                <ul>
                    <li>Bolos (Itak)</li>
                    <li>Sickles (Karit)</li>
                    <li>Hoes (Asarol)</li>
                    <li>Plowshares (Sudsod)</li>
                </ul>

                <!-- Household Items -->
                <h5>Household Items</h5>
                <ul>
                    <li>Cooking Pots (Kaldero)</li>
                    <li>Frying Pans (Kawali)</li>
                    <li>Fireplace Tools (Pang-uling)</li>
                    <li>Candle Holders (Patungan ng Kandila)</li>
                </ul>

                <!-- Knives & Blades -->
                <h5>Knives & Blades</h5>
                <ul>
                    <li>Kitchen Knives (Kutsilyo)</li>
                    <li>Utility Knives (Lanseta)</li>
                    <li>Bolos (Itak)</li>
                    <li>Machetes (Gulok)</li>
                </ul>

                <!-- Hardware & Repairs -->
                <h5>Hardware & Repairs</h5>
                <ul>
                    <li>Nails (Pako)</li>
                    <li>Hinges (Bisagra)</li>
                    <li>Chains (Kadena)</li>
                    <li>Locks (Kandado)</li>
                </ul>

                <!-- Other -->
                <h5>Other</h5>
                <ul>
                    <li>Custom-Made Items</li>
                    <li>Unique Requests</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Create Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Product Form -->
                <form method="POST" id="addProductForm">
                    <!-- Product Name -->
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control">
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Tools">Tools</option>
                            <option value="Farming Tools">Farming Tools</option>
                            <option value="Household Items">Household Items</option>
                            <option value="Knives & Blades">Knives & Blades</option>
                            <option value="Hardware & Repairs">Hardware & Repairs</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Price -->
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" class="form-control" step="0.01" required>
                    </div>

                    <!-- Materials Section -->
                    <div class="mb-3">
                        <label class="form-label">Materials</label>
                        <div id="materialFields">
                            <div class="row mb-2">
                                <div class="col">
                                    <select name="material_id[]" class="form-control" required>
                                        <option value="">Select Material</option>
                                        <?php while ($material = $material_result->fetch_assoc()) { ?>
                                            <option value="<?php echo $material['id']; ?>"><?php echo $material['material_type']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-danger btn-sm removeMaterialField" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="addMaterialField" class="btn btn-secondary btn-sm">
                            <i class="bi bi-plus"></i>&nbsp;Add Material
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="add_product" class="btn btn-success">+ Add</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editProductForm">
                    <!-- Hidden field for Product ID -->
                    <input type="hidden" name="id" id="editProductId">

                    <!-- Product Name -->
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="editProductName" class="form-control" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" id="editProductDescription" class="form-control">
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="editProductCategory" class="form-control" required>
                            <option value="Tools">Tools</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Price -->
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" id="editProductPrice" class="form-control" step="0.01" required>
                    </div>

                    <!-- Materials Section -->
                    <div class="mb-3">
                        <label class="form-label">Materials</label>
                        <div id="editMaterialFields">
                            <!-- Dynamic fields for materials will be added here -->
                        </div>
                        <button type="button" id="addEditMaterialField" class="btn btn-secondary btn-sm">
                            <i class="bi bi-plus"></i>&nbsp;Add Material
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="edit_product" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Info Product Modal -->
<div class="modal fade" id="infoProductModal" tabindex="-1" aria-labelledby="infoProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoProductModalLabel">Product Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>ID:</strong> <span id="infoProductId"></span></p>
                <p><strong>Product Name:</strong> <span id="infoProductName"></span></p>
                <p><strong>Description:</strong> <span id="infoProductDescription"></span></p>
                <p><strong>Category:</strong> <span id="infoProductCategory"></span></p>
                <p><strong>Price:</strong> <span id="infoProductPrice"></span></p>
                <p><strong>Materials:</strong> <span id="infoProductMaterials"></span></p>
                <p><strong>Created By:</strong> <span id="infoProductCreatedBy"></span></p>
                <p><strong>Created At:</strong> <span id="infoProductCreatedAt"></span></p>
                <p><strong>Updated By:</strong> <span id="infoProductUpdatedBy"></span></p>
                <p><strong>Updated At:</strong> <span id="infoProductUpdatedAt"></span></p>
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
    $('#productsTable').DataTable();

    // Add Material Field
    $("#addMaterialField").click(function() {
        let newField = `
            <div class="row mb-2">
                <div class="col">
                    <select name="material_id[]" class="form-control" required>
                        <option value="">Select Material</option>
                        <?php
                        $material_result->data_seek(0); // Reset pointer to the beginning
                        while ($material = $material_result->fetch_assoc()) { ?>
                            <option value="<?php echo $material['id']; ?>"><?php echo $material['material_type']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger btn-sm removeMaterialField">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $("#materialFields").append(newField);
    });

    // Remove Material Field
    $(document).on("click", ".removeMaterialField", function() {
        $(this).closest(".row").remove();
    });

    // Handle Edit Button Click
    $(document).on("click", ".edit_product-btn", function() {
        let productId = $(this).data("id");
        let productName = $(this).data("name");
        let productDescription = $(this).data("description");
        let productCategory = $(this).data("category");
        let productPrice = $(this).data("price");

        // Populate the Edit Modal
        $("#editProductId").val(productId);
        $("#editProductName").val(productName);
        $("#editProductDescription").val(productDescription);
        $("#editProductCategory").val(productCategory);
        $("#editProductPrice").val(productPrice);

    // Fetch materials for the product
    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: 'POST',
        data: { fetch_materials: true, product_id: productId },
        success: function(response) {
            let materials = JSON.parse(response);
            let materialFields = '';

    // Fetch all materials from PHP and store them in a JavaScript variable
        let allMaterials = [
            <?php
            $material_result->data_seek(0); // Reset pointer to the beginning
            while ($material = $material_result->fetch_assoc()) {
                echo "{ id: " . $material['id'] . ", material_type: '" . $material['material_type'] . "' },";
            }
            ?>
        ];

    // Loop through the materials associated with the product
        materials.forEach(material => {
            materialFields += `
                <div class="row mb-2">
                    <div class="col">
                        <select name="material_id[]" class="form-control" required>
                            <option value="">Select Material</option>
                            ${allMaterials.map(m => `
                                <option value="${m.id}" ${material.id == m.id ? 'selected' : ''}>
                                    ${m.material_type}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-danger btn-sm removeMaterialField">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        // Append the material fields to the modal
        $("#editMaterialFields").html(materialFields);
    }
});

    // Show the Edit Modal
    $("#editProductModal").modal("show");
        });

    // Add Material Field in Edit Modal
        $("#addEditMaterialField").click(function() {
            let newField = `
                <div class="row mb-2">
                    <div class="col">
                        <select name="material_id[]" class="form-control" required>
                            <option value="">Select Material</option>
                            <?php
                            $material_result->data_seek(0); // Reset pointer to the beginning
                            while ($material = $material_result->fetch_assoc()) { ?>
                                <option value="<?php echo $material['id']; ?>"><?php echo $material['material_type']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-danger btn-sm removeMaterialField">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            $("#editMaterialFields").append(newField);
        });

    // Remove Material Field in Edit Modal
        $(document).on("click", ".removeMaterialField", function() {
            $(this).closest(".row").remove();
        });

    // Handle Info Button Click
        $(document).on("click", ".info_product-btn", function() {
            let productId = $(this).data("id");
            let productName = $(this).data("name");
            let productDescription = $(this).data("description");
            let productCategory = $(this).data("category");
            let productPrice = $(this).data("price");
            let productMaterials = $(this).data("materials");
            let createdBy = $(this).data("created-by");
            let createdAt = $(this).data("created-at");
            let updatedBy = $(this).data("updated-by");
            let updatedAt = $(this).data("updated-at");

            // Populate the Info Modal
            $("#infoProductId").text(productId);
            $("#infoProductName").text(productName);
            $("#infoProductDescription").text(productDescription);
            $("#infoProductCategory").text(productCategory);
            $("#infoProductPrice").text(productPrice);
            $("#infoProductMaterials").text(productMaterials);
            $("#infoProductCreatedBy").text(createdBy);
            $("#infoProductCreatedAt").text(createdAt);
            $("#infoProductUpdatedBy").text(updatedBy);
            $("#infoProductUpdatedAt").text(updatedAt);

            // Show the Info Modal
            $("#infoProductModal").modal("show");
        });
    });
</script>
</body>
</html>