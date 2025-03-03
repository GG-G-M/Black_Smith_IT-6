<?php
include 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if the username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already exists. Choose a different username.";
        } else {
            // Proceed with inserting the new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $username, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registration successful! <a href='login.php'>Login here</a>";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
