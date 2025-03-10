<?php
session_start();
require '../Handler/db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username  = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password  = trim($_POST['password']);
    $confirm   = trim($_POST['confirm_password']);

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Hash the password before saving
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (username, password, first_name, last_name) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $first_name, $last_name);
            if ($stmt->execute()) {
                echo '<div class="alert alert-success">Register successful! Redirecting to login...</div>';
                header("Location: login.php");
                exit;
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Handle duplicate username error
            if ($e->getCode() === 1062) { // 1062 is the error code for duplicate entry
                $error = "Username already exists. Please choose a different username.";
            } else {
                // Handle other database errors
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container">
  <h2>Register</h2>
  <?php if ($error): ?>
    <!-- Trigger modal if there's an error -->
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        new bootstrap.Modal(document.getElementById('errorModal')).show();
      });
    </script>
  <?php endif; ?>
  <form method="POST" action="">
    <div class="mb-3">
      <input type="text" name="first_name" placeholder="First Name" class="form-control" required>
    </div>
    <div class="mb-3">
      <input type="text" name="last_name" placeholder="Last Name" class="form-control" required>
    </div>
    <div class="mb-3">
      <input type="text" name="username" placeholder="Username" class="form-control" required>
    </div>
    <div class="mb-3">
      <input type="password" name="password" placeholder="Password" class="form-control" required>
    </div>
    <div class="mb-3">
      <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Register</button>
  </form>
  <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="errorModalLabel">Error</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo $error; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
</body>
</html>