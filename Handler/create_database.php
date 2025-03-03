<?php
$host = "localhost"; 
$user = "root";        
$pass = "";            
$db = "inventory_system";


$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $db";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:Green;'>Database '$db' created successfully or already exists.</p>";
} else {
    echo "<p style='color:Red;'>Error creating database: " . $conn->error . "</p>";
}


$conn->select_db($db);
$sql = "CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL
);

-- Suppliers Table
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_contact VARCHAR(255) NOT NULL
);

-- Materials Table with Foreign Key
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    items INT NOT NULL,
    CONSTRAINT fk_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE
);
";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:Green;'>Table 'users' created successfully or already exists.</p>";
} else {
    echo "<p style='color:Red;'>Error creating table: " . $conn->error . "</p>";
}

$conn->close();
?>
