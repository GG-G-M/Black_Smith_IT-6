CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL
);

-- Suppliers Table
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_contact VARCHAR(255) NOT NULL,
    created_by INT,
    created_at DATE,
    updated_by INT,
    updated_at DATE,
    CONSTRAINT fk_supplier_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_supplier_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Materials Table with Foreign Key
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    items INT NOT NULL,
    created_by INT,
    created_at DATE,
    updated_by INT,
    updated_at DATE,
    CONSTRAINT fk_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    CONSTRAINT fk_materials_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_materials_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Stored Procedures
DELIMITER $$

CREATE PROCEDURE AddSupplier(
    IN in_supplier_name VARCHAR(255),
    IN in_supplier_contact VARCHAR(255),
    IN in_created_by INT
)
BEGIN
    INSERT INTO suppliers (supplier_name, supplier_contact, created_by, created_at, updated_by, updated_at)
    VALUES (in_supplier_name, in_supplier_contact, in_created_by, CURDATE(), in_created_by, CURDATE());
END $$

CREATE PROCEDURE EditSupplier(
    IN in_supplier_name VARCHAR(255),
    IN in_supplier_contact VARCHAR(255),
    IN in_supplier_id INT,
    IN in_updated_by INT
)
BEGIN
    UPDATE suppliers
    SET supplier_name = in_supplier_name,
        supplier_contact = in_supplier_contact,
        updated_by = in_updated_by,
        updated_at = CURDATE()
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
    IN in_items INT,
    IN in_created_by INT
)
BEGIN
    INSERT INTO materials (supplier_id, material_name, items, created_by, created_at, updated_by, updated_at)
    VALUES (in_supplier_id, in_material_name, in_items, in_created_by, CURDATE(), in_created_by, CURDATE());
END $$

CREATE PROCEDURE EditMaterial(
    IN in_material_name VARCHAR(255),
    IN in_supplier_id INT,
    IN in_items INT,
    IN in_id INT,
    IN in_updated_by INT
)
BEGIN
    UPDATE materials
    SET material_name = in_material_name,
        supplier_id = in_supplier_id,
        items = in_items,
        updated_by = in_updated_by,
        updated_at = CURDATE()
    WHERE id = in_id;
END $$

CREATE PROCEDURE DeleteMaterial(
    IN in_material_id INT
)
BEGIN
    DELETE FROM materials WHERE id = in_material_id;
END $$

DELIMITER ;