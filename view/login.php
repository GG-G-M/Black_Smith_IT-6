<?php
session_start();
require '../Handler/db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, password, first_name, last_name FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $first_name, $last_name);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            // Store user details in session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $first_name . " " . $last_name;

            // Update last_login timestamp
            $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("i", $id);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <!-- Left Side: Login -->
        <div class="row w-75 shadow-lg bg-white rounded p-4">
            <div class="col-md-6 d-flex flex-column justify-content-center">
                <h3 class="mb-3">Login</h3>
                
                <!-- Error message -->
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username*</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password*</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Login</button>
                </form>

                <p class="mt-3"><a href="register.php" class="text-dark">Create a new account</a></p>
            </div>

            <!-- Right Side: Image -->
            <div class="col-md-6 d-none d-md-block">
                <img src="../Image/monkey-monkey-smile.gif" class="img-fluid" alt="Monkey Image">
            </div>
        </div>
    </div>

</body>
</html>
