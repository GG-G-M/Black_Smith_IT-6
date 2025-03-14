INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `created_at`, `last_login`, `is_active`) VALUES
(1, '1', '$2y$10$GU4XID0nn7H6BZG3h.VN1.CcATqbt2E3jbjzRAGfDZJqfiozP5VOG', 'Gilgre Gene', 'Mantilla', '2025-03-12 05:08:45', '2025-03-12 05:09:24', 1),
(2, '2', '$2y$10$rrdKGVVa4whPV7s.FwcQR.EXr9DtUtdl02iyIifM0.c4dGX/LNRT6', 'Jan Angel', 'Ayala', '2025-03-12 05:08:54', '2025-03-12 05:08:54', 1),
(3, '3', '$2y$10$DiVXUS22RBhIWw/zCU4rzuKaCM461rje8LVJckyfyCnqUiS3drQZO', 'Josh Andrei', 'Magcalas', '2025-03-12 05:09:03', '2025-03-12 05:09:03', 1),
(4, '4', '$2y$10$55IjM6V5sFbOt8cj4KHlzeDTaNkfN2ELK.caqQ4mowC1BpQ6PW/.W', 'Jhon Jan Raven', 'Canedo', '2025-03-12 05:09:10', '2025-03-12 05:09:10', 1),
(5, '5', '$2y$10$.5UrWmZiYaoMaGst29W6yOBEukUsjHNrZvo5YmSRI2dE7W0Aff.lW', 'Earl', 'Fructose', '2025-03-12 05:09:21', '2025-03-12 05:09:21', 1);


INSERT INTO suppliers (supplier_name, supplier_info, address, created_by) VALUES
('MetalWorks Inc.', 'Supplier of high-quality metals', '123 Metal St, Industry City', 1),
('WoodCraft Ltd.', 'Supplier of premium wood materials', '456 Timber Ave, Forest Town', 2),
('PlasticWorld', 'Supplier of plastic materials', '789 Plastic Rd, Polymer City', 3),
('SteelMasters', 'Supplier of steel products', '101 Steel Blvd, Metal City', 4),
('TimberLand', 'Supplier of timber and wood products', '202 Wood Rd, Timber Town', 5);

INSERT INTO materials (supplier_id, material_type, quantity, created_by) VALUES
(1, 'Steel', 100, 1),
(1, 'Aluminum', 200, 2),
(2, 'Oak Wood', 150, 3),
(2, 'Pine Wood', 300, 4),
(3, 'Polycarbonate', 500, 5),
(4, 'Stainless Steel', 250, 1),
(5, 'Cedar Wood', 400, 2),
(1, 'Iron', 350, 3),
(2, 'Mahogany Wood', 200, 4),
(3, 'PVC', 450, 5),
(4, 'Copper', 300, 1),
(5, 'Birch Wood', 500, 2),
(1, 'Nylon', 700, 3),
(2, 'Titanium', 150, 4),
(3, 'Plywood', 800, 5);

INSERT INTO stock_material (supplier_id, material_id, quantity) VALUES
(1, 1, 50),  -- Steel
(2, 3, 100), -- Oak Wood
(3, 5, 200), -- Polycarbonate
(4, 6, 150), -- Stainless Steel
(5, 7, 300); -- Cedar Wood

INSERT INTO products (name, description, category, price, created_by) VALUES
('Steel Hammer', 'Durable steel hammer for construction', 'Tools', 25.99, 1),
('Oak Chair', 'Handcrafted oak wood chair', 'Accessories', 120.50, 2),
('Plastic Cup', 'Reusable plastic cup', 'Other', 5.99, 3),
('Stainless Steel Knife', 'Sharp stainless steel kitchen knife', 'Tools', 15.99, 4),
('Cedar Table', 'Elegant cedar wood dining table', 'Accessories', 250.00, 5),
('Polypropylene Container', 'Durable plastic storage container', 'Other', 12.99, 1),
('Iron Anvil', 'Heavy-duty iron anvil for blacksmithing', 'Tools', 200.00, 2),
('Mahogany Bookshelf', 'Classic mahogany wood bookshelf', 'Accessories', 180.00, 3),
('PVC Pipe', 'Flexible PVC pipe for plumbing', 'Other', 8.99, 4),
('Copper Wire', 'High-conductivity copper wire', 'Tools', 10.50, 5),
('Birch Stool', 'Lightweight birch wood stool', 'Accessories', 45.00, 1),
('Nylon Rope', 'Strong nylon rope for outdoor use', 'Other', 7.99, 2),
('Titanium Screwdriver', 'Durable titanium screwdriver set', 'Tools', 35.00, 3);

INSERT INTO product_materials (product_id, material_id) VALUES
(1, 1),  -- Steel Hammer uses Steel
(2, 3),  -- Oak Chair uses Oak Wood
(3, 5),  -- Plastic Cup uses Polycarbonate
(4, 6),  -- Stainless Steel Knife uses Stainless Steel
(5, 7),  -- Cedar Table uses Cedar Wood
(6, 8),  -- Polypropylene Container uses Polypropylene
(7, 9),  -- Iron Anvil uses Iron
(8, 10), -- Mahogany Bookshelf uses Mahogany Wood
(9, 11), -- PVC Pipe uses PVC
(10, 12),-- Copper Wire uses Copper
(11, 13),-- Birch Stool uses Birch Wood
(12, 14),-- Nylon Rope uses Nylon
(13, 15);-- Titanium Screwdriver uses Titanium


INSERT INTO stock_product (material_id, material_consumed, product_produced) VALUES
(1, 10, 1),  -- Steel used to produce Steel Hammer
(3, 5, 2),   -- Oak Wood used to produce Oak Chair
(5, 20, 3),  -- Polycarbonate used to produce Plastic Cup
(6, 15, 4),  -- Stainless Steel used to produce Stainless Steel Knife
(7, 8, 5);   -- Cedar Wood used to produce Cedar Table

INSERT INTO inventory (product_id, quantity, created_by) VALUES
(1, 50, 1),
(2, 20, 2),
(3, 100, 3),
(4, 75, 4),
(5, 30, 5),
(6, 200, 1),
(7, 10, 2),
(8, 25, 3),
(9, 150, 4),
(10, 80, 5),
(11, 40, 1),
(12, 300, 2),
(13, 15, 3);

INSERT INTO customer (customer_name, customer_contact, customer_address, created_by) VALUES
('ABC Corporation', '123-456-7890', '123 Business Rd, Metro City', 1),
('XYZ Enterprises', '987-654-3210', '456 Commerce St, Downtown', 2),
('Global Traders', '555-123-4567', '789 Trade Ave, Business Park', 3),
('Prime Suppliers', '444-555-6666', '101 Supply Blvd, Industrial Zone', 4),
('Elite Distributors', '777-888-9999', '202 Distribution Rd, Logistics Hub', 5);

INSERT INTO orders (customer_id, order_date, delivery_date, status, amount_paid) VALUES
(1, '2025-03-15', '2025-03-25', 'Completed', 1000.00),
(2, '2025-03-16', '2025-03-26', 'Pending', 750.00),
(3, '2025-03-17', '2025-03-27', 'Cancelled', 500.00),
(4, '2025-03-18', '2025-03-28', 'Completed', 1200.00),
(5, '2025-03-19', '2025-03-29', 'Pending', 900.00);

INSERT INTO order_details (order_id, product_id, quantity, unique_price) VALUES
(1, 1, 10, 25.99),  -- Steel Hammer for Order ID 1
(1, 2, 5, 120.50),  -- Oak Chair for Order ID 1
(2, 3, 20, 5.99),   -- Plastic Cup for Order ID 2
(2, 4, 15, 15.99),  -- Stainless Steel Knife for Order ID 2
(3, 5, 8, 250.00),  -- Cedar Table for Order ID 3
(3, 6, 30, 12.99),  -- Polypropylene Container for Order ID 3
(4, 7, 5, 200.00),  -- Iron Anvil for Order ID 4
(4, 8, 10, 180.00), -- Mahogany Bookshelf for Order ID 4
(5, 9, 25, 8.99),   -- PVC Pipe for Order ID 5
(5, 10, 20, 10.50); -- Copper Wire for Order ID 5

INSERT INTO invoice (user_id, invoice_date, customer_id, delivery_date, total_amount) VALUES
(1, '2025-03-15', 1, '2025-03-25', 1000.00),
(2, '2025-03-16', 2, '2025-03-26', 750.00),
(3, '2025-03-17', 3, '2025-03-27', 500.00),
(4, '2025-03-18', 4, '2025-03-28', 1200.00),
(5, '2025-03-19', 5, '2025-03-29', 900.00);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, amount) VALUES
(1, 1, 10, 25.99, 259.90),  -- Steel Hammer
(1, 2, 5, 120.50, 602.50),   -- Oak Chair
(2, 3, 20, 5.99, 119.80),    -- Plastic Cup
(2, 4, 15, 15.99, 239.85),   -- Stainless Steel Knife
(3, 5, 8, 250.00, 2000.00),  -- Cedar Table
(3, 6, 30, 12.99, 389.70),   -- Polypropylene Container
(4, 7, 5, 200.00, 1000.00),  -- Iron Anvil
(4, 8, 10, 180.00, 1800.00), -- Mahogany Bookshelf
(5, 9, 25, 8.99, 224.75),    -- PVC Pipe
(5, 10, 20, 10.50, 210.00);  -- Copper Wire

INSERT INTO returns (order_id, customer_id, product_id, return_date, reason, status, created_by) VALUES
(1, 1, 1, '2025-03-20', 'Defective product', 'Pending', 1),
(2, 2, 2, '2025-03-21', 'Wrong item delivered', 'Approved', 2),
(3, 3, 3, '2025-03-22', 'Customer changed mind', 'Rejected', 3),
(4, 4, 4, '2025-03-23', 'Damaged during delivery', 'Pending', 4),
(5, 5, 5, '2025-03-24', 'Product not as described', 'Approved', 5);