-- ============================================================
-- BEE SHARP SV — MySQL Database Schema
-- Compatible with IONOS MySQL 5.7+ / MariaDB 10.3+
-- Run automatically via install.php
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- ADMIN USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(80)  NOT NULL UNIQUE,
  `email`         VARCHAR(160) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `last_login`    DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CUSTOMERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `customers` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`    VARCHAR(80)  NOT NULL,
  `last_name`     VARCHAR(80)  NOT NULL DEFAULT '',
  `email`         VARCHAR(160) DEFAULT NULL,
  `whatsapp`      VARCHAR(30)  NOT NULL,
  `address`       VARCHAR(255) DEFAULT NULL,
  `area`          VARCHAR(100) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `payment_pref`  ENUM('cash','bitcoin_lightning','bitcoin_onchain','any') DEFAULT 'any',
  `media_consent` TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `notes`         TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_whatsapp` (`whatsapp`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number`   VARCHAR(24)  NOT NULL UNIQUE,
  `customer_id`    INT UNSIGNED DEFAULT NULL,
  `customer_name`  VARCHAR(160) NOT NULL,
  `customer_phone` VARCHAR(30)  NOT NULL,
  `service_type`   ENUM('pickup_delivery','onsite','market') NOT NULL DEFAULT 'pickup_delivery',
  `pickup_address` VARCHAR(255) DEFAULT NULL,
  `scheduled_date` DATE         DEFAULT NULL,
  `scheduled_time` TIME         DEFAULT NULL,
  `status`         ENUM('pending','scheduled','picked_up','in_progress','ready','out_for_delivery','delivered','complete','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('cash','bitcoin_lightning','bitcoin_onchain') NOT NULL DEFAULT 'cash',
  `payment_status` ENUM('unpaid','paid','partial') NOT NULL DEFAULT 'unpaid',
  `subtotal`       DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `delivery_fee`   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `discount_pct`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `discount_amt`   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `media_consent`  TINYINT(1)   NOT NULL DEFAULT 0,
  `notes`          TEXT         DEFAULT NULL,
  `admin_notes`    TEXT         DEFAULT NULL,
  `wa_sent`        TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status`   (`status`),
  KEY `idx_date`     (`scheduled_date`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORDER ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED NOT NULL,
  `item_type`   ENUM('knife','axe_machete','garden_tool','pizza_cutter','repair','other') NOT NULL,
  `description` VARCHAR(200) DEFAULT NULL,
  `quantity`    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price`  DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `line_total`  DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORDER STATUS HISTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED NOT NULL,
  `old_status` VARCHAR(40)  DEFAULT NULL,
  `new_status` VARCHAR(40)  NOT NULL,
  `changed_by` VARCHAR(80)  DEFAULT 'system',
  `note`       TEXT         DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SCHEDULE / AVAILABILITY
-- ============================================================
CREATE TABLE IF NOT EXISTS `schedule_slots` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slot_date`    DATE         NOT NULL,
  `slot_time`    TIME         NOT NULL,
  `service_type` ENUM('pickup_delivery','onsite','market','any') DEFAULT 'any',
  `is_available` TINYINT(1)   NOT NULL DEFAULT 1,
  `order_id`     INT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slot` (`slot_date`,`slot_time`,`service_type`),
  KEY `idx_date` (`slot_date`),
  CONSTRAINT `fk_slot_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`        ENUM('order_status','ready','promotion','market_event','custom') NOT NULL,
  `recipient`   ENUM('single','all','bitcoin_customers','active_orders','custom_list') NOT NULL DEFAULT 'single',
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `message`     TEXT         NOT NULL,
  `channel`     ENUM('whatsapp','email') NOT NULL DEFAULT 'whatsapp',
  `status`      ENUM('pending','sent','failed') NOT NULL DEFAULT 'sent',
  `sent_by`     VARCHAR(80)  DEFAULT 'admin',
  `sent_at`     DATETIME     DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  CONSTRAINT `fk_notif_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BITCOIN PAYMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `bitcoin_payments` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED NOT NULL,
  `method`       ENUM('lightning','onchain') NOT NULL,
  `amount_usd`   DECIMAL(8,2) NOT NULL,
  `invoice_ref`  VARCHAR(255) DEFAULT NULL,
  `status`       ENUM('pending','confirmed','expired','failed') NOT NULL DEFAULT 'pending',
  `confirmed_at` DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_btc_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INVENTORY (equipment tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS `inventory` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_name`         VARCHAR(120) NOT NULL,
  `status`            ENUM('in_service','needs_service','retired') NOT NULL DEFAULT 'in_service',
  `last_service_date` DATE         DEFAULT NULL,
  `next_service_date` DATE         DEFAULT NULL,
  `quantity`          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `notes`             TEXT         DEFAULT NULL,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inventory with typical equipment
INSERT INTO `inventory` (`item_name`, `status`, `quantity`) VALUES
('Whetstone 1000 grit', 'in_service', 2),
('Whetstone 3000 grit', 'in_service', 2),
('Whetstone 6000 grit', 'in_service', 1),
('Leather strop', 'in_service', 2),
('Angle guide', 'in_service', 3),
('Bench grinder', 'in_service', 1);

-- ============================================================
-- SETTINGS (key-value store)
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT         DEFAULT NULL,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('business_name',        'Bee Sharp SV'),
('whatsapp_number',      '+50379522492'),
('email',                'bee-sharpSV@proton.me'),
('service_area',         'San Salvador, Santa Tecla, La Libertad'),
('delivery_fee',         '10.00'),
('free_delivery_min',    '10'),
('bitcoin_discount_pct', '10'),
('bulk_discount_pct',    '10'),
('bulk_discount_min',    '10'),
('price_knife',          '5.00'),
('price_axe',            '7.00'),
('price_garden',         '9.00'),
('price_pizza',          '0.00'),
('lightning_address',    ''),
('onchain_address',      ''),
('instagram_handle',     '@BEESHARP_SV'),
('facebook_url',         'https://www.facebook.com/BeeSharpSV/'),
('telegram_handle',      '@BEE_SHARP'),
('nostr_handle',         '@BEESHARP')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
