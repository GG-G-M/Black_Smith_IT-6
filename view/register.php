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

        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (username, password, first_name, last_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $first_name, $last_name);
        if ($stmt->execute()) {
            // // Set session and redirect to dashboard
            // $_SESSION['user_id'] = $conn->insert_id;
            // $_SESSION['username'] = $username;
            // $_SESSION['full_name'] = $full_name;
            echo '<div class="alert alert-success">Register successful! Redirecting to login...</div>';
            header("Location: login.php");
            exit;
        } else {
            $error = "Registration failed: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
  <h2>Register</h2>
  <?php if ($error) echo '<div class="alert alert-danger">' . $error . '</div>'; ?>
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
</body>
</html>
