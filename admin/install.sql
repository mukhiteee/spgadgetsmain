-- ========================================
-- ADMIN PANEL DATABASE SETUP
-- ========================================

-- Create admin_users table for authentication
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin_sessions table for session management
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `session_token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_admin_id` (`admin_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin_activity_log table for tracking admin actions
CREATE TABLE IF NOT EXISTS `admin_activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` INT(11) NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_admin_id` (`admin_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Username: admin
-- Password: admin123 (CHANGE THIS IMMEDIATELY!)
-- Password hash created using: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@spgadgets.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- ========================================
-- IMPORTANT SECURITY NOTES:
-- 1. Change the default admin password immediately after first login!
-- 2. Use strong passwords (min 12 characters, mixed case, numbers, symbols)
-- 3. Enable HTTPS in production
-- 4. Regularly backup your database
-- ========================================

-- SUCCESS! Admin panel tables created.
-- Default Login Credentials:
-- Username: admin
-- Password: admin123
-- 
-- CHANGE PASSWORD IMMEDIATELY AFTER FIRST LOGIN!
