CREATE DATABASE IF NOT EXISTS inventory_system;
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

-- Stored Procedure--

DELIMITER $$

CREATE PROCEDURE AddSupplier(
    IN in_supplier_name VARCHAR(255),
    IN in_supplier_contact VARCHAR(255)
)
BEGIN
    INSERT INTO suppliers (supplier_name, supplier_contact)
    VALUES (in_supplier_name, in_supplier_contact);
END $$

CREATE PROCEDURE EditSupplier(
    IN in_supplier_name VARCHAR(255),
    IN in_supplier_contact VARCHAR(255),
    IN in_supplier_id INT
)
BEGIN
    UPDATE suppliers
    SET supplier_name = in_supplier_name,
        supplier_contact = in_supplier_contact
    WHERE supplier_id = in_supplier_id;
END $$

CREATE PROCEDURE DeleteSupplier(
    IN in_supplier_id INT
)
BEGIN
    DELETE FROM suppliers WHERE supplier_id = in_supplier_id;
END $$

CREATE PROCEDURE AddMaterial(
    IN in_supplier_id INT,
    IN in_material_name VARCHAR(255),
    IN in_items INT
)
BEGIN
    INSERT INTO materials (supplier_id, material_name, items)
    VALUES (in_supplier_id, in_material_name, in_items);
END $$

CREATE PROCEDURE EditMaterial(
    IN in_material_name VARCHAR(255),
    IN in_supplier_id INT,
    IN in_items INT,
    IN in_id INT
)
BEGIN
    UPDATE materials
    SET material_name = in_material_name,
        supplier_id = in_supplier_id,
        items = in_items
    WHERE id = in_id;
END $$

CREATE PROCEDURE DeleteMaterial(
    IN in_material_id INT
)
BEGIN
    DELETE FROM materials WHERE id = in_material_id;
END $$

DELIMITER ;

