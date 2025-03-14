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

INSERT INTO `orders` (`id`, `customer_id`, `order_date`, `delivery_date`, `status`, `amount_paid`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-03-14', '2025-03-30', 'Pending', 123.00, '2025-03-14 08:29:39', '2025-03-14 08:29:39'),
(2, 2, '2025-03-14', '2025-03-22', 'Pending', 500.00, '2025-03-14 08:30:03', '2025-03-14 08:44:56'),
(3, 3, '2025-03-14', '2025-03-22', 'Pending', 321.00, '2025-03-14 08:45:22', '2025-03-14 08:45:22'),
(4, 4, '2025-03-14', '2025-04-01', 'Pending', 520.00, '2025-03-14 08:45:58', '2025-03-14 08:45:58');

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `unique_price`) VALUES
(1, 1, 2, 10, 120.50),
(2, 1, 7, 25, 200.00),
(3, 1, 11, 14, 45.00),
(4, 2, 12, 32, 7.99),
(5, 2, 8, 13, 180.00),
(6, 2, 2, 42, 120.50),
(7, 3, 4, 32, 15.99),
(8, 4, 13, 4, 35.00),
(9, 4, 8, 2, 180.00),
(10, 4, 2, 1, 120.50);