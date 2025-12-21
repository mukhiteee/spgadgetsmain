-- ========================================
-- USER AUTHENTICATION SYSTEM
-- ========================================

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `verification_token` VARCHAR(255) NULL,
  `reset_token` VARCHAR(255) NULL,
  `reset_token_expiry` TIMESTAMP NULL,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_verification_token` (`verification_token`),
  INDEX `idx_reset_token` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_addresses table
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `address_type` ENUM('shipping', 'billing') DEFAULT 'shipping',
  `full_address` TEXT NOT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `country` VARCHAR(100) DEFAULT 'Nigeria',
  `postal_code` VARCHAR(20) NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_sessions table
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `session_token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user_id column to orders table (if not exists)
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS user_id INT(11) NULL 
AFTER id,
ADD INDEX IF NOT EXISTS idx_user_id (user_id),
ADD CONSTRAINT IF NOT EXISTS fk_orders_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- SUCCESS! User authentication tables created.
-- 
-- Tables created:
-- 1. users - User accounts
-- 2. user_addresses - Saved addresses
-- 3. user_sessions - Session management
-- 4. orders (updated) - Linked to users
-- 
-- Features:
-- ✅ User registration
-- ✅ Login/logout
-- ✅ Password reset
-- ✅ Email verification
-- ✅ Saved addresses
-- ✅ Order history
-- ✅ Guest checkout support
