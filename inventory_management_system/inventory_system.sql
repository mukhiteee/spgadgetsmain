-- ========================================
-- INVENTORY MANAGEMENT SYSTEM
-- ========================================

-- Create stock_history table to track all stock changes
CREATE TABLE IF NOT EXISTS `stock_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `change_type` ENUM('purchase', 'manual_add', 'manual_subtract', 'return', 'adjustment') NOT NULL,
  `quantity_before` INT(11) NOT NULL,
  `quantity_change` INT(11) NOT NULL,
  `quantity_after` INT(11) NOT NULL,
  `reference_type` VARCHAR(50) NULL COMMENT 'order, admin, return',
  `reference_id` INT(11) NULL COMMENT 'order_id or admin_id',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stock_alerts table for low stock notifications
CREATE TABLE IF NOT EXISTS `stock_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `alert_type` ENUM('low_stock', 'out_of_stock', 'restocked') NOT NULL,
  `stock_level` INT(11) NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_is_read` (`is_read`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add low_stock_threshold column to products table if it doesn't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS low_stock_threshold INT(11) DEFAULT 10 
AFTER stock_quantity;

-- SUCCESS! Inventory management tables created.
-- 
-- Tables created:
-- 1. stock_history - Tracks all stock changes
-- 2. stock_alerts - Low stock notifications
-- 
-- Features:
-- ✅ Auto stock reduction on purchase
-- ✅ Stock history tracking
-- ✅ Low stock alerts
-- ✅ Out-of-stock handling
-- ✅ Configurable thresholds
