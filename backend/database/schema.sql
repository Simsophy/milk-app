-- Milk App MySQL Schema (MySQL 8+)
CREATE DATABASE IF NOT EXISTS `milk_app`;
USE `milk_app`;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` enum('Fresh','Almond','Oat') NOT NULL,
  `stock` int DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bakong_wallet` varchar(100) DEFAULT NULL,
  `merchant_id` varchar(100) DEFAULT NULL,
  `app_id` varchar(100) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `role` enum('customer','seller','admin') DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `products` (`id`, `name`, `price`, `category`, `stock`, `image_url`) VALUES
(1, 'Fresh Whole Milk', 2.50, 'Fresh', 50, NULL),
(2, 'Organic Almond Milk', 4.00, 'Almond', 30, NULL),
(3, 'Premium Oat Milk', 4.50, 'Oat', 25, NULL)
ON DUPLICATE KEY UPDATE
`name`=VALUES(`name`),`price`=VALUES(`price`),`category`=VALUES(`category`),`stock`=VALUES(`stock`),`image_url`=VALUES(`image_url`);

-- Password below is placeholder from provided dump.
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$abcdefghijklmnopqrstuv', 'admin')
ON DUPLICATE KEY UPDATE
`username`=VALUES(`username`),`password`=VALUES(`password`),`role`=VALUES(`role`);


-- Bakong Payment Transactions
CREATE TABLE IF NOT EXISTS `bakong_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `bakong_txn_id` varchar(100) DEFAULT NULL,
  `order_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` enum('KHR','USD') DEFAULT 'KHR',
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `direction` enum('debit','credit') DEFAULT 'debit',
  `customer_wallet` varchar(100) DEFAULT NULL,
  `merchant_id` varchar(100) DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `bakong_txn_id` (`bakong_txn_id`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bakong_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- POS System Tables

-- Stock Logs - Audit trail for all inventory movements
CREATE TABLE IF NOT EXISTS `stock_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `quantity` int NOT NULL,
  `reference_id` int DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_logs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Orders - Main order records
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `bakong_transaction_id` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`bakong_transaction_id`) REFERENCES `bakong_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Order Items - Line items for each order
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Suppliers - Track inventory sources
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Product Stock Settings - Enhanced stock control
CREATE TABLE IF NOT EXISTS `product_stock_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL UNIQUE,
  `min_stock_level` int DEFAULT 5,
  `reorder_quantity` int DEFAULT 10,
  `default_supplier_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `default_supplier_id` (`default_supplier_id`),
  CONSTRAINT `product_stock_settings_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_settings_ibfk_2` FOREIGN KEY (`default_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Update products table to add timestamps if they don't exist
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Insert default suppliers and stock settings for existing products
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `created_at`) VALUES
(1, 'Premium Dairy Co', 'John Supplier', '+855123456789', 'supplier@dairy.kh', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `product_stock_settings` (`product_id`, `min_stock_level`, `reorder_quantity`, `default_supplier_id`) VALUES
(1, 10, 20, 1),
(2, 5, 15, 1),
(3, 5, 15, 1)
ON DUPLICATE KEY UPDATE `min_stock_level`=VALUES(`min_stock_level`),`reorder_quantity`=VALUES(`reorder_quantity`);


