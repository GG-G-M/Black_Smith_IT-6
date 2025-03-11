<?php
// Include session and database connection files
include '../Handler/session.php';
include '../Handler/db.php'; // Adjust the path to your database connection file

$full_name = $_SESSION['full_name']; // Get the user's full name

// Fetch users from the database
$users = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="content">
    <h3 class="text-primary">User Management - Hello <?php echo htmlspecialchars($full_name); ?></h3>
    <div class="table-container">
        <table id="userTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Created At</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td><?php echo $row['last_login'] ?: 'Never'; ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-user"
                                data-id="<?php echo $row['id']; ?>"
                                data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                data-firstname="<?php echo htmlspecialchars($row['first_name']); ?>"
                                data-lastname="<?php echo htmlspecialchars($row['last_name']); ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#editUserModal">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="editUserId" name="edit_user">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" id="editUsername" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" id="editFirstName" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="editLastName" name="last_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
        $('#userTable').DataTable();

        // Open Edit Modal & Populate Fields
        $(document).on("click", ".edit-user", function() {
            let userId = $(this).data("id");
            let username = $(this).data("username");
            let firstName = $(this).data("firstname");
            let lastName = $(this).data("lastname");

            $("#editUserId").val(userId);
            $("#editUsername").val(username);
            $("#editFirstName").val(firstName);
            $("#editLastName").val(lastName);

            $("#editUserModal").modal("show");
        });
    });
</script>
</body>
</html>
