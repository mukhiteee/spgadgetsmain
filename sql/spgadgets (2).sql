-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2025 at 07:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spgadgets`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('billing','shipping','both') DEFAULT 'both',
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'Nigeria',
  `postal_code` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `role` enum('super_admin','admin','manager') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@spgadgets.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active', NULL, '2025-12-25 15:21:44', '2025-12-25 15:21:44');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-18 02:06:22'),
(2, 2, 'logout', NULL, NULL, 'Admin logged out', '::1', '2025-12-18 02:08:05'),
(3, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-18 20:55:36'),
(4, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-18 22:12:59'),
(5, 2, 'logout', NULL, NULL, 'Admin logged out', '::1', '2025-12-18 22:14:39'),
(6, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-18 22:27:55'),
(7, 2, 'logout', NULL, NULL, 'Admin logged out', '::1', '2025-12-18 22:28:02'),
(8, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-19 10:57:00'),
(9, 2, 'login', NULL, NULL, 'Admin logged in', '10.170.55.183', '2025-12-20 11:38:07'),
(10, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-24 18:08:07'),
(11, 2, 'logout', NULL, NULL, 'Admin logged out', '::1', '2025-12-24 18:10:06'),
(12, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-25 08:49:54'),
(13, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-25 14:11:35'),
(14, 2, 'logout', NULL, NULL, 'Admin logged out', '::1', '2025-12-25 14:43:15'),
(15, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-25 14:45:58'),
(16, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-25 21:57:37'),
(17, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-26 15:21:09'),
(18, 2, 'login', NULL, NULL, 'Admin logged in', '::1', '2025-12-26 18:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(5, 2, 'db59210f1dfb808c94887dff4ad719a149cb1254330cbf42f750806d3570bd74', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-19 11:57:00', '2025-12-19 10:57:00'),
(6, 2, '1aa9b1dbba72fb9e5d90d3a5886229c577b42d1c01b758ca0cce4204595c2416', '10.170.55.183', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2025-12-20 12:38:07', '2025-12-20 11:38:07'),
(8, 2, 'ec9c13caba15d2ec92d2f872fc8402af969c8e272d4dd46d361284570f1e8a33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 09:49:54', '2025-12-25 08:49:54'),
(10, 2, 'b36004f1e0b1743b2d6e61710bc45df66f9160a08df6b9d12f1f8b48ae370630', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 15:45:58', '2025-12-25 14:45:58'),
(11, 2, 'b27b43908457e06c366282ac2a49ea6e4c26443ac979cc6ca3031f7ca413a425', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 22:57:37', '2025-12-25 21:57:37'),
(12, 2, '0665da0ee39ac419fe34352295b1d4d1a27c7ca8bf0a37b68b23979ee69f25a2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:21:09', '2025-12-26 15:21:09'),
(13, 2, '24ea92e6fe716a51bd20def552a07532b61e75db8a26f3e3cd9ec1d2e755c7f0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:08:44', '2025-12-26 18:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@spgadgets.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 1, NULL, '2025-12-18 02:01:02', '2025-12-18 02:01:02'),
(2, 'Mukhiteee', 'mukhiteee@gmail.com', '$2y$10$mNBdUQDAYihwyFIqEH7Og.U/liAcx5akehBexoLRRcvz9WT4I/uhG', 'Mukhtar', 'super_admin', 1, '2025-12-26 18:08:44', '2025-12-18 02:06:08', '2025-12-26 18:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Laptops', 'laptops', 'High-performance laptops for work and gaming', NULL, NULL, 1, 'active', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(2, 'Phones', 'phones', 'Latest smartphones and mobile devices', NULL, NULL, 2, 'active', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(3, 'Tablets', 'tablets', 'iPads and Android tablets', NULL, NULL, 3, 'active', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(4, 'Accessories', 'accessories', 'Tech accessories and peripherals', NULL, NULL, 4, 'active', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(5, 'Gaming', 'gaming', 'Gaming consoles and accessories', NULL, NULL, 5, 'active', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(6, 'Audio', 'audio', 'Headphones, speakers, and audio equipment', NULL, NULL, 6, 'active', '2025-12-25 15:21:44', '2025-12-25 15:21:44');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order_amount` decimal(10,2) DEFAULT 0.00,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `user_limit` int(11) DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `status` enum('active','unsubscribed') DEFAULT 'active',
  `verification_token` varchar(100) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `shipping_address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `city`, `state`, `payment_method`, `subtotal`, `shipping_fee`, `tax`, `total_amount`, `order_status`, `payment_status`, `order_notes`, `created_at`, `updated_at`) VALUES
(1, 'SPG-20251216-716E99', 'Mukhtar Abdulhamid', 'mukhiteee@gmail.com', '+2349025948400', 'No 4, Behind Anglican Church, Dutse Alhaji. Abuja', 'Abuja', 'FCT', 'bank_transfer', 2350000.00, 5000.00, 176250.00, 2531250.00, 'pending', 'pending', '', '2025-12-16 09:22:15', '2025-12-16 09:31:08'),
(2, 'SPG-20251218-9897D3', 'Mukhtar Abdulhamid', 'mukhiteee@gmail.com', '+2349025948400', 'No 4, Behind Anglican Church, Dutse Alhaji. Abuja', 'Abuja', 'FCT', 'cash_on_delivery', 70000.00, 5000.00, 5250.00, 80250.00, 'pending', 'pending', '', '2025-12-18 01:30:17', '2025-12-18 01:30:17'),
(3, 'SPG-20251218-43F678', 'Mukhtar Abdulhamid', 'mukhiteee@gmail.com', '+2349025948400', 'No 4, Behind Anglican Church, Dutse Alhaji. Abuja', 'Abuja', 'FCT', 'cash_on_delivery', 7230000.00, 5000.00, 542250.00, 7777250.00, 'pending', 'pending', '', '2025-12-18 01:40:52', '2025-12-25 14:20:46');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `before_order_insert` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_brand` varchar(100) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_brand`, `product_price`, `quantity`, `subtotal`, `created_at`) VALUES
(1, 1, 1, 'iPhone 15 Pro Max 256GB', 'Apple', 2350000.00, 1, 2350000.00, '2025-12-16 09:22:15'),
(2, 2, 63, 'Philips Hue White & Color Bulb', 'Philips', 35000.00, 2, 70000.00, '2025-12-18 01:30:17'),
(3, 3, 1, 'iPhone 15 Pro Max 256GB', 'Apple', 2350000.00, 1, 2350000.00, '2025-12-18 01:40:52'),
(4, 3, 2, 'Samsung Galaxy S24 Ultra 512GB', 'Samsung', 2150000.00, 1, 2150000.00, '2025-12-18 01:40:52'),
(5, 3, 55, 'Sennheiser HD 660S2 Open-Back', 'Sennheiser', 750000.00, 1, 750000.00, '2025-12-18 01:40:52'),
(6, 3, 22, 'Samsung Galaxy S23 FE', 'Samsung', 990000.00, 2, 1980000.00, '2025-12-18 01:40:52');

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `after_order_item_insert` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET stock_quantity = stock_quantity - NEW.quantity,
        sales_count = sales_count + NEW.quantity
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `item_condition` enum('new','used','refurbished') NOT NULL DEFAULT 'new',
  `image` varchar(255) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 10,
  `average_rating` decimal(2,1) DEFAULT 0.0,
  `review_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `category`, `description`, `price`, `item_condition`, `image`, `stock_quantity`, `low_stock_threshold`, `average_rating`, `review_count`, `created_at`) VALUES
(1, 'iPhone 15 Pro Max 256GB', 'Apple', 'Phones', 'High-quality electronics product with excellent features and reliability. Perfect for everyday use with advanced technology and durable construction.', 2350000.00, 'new', 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=500', 13, 10, 0.0, 0, '2025-12-15 14:20:16'),
(2, 'Samsung Galaxy S24 Ultra 512GB', 'Samsung', 'Phones', 'Premium device offering superior performance and cutting-edge technology. Built with quality materials for long-lasting durability.', 2150000.00, 'new', 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=500', 11, 10, 0.0, 0, '2025-12-15 14:20:16'),
(3, 'Google Pixel 8 Pro 128GB', 'Google', 'Phones', NULL, 1750000.00, 'new', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500', 8, 10, 0.0, 0, '2025-12-15 14:20:16'),
(4, 'iPhone 14 128GB', 'Apple', 'Phones', NULL, 1120000.00, 'refurbished', 'https://images.unsplash.com/photo-1592286927505-b0e2cc3dd8bf?w=500', 22, 10, 0.0, 0, '2025-12-15 14:20:16'),
(5, 'Xiaomi 14T 256GB', 'Xiaomi', 'Phones', NULL, 890000.00, 'new', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500', 35, 10, 0.0, 0, '2025-12-15 14:20:16'),
(6, 'Tecno Phantom X2 Pro', 'Tecno', 'Phones', NULL, 785000.00, 'new', 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=500', 40, 10, 0.0, 0, '2025-12-15 14:20:16'),
(7, 'Infinix Note 40 Pro', 'Infinix', 'Phones', NULL, 350000.00, 'new', 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?w=500', 55, 10, 0.0, 0, '2025-12-15 14:20:16'),
(8, 'Samsung Galaxy A55 5G', 'Samsung', 'Phones', NULL, 480000.00, 'new', 'https://images.unsplash.com/photo-1567581935884-3349723552ca?w=500', 30, 10, 0.0, 0, '2025-12-15 14:20:16'),
(9, 'iPhone 13 Pro 256GB', 'Apple', 'Phones', NULL, 1550000.00, 'used', 'https://images.unsplash.com/photo-1632661674596-df8be070a5c5?w=500', 9, 10, 0.0, 0, '2025-12-15 14:20:16'),
(10, 'Tecno Spark 10 5G', 'Tecno', 'Phones', NULL, 195000.00, 'new', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500', 60, 10, 0.0, 0, '2025-12-15 14:20:16'),
(11, 'Xiaomi Redmi Note 13', 'Xiaomi', 'Phones', NULL, 255000.00, 'new', 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=500', 70, 10, 0.0, 0, '2025-12-15 14:20:16'),
(12, 'Samsung Galaxy Z Fold5', 'Samsung', 'Phones', NULL, 2900000.00, 'new', 'https://images.unsplash.com/photo-1603891117381-1e2f1d4fd6a7?w=500', 5, 10, 0.0, 0, '2025-12-15 14:20:16'),
(13, 'iPhone SE (2022) 64GB', 'Apple', 'Phones', NULL, 450000.00, 'refurbished', 'https://images.unsplash.com/photo-1556656793-08538906a9f8?w=500', 28, 10, 0.0, 0, '2025-12-15 14:20:16'),
(14, 'Infinix Hot 30 Play', 'Infinix', 'Phones', NULL, 165000.00, 'new', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500', 85, 10, 0.0, 0, '2025-12-15 14:20:16'),
(15, 'Samsung Galaxy S22 Ultra', 'Samsung', 'Phones', NULL, 1250000.00, 'used', 'https://images.unsplash.com/photo-1610945264803-c22b62d2a7b3?w=500', 18, 10, 0.0, 0, '2025-12-15 14:20:16'),
(16, 'Tecno Camon 20 Pro', 'Tecno', 'Phones', NULL, 315000.00, 'new', 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?w=500', 45, 10, 0.0, 0, '2025-12-15 14:20:16'),
(17, 'iPhone 15 128GB', 'Apple', 'Phones', NULL, 1850000.00, 'new', 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=500', 14, 10, 0.0, 0, '2025-12-15 14:20:16'),
(18, 'Google Pixel 7a', 'Google', 'Phones', NULL, 750000.00, 'refurbished', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500', 11, 10, 0.0, 0, '2025-12-15 14:20:16'),
(19, 'Xiaomi Poco F5 Pro', 'Xiaomi', 'Phones', NULL, 680000.00, 'new', 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=500', 27, 10, 0.0, 0, '2025-12-15 14:20:16'),
(20, 'Infinix Zero 30 5G', 'Infinix', 'Phones', NULL, 410000.00, 'new', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500', 33, 10, 0.0, 0, '2025-12-15 14:20:16'),
(21, 'iPhone 12 64GB', 'Apple', 'Phones', NULL, 780000.00, 'used', 'https://images.unsplash.com/photo-1591337676887-a217a6970a8a?w=500', 19, 10, 0.0, 0, '2025-12-15 14:20:16'),
(22, 'Samsung Galaxy S23 FE', 'Samsung', 'Phones', NULL, 990000.00, 'new', 'https://images.unsplash.com/photo-1567581935884-3349723552ca?w=500', 22, 10, 0.0, 0, '2025-12-15 14:20:16'),
(23, 'Tecno Spark 20', 'Tecno', 'Phones', NULL, 145000.00, 'new', 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=500', 92, 10, 0.0, 0, '2025-12-15 14:20:16'),
(24, 'Xiaomi 13T Pro', 'Xiaomi', 'Phones', NULL, 1350000.00, 'refurbished', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500', 10, 10, 0.0, 0, '2025-12-15 14:20:16'),
(25, 'Google Pixel 6', 'Google', 'Phones', NULL, 550000.00, 'used', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500', 7, 10, 0.0, 0, '2025-12-15 14:20:16'),
(26, 'MacBook Pro 16\" M3 Max 36GB', 'Apple', 'Laptops', NULL, 3450000.00, 'new', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500', 6, 10, 0.0, 0, '2025-12-15 14:20:16'),
(27, 'Dell XPS 15 i9 32GB RAM', 'Dell', 'Laptops', NULL, 2800000.00, 'new', 'https://images.unsplash.com/photo-1593642532400-2682810df593?w=500', 9, 10, 0.0, 0, '2025-12-15 14:20:16'),
(28, 'HP Spectre x360 i7', 'HP', 'Laptops', NULL, 1950000.00, 'new', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 11, 10, 0.0, 0, '2025-12-15 14:20:16'),
(29, 'Lenovo ThinkPad X1 Carbon i7', 'Lenovo', 'Laptops', NULL, 2100000.00, 'new', 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500', 8, 10, 0.0, 0, '2025-12-15 14:20:16'),
(30, 'MacBook Air M2 8GB 256GB', 'Apple', 'Laptops', NULL, 1350000.00, 'refurbished', 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=500', 20, 10, 0.0, 0, '2025-12-15 14:20:16'),
(31, 'Dell Inspiron 14 i5 16GB RAM', 'Dell', 'Laptops', NULL, 720000.00, 'new', 'https://images.unsplash.com/photo-1588702547923-7093a6c3ba33?w=500', 30, 10, 0.0, 0, '2025-12-15 14:20:16'),
(32, 'HP Pavilion Gaming Laptop', 'HP', 'Laptops', NULL, 850000.00, 'new', 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', 25, 10, 0.0, 0, '2025-12-15 14:20:16'),
(33, 'Lenovo IdeaPad Slim 5', 'Lenovo', 'Laptops', NULL, 590000.00, 'new', 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=500', 45, 10, 0.0, 0, '2025-12-15 14:20:16'),
(34, 'Dell Latitude 5420 i5', 'Dell', 'Laptops', NULL, 950000.00, 'used', 'https://images.unsplash.com/photo-1593642532400-2682810df593?w=500', 15, 10, 0.0, 0, '2025-12-15 14:20:16'),
(35, 'MacBook Pro 14\" M1 Pro', 'Apple', 'Laptops', NULL, 2150000.00, 'refurbished', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500', 10, 10, 0.0, 0, '2025-12-15 14:20:16'),
(36, 'HP Envy 13 i7 16GB RAM', 'HP', 'Laptops', NULL, 1280000.00, 'new', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 18, 10, 0.0, 0, '2025-12-15 14:20:16'),
(37, 'Lenovo Legion 5 Gaming Laptop', 'Lenovo', 'Laptops', NULL, 1650000.00, 'new', 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', 14, 10, 0.0, 0, '2025-12-15 14:20:16'),
(38, 'Dell Precision 7760 Xeon', 'Dell', 'Laptops', NULL, 3300000.00, 'new', 'https://images.unsplash.com/photo-1593642532400-2682810df593?w=500', 4, 10, 0.0, 0, '2025-12-15 14:20:16'),
(39, 'MacBook Air 13\" M1', 'Apple', 'Laptops', NULL, 990000.00, 'used', 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=500', 22, 10, 0.0, 0, '2025-12-15 14:20:16'),
(40, 'HP 250 G8 i3', 'HP', 'Laptops', NULL, 450000.00, 'new', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 50, 10, 0.0, 0, '2025-12-15 14:20:16'),
(41, 'Lenovo Yoga 9i i7', 'Lenovo', 'Laptops', NULL, 1800000.00, 'new', 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=500', 7, 10, 0.0, 0, '2025-12-15 14:20:16'),
(42, 'Dell XPS 13 i7 16GB RAM', 'Dell', 'Laptops', NULL, 1450000.00, 'refurbished', 'https://images.unsplash.com/photo-1593642532400-2682810df593?w=500', 16, 10, 0.0, 0, '2025-12-15 14:20:16'),
(43, 'HP EliteBook 840 G7 i5', 'HP', 'Laptops', NULL, 780000.00, 'used', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 12, 10, 0.0, 0, '2025-12-15 14:20:16'),
(44, 'Lenovo ThinkBook 14s', 'Lenovo', 'Laptops', NULL, 690000.00, 'new', 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500', 35, 10, 0.0, 0, '2025-12-15 14:20:16'),
(45, 'MacBook Pro 13\" i5 (2020)', 'Apple', 'Laptops', NULL, 850000.00, 'used', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500', 17, 10, 0.0, 0, '2025-12-15 14:20:16'),
(46, 'Dell Alienware m16 i9', 'Dell', 'Laptops', NULL, 3500000.00, 'new', 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', 3, 10, 0.0, 0, '2025-12-15 14:20:16'),
(47, 'HP Victus 15 Ryzen 5', 'HP', 'Laptops', NULL, 620000.00, 'new', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 40, 10, 0.0, 0, '2025-12-15 14:20:16'),
(48, 'Lenovo IdeaPad Pro 5', 'Lenovo', 'Laptops', NULL, 980000.00, 'refurbished', 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=500', 13, 10, 0.0, 0, '2025-12-15 14:20:16'),
(49, 'Dell G15 Gaming Laptop', 'Dell', 'Laptops', NULL, 1150000.00, 'new', 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', 21, 10, 0.0, 0, '2025-12-15 14:20:16'),
(50, 'HP ZBook Power G8', 'HP', 'Laptops', NULL, 2500000.00, 'new', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 5, 10, 0.0, 0, '2025-12-15 14:20:16'),
(51, 'Sony WH-1000XM5 Noise Cancelling', 'Sony', 'Audio', NULL, 650000.00, 'new', 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=500', 30, 10, 0.0, 0, '2025-12-15 14:20:16'),
(52, 'Apple AirPods Pro 2nd Gen', 'Apple', 'Audio', NULL, 420000.00, 'new', 'https://images.unsplash.com/photo-1606841837239-c5a1a4a07af7?w=500', 50, 10, 0.0, 0, '2025-12-15 14:20:16'),
(53, 'JBL Flip 6 Portable Speaker', 'JBL', 'Audio', NULL, 180000.00, 'new', 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500', 45, 10, 0.0, 0, '2025-12-15 14:20:16'),
(54, 'Bose QuietComfort Earbuds II', 'Bose', 'Audio', NULL, 380000.00, 'refurbished', 'https://images.unsplash.com/photo-1590658165737-15a047b7d0c8?w=500', 15, 10, 0.0, 0, '2025-12-15 14:20:16'),
(55, 'Sennheiser HD 660S2 Open-Back', 'Sennheiser', 'Audio', NULL, 750000.00, 'new', 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=500', 9, 10, 0.0, 0, '2025-12-15 14:20:16'),
(56, 'M-Audio Nova USB Microphone', 'M-Audio', 'Audio', NULL, 120000.00, 'new', 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=500', 25, 10, 0.0, 0, '2025-12-15 14:20:16'),
(57, 'Skullcandy Hesh ANC', 'Skullcandy', 'Audio', NULL, 110000.00, 'used', 'https://images.unsplash.com/photo-1545127398-14699f92334b?w=500', 22, 10, 0.0, 0, '2025-12-15 14:20:16'),
(58, 'Logitech G PRO X Headset', 'Logitech', 'Audio', NULL, 210000.00, 'new', 'https://images.unsplash.com/photo-1599669454699-248893623440?w=500', 38, 10, 0.0, 0, '2025-12-15 14:20:16'),
(59, 'Google Nest Hub (2nd Gen)', 'Google', 'Smart Home', NULL, 150000.00, 'new', 'https://images.unsplash.com/photo-1558089687-e1b5b7e0ca5d?w=500', 40, 10, 0.0, 0, '2025-12-15 14:20:16'),
(60, 'Amazon Echo Dot (5th Gen)', 'Amazon', 'Smart Home', NULL, 75000.00, 'new', 'https://images.unsplash.com/photo-1543512214-318c7553f230?w=500', 60, 10, 0.0, 0, '2025-12-15 14:20:16'),
(61, 'TP-Link Kasa Smart Plug (4-pack)', 'TP-Link', 'Smart Home', NULL, 55000.00, 'new', 'https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?w=500', 75, 10, 0.0, 0, '2025-12-15 14:20:16'),
(62, 'Ring Video Doorbell Wired', 'Ring', 'Smart Home', NULL, 190000.00, 'refurbished', 'https://images.unsplash.com/photo-1558002038-1055907df827?w=500', 18, 10, 0.0, 0, '2025-12-15 14:20:16'),
(63, 'Philips Hue White & Color Bulb', 'Philips', 'Smart Home', NULL, 35000.00, 'new', 'https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?w=500', 88, 10, 0.0, 0, '2025-12-15 14:20:16'),
(64, 'Nest Learning Thermostat (3rd Gen)', 'Google', 'Smart Home', NULL, 320000.00, 'new', 'https://images.unsplash.com/photo-1545259741-2ea3ebf61fa3?w=500', 11, 10, 0.0, 0, '2025-12-15 14:20:16'),
(65, 'Logitech MX Master 3S Wireless Mouse', 'Logitech', 'Accessories', NULL, 115000.00, 'new', 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=500', 55, 10, 0.0, 0, '2025-12-15 14:20:16'),
(66, 'Samsung Galaxy Watch 6 Classic', 'Samsung', 'Accessories', NULL, 450000.00, 'new', 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=500', 28, 10, 0.0, 0, '2025-12-15 14:20:16'),
(67, 'Apple Watch SE (2nd Gen)', 'Apple', 'Accessories', NULL, 390000.00, 'refurbished', 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=500', 20, 10, 0.0, 0, '2025-12-15 14:20:16'),
(68, 'Anker PowerCore 20000mAh Power Bank', 'Anker', 'Accessories', NULL, 85000.00, 'new', 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=500', 80, 10, 0.0, 0, '2025-12-15 14:20:16'),
(69, 'Seagate Portable 2TB External HDD', 'Seagate', 'Accessories', NULL, 150000.00, 'new', 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?w=500', 42, 10, 0.0, 0, '2025-12-15 14:20:16'),
(70, 'Logitech K380 Multi-Device Keyboard', 'Logitech', 'Accessories', NULL, 70000.00, 'used', 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=500', 31, 10, 0.0, 0, '2025-12-15 14:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `image_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `image_order`, `is_primary`, `alt_text`, `created_at`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800', 1, 1, 'Main product view', '2025-12-17 22:30:59'),
(2, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&angle=2', 2, 0, 'Side view', '2025-12-17 22:30:59'),
(3, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&angle=3', 3, 0, 'Back view', '2025-12-17 22:30:59'),
(4, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&angle=4', 4, 0, 'Detail view', '2025-12-17 22:30:59'),
(5, 2, 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=800', 1, 1, 'Main product view', '2025-12-17 22:30:59'),
(6, 2, 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=800&angle=2', 2, 0, 'Side view', '2025-12-17 22:30:59'),
(7, 2, 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=800&angle=3', 3, 0, 'Top view', '2025-12-17 22:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_title` varchar(255) NOT NULL,
  `review_text` text NOT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `not_helpful_count` int(11) DEFAULT 0,
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `review_text` text NOT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int(11) DEFAULT 0,
  `verified_purchase` tinyint(1) DEFAULT 0,
  `admin_response` text DEFAULT NULL,
  `admin_response_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_helpfulness`
--

CREATE TABLE `review_helpfulness` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_identifier` varchar(255) NOT NULL,
  `vote_type` enum('helpful','not_helpful') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'SP Gadgets', 'text', 'Website name', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(2, 'site_email', 'info@spgadgets.com', 'text', 'Contact email', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(3, 'site_phone', '+234 XXX XXX XXXX', 'text', 'Contact phone', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(4, 'currency', 'NGN', 'text', 'Default currency', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(5, 'currency_symbol', '₦', 'text', 'Currency symbol', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(6, 'tax_rate', '0', 'number', 'Tax rate percentage', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(7, 'shipping_fee', '0', 'number', 'Default shipping fee', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(8, 'free_shipping_threshold', '50000', 'number', 'Free shipping above this amount', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(9, 'order_prefix', 'ORD', 'text', 'Order number prefix', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(10, 'enable_reviews', '1', 'boolean', 'Enable product reviews', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(11, 'enable_wishlist', '1', 'boolean', 'Enable wishlist', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(12, 'enable_newsletter', '1', 'boolean', 'Enable newsletter', '2025-12-25 15:21:44', '2025-12-25 15:21:44'),
(13, 'maintenance_mode', '0', 'boolean', 'Site maintenance mode', '2025-12-25 15:21:44', '2025-12-25 15:21:44');

-- --------------------------------------------------------

--
-- Table structure for table `stock_alerts`
--

CREATE TABLE `stock_alerts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','restocked') NOT NULL,
  `stock_level` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `change_type` enum('purchase','manual_add','manual_subtract','return','adjustment') NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, admin, return',
  `reference_id` int(11) DEFAULT NULL COMMENT 'order_id or admin_id',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `is_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'mukhiteee@gmail.com', '$2y$10$vIdq1G.XOJkHWtX4gE9j3ehbK09pyYwK0fjfyP7Z5ZDij2HRsL6v6', 'Mukhtar', 'Abdulhamid', '09025948400', 0, '45f1ffae328d73c77fd4b7d4e66ab73df5a7d717db519317410603a17389f116', NULL, NULL, '2025-12-26 18:14:54', '2025-12-19 11:51:40', '2025-12-26 18:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('shipping','billing') DEFAULT 'shipping',
  `full_address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Nigeria',
  `postal_code` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(1, 1, 'b59101747a77e2fab4076a8823325ae4d258ccb903f1241f906fe67da41bb7b9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-19 13:51:40', '2025-12-19 11:51:40'),
(2, 1, '4e1134f6e161b4e62167c3440dd862ed7c8961b658f1fb5ac1a2d4ad87453e5d', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', '2025-12-20 00:37:53', '2025-12-19 22:37:53'),
(3, 1, 'f942a9ce321c08f7d4239650b3f32272eff78dbd9c96d700dc7782384fe15828', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', '2025-12-20 13:27:10', '2025-12-20 11:27:10'),
(4, 1, '47c81043b62e680a6ab857763b99e0a50d18a4baba6000a8471ce1614126fbd2', '10.170.55.183', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2025-12-20 13:34:54', '2025-12-20 11:34:54'),
(5, 1, 'eff1ec30c91f968f485da81c1d176b2f5372bbfe726654c6e77fb78ef2d27012', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', '2025-12-20 13:55:04', '2025-12-20 11:55:04'),
(6, 1, '39c255cfa8e2f11dae853f9aa8c8758afbdb5fe49d60be8dc168aa0e358b2d6c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 11:32:27', '2025-12-25 09:32:27'),
(7, 1, 'f9a2b1ae9749f7b8c82deb25bd003555233969ef47af6a380d90f0cd5edc2e0b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 17:29:30', '2025-12-25 15:29:30'),
(8, 1, '238b64246472fd9a500c42a2aa8dfb8b30f1a49632a430a80be08223c74e4ba4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 17:41:41', '2025-12-26 15:41:41'),
(9, 1, '356e8499367c06b6046c5e0693434c695acdadea873d1c2eb00998bbc5f45dca', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 20:14:54', '2025-12-26 18:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `replied_by` (`replied_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_id` (`coupon_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_customer_email` (`customer_email`),
  ADD KEY `idx_order_status` (`order_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_image_order` (`image_order`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_approved` (`is_approved`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`user_identifier`),
  ADD KEY `idx_review_id` (`review_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`user_id`,`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD CONSTRAINT `review_helpfulness_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD CONSTRAINT `stock_alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
