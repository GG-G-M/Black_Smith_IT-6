<?php
include '../Handler/session.php';
$full_name = $_SESSION['full_name']; // Get the user's full name

// DB Connection
include '../Handler/db.php';

// Fetch products with material and user details
$product_sql = "SELECT products.id, products.name, products.description, products.category, products.price, 
                       materials.material_type, creator.last_name AS created_by, updater.last_name AS updated_by,
                       products.created_at, products.updated_at
                FROM products
                JOIN materials ON products.material_id = materials.id
                LEFT JOIN users AS creator ON products.created_by = creator.id
                LEFT JOIN users AS updater ON products.updated_by = updater.id";
$product_result = $conn->query($product_sql);

// Fetch materials for the dropdown
$material_sql = "SELECT id, material_type FROM materials";
$material_result = $conn->query($material_sql);

// Handle Add Product
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $material_id = $_POST['material_id'];
    $created_by = $_SESSION['user_id']; // Assuming you have a logged-in user

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, material_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdii", $name, $description, $category, $price, $material_id, $created_by);
    $stmt->execute();

    // Redirect to refresh the page
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
    $material_id = $_POST['material_id'];
    $updated_by = $_SESSION['user_id']; // Ensure this is set

    // Update the database
    $update_sql = "UPDATE products SET name = ?, description = ?, category = ?, price = ?, material_id = ?, updated_by = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssdiii", $name, $description, $category, $price, $material_id, $updated_by, $product_id);
    $update_stmt->execute();

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete Product
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
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
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <table id="productsTable" class="table table-hover">
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
            <tbody id="productsList">
                <?php while ($row = $product_result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td><?php echo $row['material_type']; ?></td>
                        <td>
                            <button class="btn btn-success btn-sm edit_product-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo $row['name']; ?>" 
                                data-description="<?php echo $row['description']; ?>" 
                                data-category="<?php echo $row['category']; ?>" 
                                data-price="<?php echo $row['price']; ?>"
                                data-material-id="<?php echo $row['name']; ?>">
                                    <i class="bi bi-pencil-square"></i>&nbsp;Edit
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="delete_product" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash3"></i>&nbsp;Delete
                                </button>
                            </form>
                            <button class="btn btn-primary btn-sm info_product-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo $row['name']; ?>" 
                                data-created-by="<?php echo $row['created_by']; ?>" 
                                data-created-at="<?php echo $row['created_at']; ?>" 
                                data-updated-by="<?php echo $row['updated_by']; ?>" 
                                data-updated-at="<?php echo $row['updated_at']; ?>">
                                <i class="bi bi-info-circle"></i>&nbsp;Info
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
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
                            <option value="Furniture">Furniture</option>
                            <option value="Tools">Tools</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Price -->
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" class="form-control" step="0.01" required>
                    </div>

                    <!-- Material Select -->
                    <div class="mb-3">
                        <label class="form-label">Material</label>
                        <select name="material_id" class="form-control" required>
                            <option value="">Select Material</option>
                            <?php while ($material = $material_result->fetch_assoc()) { ?>
                                <option value="<?php echo $material['id']; ?>"><?php echo $material['material_type']; ?></option>
                            <?php } ?>
                        </select>
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
                            <option value="Furniture">Furniture</option>
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

                    <!-- Material Select -->
                    <div class="mb-3">
                        <label class="form-label">Material</label>
                        <select name="material_id" id="editProductMaterialId" class="form-control" required>
                            <option value="">Select Material</option>
                            <?php
                            $material_result->data_seek(0); // Reset pointer to the beginning
                            while ($material = $material_result->fetch_assoc()) { ?>
                                <option value="<?php echo $material['id']; ?>"><?php echo $material['material_type']; ?></option>
                            <?php } ?>
                        </select>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#productsTable').DataTable();

        // Handle Edit Button Click
        $(document).on("click", ".edit_product-btn", function() {
            let productId = $(this).data("id");
            let productName = $(this).data("name");
            let productDescription = $(this).data("description");
            let productCategory = $(this).data("category");
            let productPrice = $(this).data("price");
            let productMaterialId = $(this).data("material-id");

            // Populate the Edit Modal
            $("#editProductId").val(productId);
            $("#editProductName").val(productName);
            $("#editProductDescription").val(productDescription);
            $("#editProductCategory").val(productCategory);
            $("#editProductPrice").val(productPrice);
            $("#editProductMaterialId").val(productMaterialId);

            // Show the Edit Modal
            $("#editProductModal").modal("show");
        });

        // Handle Info Button Click
        $(document).on("click", ".info_product-btn", function() {
            let productId = $(this).data("id");
            let productName = $(this).data("name");
            let createdBy = $(this).data("created-by");
            let createdAt = $(this).data("created-at");
            let updatedBy = $(this).data("updated-by");
            let updatedAt = $(this).data("updated-at");

            // Populate the Info Modal
            $("#infoProductId").text(productId);
            $("#infoProductName").text(productName);
            $("#infoCreatedBy").text(createdBy);
            $("#infoCreatedAt").text(createdAt);
            $("#infoUpdatedBy").text(updatedBy);
            $("#infoUpdatedAt").text(updatedAt);

            // Show the Info Modal
            $("#infoProductModal").modal("show");
        });
    });
</script>
</body>
</html>