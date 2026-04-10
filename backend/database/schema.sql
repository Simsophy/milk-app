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


