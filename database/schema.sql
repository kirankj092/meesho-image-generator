-- ============================================================
-- MEESHO IMAGE GENERATOR — Complete Database Schema
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- ============================================================
-- TABLE 1: categories (parent categories)
-- ============================================================
CREATE TABLE IF NOT EXISTS categories 
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 2: subcategories
-- ============================================================
CREATE TABLE IF NOT EXISTS subcategories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(100) NOT NULL UNIQUE,
    default_weight  INT NOT NULL COMMENT 'in grams',
    weight_slab     ENUM('slab1','slab2','slab3') NOT NULL COMMENT 'slab1=0-500g, slab2=501g-1kg, slab3=1kg+',
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 3: shipping_rates
-- ============================================================
CREATE TABLE IF NOT EXISTS shipping_rates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slab        ENUM('slab1','slab2','slab3') NOT NULL,
    zone        ENUM('local','national') NOT NULL,
    min_rate    DECIMAL(8,2) NOT NULL,
    max_rate    DECIMAL(8,2) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slab_zone (slab, zone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 4: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100),
    email           VARCHAR(150) UNIQUE,
    phone           VARCHAR(15),
    password_hash   VARCHAR(255),
    google_id       VARCHAR(100),
    plan            ENUM('free','pro') DEFAULT 'free',
    trial_ends_at   TIMESTAMP NULL,
    plan_expires_at TIMESTAMP NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 5: payments
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED,
    razorpay_order_id   VARCHAR(100),
    razorpay_payment_id VARCHAR(100),
    amount              DECIMAL(10,2) NOT NULL,
    currency            VARCHAR(10) DEFAULT 'INR',
    status              ENUM('created','paid','failed') DEFAULT 'created',
    plan                ENUM('pro') DEFAULT 'pro',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 6: usage_log
-- ============================================================
CREATE TABLE IF NOT EXISTS usage_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(100),
    ip_address      VARCHAR(45),
    user_id         INT UNSIGNED NULL,
    action          ENUM('upload','generate','download_free','download_pro') NOT NULL,
    filename        VARCHAR(255),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
